<?php
session_start();
require_once __DIR__ . '/dbconfig.php';

if (!isset($_SESSION['email'])) {
    header('Location: ../../admin/index.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../../admin/unauthorize.php');
    exit;
}

$ownerId = (int)($_SESSION['user_id'] ?? 0);
if ($ownerId < 1) {
    http_response_code(403);
    exit('Owner account is unavailable.');
}

if (empty($_SESSION['property_csrf'])) {
    $_SESSION['property_csrf'] = bin2hex(random_bytes(32));
}

$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$form = [
    'title' => '',
    'property_type' => '',
    'area_sqft' => '',
    'address' => '',
    'price' => '',
    'monthly_rent' => '',
    'flat_details' => '',
    'status' => 'available',
    'description' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['property_csrf'], $csrf)) {
        http_response_code(400);
        exit('Invalid request.');
    }

    $postString = static function (string $key): string {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    };
    foreach ($form as $key => $default) {
        $form[$key] = $postString($key);
    }

    if ($form['title'] === '' || strlen($form['title']) > 100) {
        $errors[] = 'Enter a property title up to 100 characters.';
    }
    if (!in_array($form['property_type'], ['flat', 'land', 'commercial'], true)) {
        $errors[] = 'Choose a valid property type.';
    }
    if ($form['address'] === '' || strlen($form['address']) > 255) {
        $errors[] = 'Enter an address up to 255 characters.';
    }
    if ($form['flat_details'] === '') {
        $errors[] = 'Enter the bedroom/details value.';
    }
    if (!in_array($form['status'], ['available', 'sold', 'rented'], true)) {
        $errors[] = 'Choose a valid property status.';
    }
    foreach (['area_sqft' => 'Area', 'price' => 'Price', 'monthly_rent' => 'Monthly rent'] as $key => $label) {
        $value = $form[$key];
        if ($value !== '' && (!is_numeric($value) || (float)$value < 0)) {
            $errors[] = $label . ' must be a non-negative number.';
        }
    }

    $photos = [];
    $uploads = $_FILES['photos'] ?? null;
    if ($uploads !== null) {
        $fileNames = $uploads['name'] ?? null;
        $fileErrors = $uploads['error'] ?? null;
        $fileSizes = $uploads['size'] ?? null;
        $temporaryNames = $uploads['tmp_name'] ?? null;
        if (
            !is_array($fileNames)
            || !is_array($fileErrors)
            || !is_array($fileSizes)
            || !is_array($temporaryNames)
        ) {
            $errors[] = 'The uploaded images are invalid.';
        } elseif (count($fileNames) > 10) {
            $errors[] = 'Upload no more than 10 images.';
        } else {
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];
            foreach (array_keys($fileNames) as $index) {
                $uploadError = $fileErrors[$index] ?? UPLOAD_ERR_NO_FILE;
                if ($uploadError === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $temporaryName = $temporaryNames[$index] ?? null;
                $fileSize = $fileSizes[$index] ?? 0;
                if (
                    $uploadError !== UPLOAD_ERR_OK
                    || !is_int($fileSize)
                    || $fileSize < 1
                    || $fileSize > 5 * 1024 * 1024
                ) {
                    $errors[] = 'Each image must be smaller than 5 MB.';
                    continue;
                }
                if (!is_string($temporaryName) || !is_uploaded_file($temporaryName)) {
                    $errors[] = 'One of the uploaded images is invalid.';
                    continue;
                }

                $mimeType = $fileInfo->file($temporaryName);
                if (!is_string($mimeType) || !isset($extensions[$mimeType])) {
                    $errors[] = 'Upload JPEG, PNG, or WebP images only.';
                    continue;
                }

                $photos[] = [
                    'tmp_name' => $temporaryName,
                    'file_name' => bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType],
                ];
            }
        }
    }

    $uploadDirectory = __DIR__ . '/uploads';
    if ($photos && (!is_dir($uploadDirectory) || !is_writable($uploadDirectory))) {
        $errors[] = 'The property image directory is unavailable.';
    }

    if (!$errors) {
        $areaSqft = $form['area_sqft'] === '' ? null : $form['area_sqft'];
        $price = $form['price'] === '' ? null : $form['price'];
        $monthlyRent = $form['monthly_rent'] === '' ? null : $form['monthly_rent'];
        $description = $form['description'] === '' ? null : $form['description'];
        $savedImages = [];
        $conn->begin_transaction();
        try {
            foreach ($photos as $photo) {
                $absolutePath = $uploadDirectory . '/' . $photo['file_name'];
                if (!move_uploaded_file($photo['tmp_name'], $absolutePath)) {
                    throw new RuntimeException('An uploaded property image could not be saved.');
                }
                $savedImages[] = [
                    'path' => 'uploads/' . $photo['file_name'],
                    'absolute_path' => $absolutePath,
                ];
            }

            $imagePath = $savedImages[0]['path'] ?? '';
            $insert = $conn->prepare(
                'INSERT INTO properties
                 (owner_id, title, property_type, area_sqft, address, price, monthly_rent,
                  flat_details, status, imagas, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->bind_param(
                'issssssssss',
                $ownerId,
                $form['title'],
                $form['property_type'],
                $areaSqft,
                $form['address'],
                $price,
                $monthlyRent,
                $form['flat_details'],
                $form['status'],
                $imagePath,
                $description
            );
            $insert->execute();
            $propertyId = (int)$conn->insert_id;

            if ($savedImages) {
                $imageInsert = $conn->prepare(
                    'INSERT INTO property_images (property_id, image_path, is_primary) VALUES (?, ?, ?)'
                );
                foreach ($savedImages as $index => $image) {
                    $imagePrimary = $index === 0 ? 1 : 0;
                    $imagePath = $image['path'];
                    $imageInsert->bind_param('isi', $propertyId, $imagePath, $imagePrimary);
                    $imageInsert->execute();
                }
            }

            $conn->commit();
            header('Location: myproperty.php?msg=added');
            exit;
        } catch (Throwable $exception) {
            $conn->rollback();
            foreach ($savedImages as $image) {
                if (is_file($image['absolute_path']) && !unlink($image['absolute_path'])) {
                    error_log('Unable to clean up an image after property creation failed.');
                }
            }
            error_log('Property creation failed: ' . $exception->getMessage());
            $errors[] = 'Property could not be saved. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="../assets/css/owner/dashboard.css">
    <link rel="stylesheet" href="../assets/css/owner/add-property.css">
    <link rel="stylesheet" href="../../shared/assets/css/palette.css">
    
</head>

<body class="add-property-page">
    <div class="owner-layout">
        <?php
        $ownerPageBase = '../';
        $ownerPageTitle = 'Add Property';
        $ownerActivePage = 'add-property';
        include __DIR__ . '/../include/owner_leftber.php';
        ?>
        <div class="owner-main-shell">
            <?php include __DIR__ . '/../../shared/partials/header.php'; ?>
    <main class="container py-5">
        <div class="card property-card mx-auto">
            <div class="property-card-header card-body p-4 p-md-5 pb-4">
                <a href="myproperty.php" class="link-secondary text-decoration-none small">&larr; Back to property list</a>
                <h1 class="h3 mt-3 mb-2">Add New Property</h1>
                <p class="text-secondary mb-0">Enter the property details below.</p>
            </div>

            <div class="card-body p-4 p-md-5 pt-4">
                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $escape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= $escape($_SESSION['property_csrf']) ?>">
                    <div class="row g-4">


                        <div class="col-12">
                            <label for="title" class="form-label">Property title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" maxlength="100" placeholder="e.g. Modern 3-bedroom apartment" required value="<?= $escape($form['title']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="property_type" class="form-label">Property type <span class="text-danger">*</span></label>
                            <select class="form-select" id="property_type" name="property_type" required>
                                <option value="" disabled <?= $form['property_type'] === '' ? 'selected' : '' ?>>Select property type</option>
                                <option value="flat" <?= $form['property_type'] === 'flat' ? 'selected' : '' ?>>Flat</option>
                                <option value="land" <?= $form['property_type'] === 'land' ? 'selected' : '' ?>>Land</option>
                                <option value="commercial" <?= $form['property_type'] === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="area_sqft" class="form-label">Area (sq. ft.)</label>
                            <input type="number" class="form-control" id="area_sqft" name="area_sqft" min="0" step="0.01" placeholder="e.g. 1200" value="<?= $escape($form['area_sqft']) ?>">
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address" name="address" maxlength="255" placeholder="Street, area, city" required value="<?= $escape($form['address']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="price" class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" placeholder="Enter price" value="<?= $escape($form['price']) ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="monthly_rent" class="form-label">Monthly rent</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" class="form-control" id="monthly_rent" name="monthly_rent" min="0" step="0.01" placeholder="Enter monthly rent" value="<?= $escape($form['monthly_rent']) ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="flat_details" class="form-label">Bedrooms/details <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="flat_details" name="flat_details" required placeholder="Number of bedrooms or details" value="<?= $escape($form['flat_details']) ?>">
                        </div>

                        <div class="col-12">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available" <?= $form['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="sold" <?= $form['status'] === 'sold' ? 'selected' : '' ?>>Sold</option>
                                <option value="rented" <?= $form['status'] === 'rented' ? 'selected' : '' ?>>Rented</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="photos" class="form-label">Property images (optional, up to 10 images, 5 MB each)</label>
                            <input type="file" class="form-control" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Add more details about the property"><?= $escape($form['description']) ?></textarea>
                        </div>

                        <div class="col-12 d-flex flex-column flex-sm-row justify-content-end gap-2 pt-2">
                            <a href="myproperty.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                            <button type="submit" name="submit" class="btn btn-primary px-4 py-2">Save Property</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>