<?php
session_start();

if (!isset($_SESSION['email'])) {
    header('Location: ../admin/index.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'owner' || !isset($_SESSION['user_id']) || !ctype_digit((string) $_SESSION['user_id'])) {
    header('Location: ../admin/unauthorize.php');
    exit;
}

require_once __DIR__ . '/dbconfig.php';

$ownerId = (int) $_SESSION['user_id'];
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$errors = [];
$successMessage = '';
$properties = [];
$selectedPropertyId = 0;
$uploadedFiles = [];
$maxFiles = 10;
$maxFileSize = 5 * 1024 * 1024;
$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

if (empty($_SESSION['property_images_csrf'])) {
    $_SESSION['property_images_csrf'] = bin2hex(random_bytes(32));
}

$propertiesStmt = $conn->prepare('SELECT property_id, title FROM properties WHERE owner_id = ? ORDER BY title');
if (!$propertiesStmt) {
    error_log('Unable to prepare owner property query: ' . $conn->error);
    $errors[] = 'Your properties could not be loaded. Please try again later.';
} else {
    $propertiesStmt->bind_param('i', $ownerId);
    if (!$propertiesStmt->execute()) {
        error_log('Unable to load owner properties: ' . $propertiesStmt->error);
        $errors[] = 'Your properties could not be loaded. Please try again later.';
    } else {
        $propertiesResult = $propertiesStmt->get_result();
        while ($property = $propertiesResult->fetch_assoc()) {
            $properties[] = $property;
        }
    }
    $propertiesStmt->close();
}

$propertyById = [];
foreach ($properties as $property) {
    $propertyById[(string) $property['property_id']] = $property;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($postedToken) || !hash_equals($_SESSION['property_images_csrf'], $postedToken)) {
        $errors[] = 'Your session token expired. Refresh the page and try again.';
    }

    $postedPropertyId = filter_var($_POST['property_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($postedPropertyId === false || $postedPropertyId === null || !isset($propertyById[(string) $postedPropertyId])) {
        $errors[] = 'Choose one of your properties before uploading images.';
    } else {
        $selectedPropertyId = (int) $postedPropertyId;
    }

    $files = $_FILES['images'] ?? null;
    if (!is_array($files) || !isset($files['name'], $files['tmp_name'], $files['error'], $files['size'])) {
        $errors[] = 'Choose at least one image to upload.';
    } elseif (!is_array($files['name']) || count($files['name']) === 0) {
        $errors[] = 'Choose at least one image to upload.';
    } elseif (count($files['name']) > $maxFiles) {
        $errors[] = 'Upload no more than ' . $maxFiles . ' images at a time.';
    } else {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        foreach ($files['name'] as $index => $originalName) {
            $uploadError = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
            $temporaryPath = $files['tmp_name'][$index] ?? '';
            $fileSize = (int) ($files['size'][$index] ?? 0);

            if ($uploadError !== UPLOAD_ERR_OK) {
                $errors[] = $uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
                    ? 'Each image must be no larger than 5 MB.'
                    : 'One or more images could not be uploaded. Please select them again.';
                continue;
            }

            if ($fileSize < 1 || $fileSize > $maxFileSize || !is_uploaded_file($temporaryPath)) {
                $errors[] = 'Each image must be a valid upload no larger than 5 MB.';
                continue;
            }

            $mimeType = $fileInfo->file($temporaryPath);
            $imageDetails = @getimagesize($temporaryPath);
            if (
                !is_string($mimeType)
                || !isset($allowedMimeTypes[$mimeType])
                || $imageDetails === false
                || ($imageDetails['mime'] ?? '') !== $mimeType
            ) {
                $errors[] = 'Only valid JPG, PNG, and WebP image files are accepted.';
                continue;
            }

            $width = (int) $imageDetails[0];
            $height = (int) $imageDetails[1];
            if ($width < 1 || $height < 1 || $width > 10000 || $height > 10000 || $width * $height > 40000000) {
                $errors[] = 'Images must be no larger than 10,000 pixels per side or 40 megapixels.';
                continue;
            }

            $uploadedFiles[] = [
                'temporary_path' => $temporaryPath,
                'extension' => $allowedMimeTypes[$mimeType],
            ];
        }
    }

    if (!$errors && !$uploadedFiles) {
        $errors[] = 'Choose at least one image to upload.';
    }

    if (!$errors) {
        if (!$conn->begin_transaction()) {
            error_log('Unable to begin property image transaction: ' . $conn->error);
            $errors[] = 'The images could not be saved. Please try again later.';
        }

    }

    if (!$errors) {
        $movedFiles = [];
        $propertyLockStmt = $conn->prepare('SELECT property_id FROM properties WHERE property_id = ? AND owner_id = ? FOR UPDATE');
        $primaryStmt = $conn->prepare('SELECT image_id FROM property_images WHERE property_id = ? AND is_primary = 1 LIMIT 1');
        $insertStmt = $conn->prepare('INSERT INTO property_images (property_id, image_path, is_primary) VALUES (?, ?, ?)');

        if (!$propertyLockStmt || !$primaryStmt || !$insertStmt) {
            error_log('Unable to prepare property image database statements: ' . $conn->error);
            $errors[] = 'The images could not be saved. Please try again later.';
        } else {
            $propertyLockStmt->bind_param('ii', $selectedPropertyId, $ownerId);
            if (!$propertyLockStmt->execute()) {
                error_log('Unable to verify property ownership for image upload: ' . $propertyLockStmt->error);
                $errors[] = 'The images could not be saved. Please try again later.';
            } elseif (!$propertyLockStmt->get_result()->fetch_assoc()) {
                $errors[] = 'The selected property was not found in your account.';
            } else {
                $primaryStmt->bind_param('i', $selectedPropertyId);
                if (!$primaryStmt->execute()) {
                    error_log('Unable to check primary property image: ' . $primaryStmt->error);
                    $errors[] = 'The images could not be saved. Please try again later.';
                } else {
                    $hasPrimaryImage = (bool) $primaryStmt->get_result()->fetch_assoc();
                    $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'property-images' . DIRECTORY_SEPARATOR . $selectedPropertyId;

                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                        $errors[] = 'The image upload folder could not be created. Check the server folder permissions.';
                    } else {
                        foreach ($uploadedFiles as $index => $file) {
                            $filename = bin2hex(random_bytes(16)) . '.' . $file['extension'];
                            $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
                            $relativePath = '../uploads/property-images/' . $selectedPropertyId . '/' . $filename;
                            $isPrimary = (!$hasPrimaryImage && $index === 0) ? 1 : 0;

                            if (!move_uploaded_file($file['temporary_path'], $destination)) {
                                $errors[] = 'An image could not be moved into storage. Check the server folder permissions.';
                                break;
                            }
                            $movedFiles[] = $destination;

                            $insertStmt->bind_param('isi', $selectedPropertyId, $relativePath, $isPrimary);
                            if (!$insertStmt->execute()) {
                                error_log('Unable to save property image record: ' . $insertStmt->error);
                                $errors[] = 'An image record could not be saved. Please try again later.';
                                break;
                            }

                            if ($isPrimary) {
                                $hasPrimaryImage = true;
                            }
                        }
                    }
                }
            }
        }

        if ($propertyLockStmt) {
            $propertyLockStmt->close();
        }
        if ($primaryStmt) {
            $primaryStmt->close();
        }
        if ($insertStmt) {
            $insertStmt->close();
        }

        if ($errors) {
            $conn->rollback();
            foreach ($movedFiles as $movedFile) {
                if (is_file($movedFile) && !unlink($movedFile)) {
                    error_log('Unable to remove rolled-back property image file: ' . $movedFile);
                }
            }
        } else {
            if (!$conn->commit()) {
                error_log('Unable to commit property image transaction: ' . $conn->error);
                $conn->rollback();
                foreach ($movedFiles as $movedFile) {
                    if (is_file($movedFile) && !unlink($movedFile)) {
                        error_log('Unable to remove uncommitted property image file: ' . $movedFile);
                    }
                }
                $errors[] = 'The images could not be saved. Please try again later.';
            } else {
                header('Location: property_Images.php?property_id=' . $selectedPropertyId . '&uploaded=' . count($uploadedFiles));
                exit;
            }
        }
    }
} else {
    $requestedPropertyId = filter_var($_GET['property_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($requestedPropertyId && isset($propertyById[(string) $requestedPropertyId])) {
        $selectedPropertyId = (int) $requestedPropertyId;
    } elseif ($properties) {
        $selectedPropertyId = (int) $properties[0]['property_id'];
    }

    $uploadedCount = filter_var($_GET['uploaded'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $maxFiles]]);
    if ($uploadedCount) {
        $successMessage = $uploadedCount . ' image' . ($uploadedCount === 1 ? '' : 's') . ' saved successfully.';
    }
}

$selectedProperty = $propertyById[(string) $selectedPropertyId] ?? null;
$images = [];
if ($selectedProperty) {
    $imagesStmt = $conn->prepare('SELECT image_path, is_primary, created_at FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, image_id DESC');
    if (!$imagesStmt) {
        error_log('Unable to prepare property gallery query: ' . $conn->error);
        $errors[] = 'The property gallery could not be loaded.';
    } else {
        $imagesStmt->bind_param('i', $selectedPropertyId);
        if (!$imagesStmt->execute()) {
            error_log('Unable to load property gallery: ' . $imagesStmt->error);
            $errors[] = 'The property gallery could not be loaded.';
        } else {
            $imagesResult = $imagesStmt->get_result();
            while ($image = $imagesResult->fetch_assoc()) {
                $images[] = $image;
            }
        }
        $imagesStmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="View and select images for your property listings.">
    <title>Property Images | EstateFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/owner/property-images.css">
</head>
<body class="property-images-page">
    <main class="container py-4 py-lg-5">
        <nav class="page-nav d-flex align-items-center justify-content-between mb-5" aria-label="Page navigation">
            <a class="brand-mark text-decoration-none" href="../owner_dashboard.php">
                <span class="brand-icon" aria-hidden="true">E</span>
                <span>EstateFlow</span>
            </a>
            <a class="btn btn-outline-secondary" href="../owner_dashboard.php">Back to dashboard</a>
        </nav>

        <header class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
            <div>
                <p class="eyebrow mb-2">OWNER WORKSPACE</p>
                <h1 class="display-6 fw-bold mb-2">Property Images</h1>
                <p class="text-secondary mb-0">Choose a property and manage its listing photos.</p>
            </div>
            <a class="btn btn-outline-primary" href="myproperty.php">View my properties</a>
        </header>

        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success" role="status"><?= $escape($successMessage) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger" role="alert"><?= $escape($error) ?></div>
        <?php endforeach; ?>

        <section class="upload-panel mb-5" aria-labelledby="upload-title">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-lg-5">
                    <h2 id="upload-title" class="h5 mb-2">Add listing photos</h2>
                    <p class="text-secondary mb-0">Upload up to <?= $maxFiles ?> JPG, PNG, or WebP images. Maximum 5 MB per image.</p>
                </div>
                <div class="col-12 col-lg-7">
                    <?php if (!$properties): ?>
                        <div class="alert alert-info mb-0">Add a property before uploading its images.</div>
                    <?php else: ?>
                        <form action="property_Images.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['property_images_csrf']) ?>">
                            <label for="property" class="form-label fw-semibold">Property</label>
                            <select class="form-select mb-3" id="property" name="property_id" required>
                                <?php foreach ($properties as $property): ?>
                                    <option value="<?= (int) $property['property_id'] ?>" <?= (int) $property['property_id'] === $selectedPropertyId ? 'selected' : '' ?>>
                                        <?= $escape($property['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label for="property-files" class="form-label fw-semibold">Image files</label>
                            <div class="upload-dropzone">
                                <span class="upload-icon" aria-hidden="true">＋</span>
                                <span class="fw-semibold">Choose image files</span>
                                <span class="small text-secondary">JPG, PNG or WebP · up to 10 files</span>
                                <input class="form-control" id="property-files" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required>
                            </div>
                            <button class="btn btn-primary w-100 mt-3" type="submit">Upload and save images</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section aria-labelledby="gallery-title">
            <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-3">
                <div>
                    <h2 id="gallery-title" class="h4 mb-1">Current gallery</h2>
                    <p class="text-secondary mb-0">
                        <?= $selectedProperty ? $escape($selectedProperty['title']) : 'Select a property to view its photos.' ?>
                    </p>
                </div>
                <?php if ($properties): ?>
                    <form class="gallery-filter d-flex align-items-center gap-2" action="property_Images.php" method="get">
                        <label class="visually-hidden" for="gallery-property">Choose gallery property</label>
                        <select class="form-select form-select-sm" id="gallery-property" name="property_id">
                            <?php foreach ($properties as $property): ?>
                                <option value="<?= (int) $property['property_id'] ?>" <?= (int) $property['property_id'] === $selectedPropertyId ? 'selected' : '' ?>>
                                    <?= $escape($property['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary btn-sm" type="submit">View</button>
                    </form>
                <?php endif; ?>
                <span class="photo-count"><?= count($images) ?> photo<?= count($images) === 1 ? '' : 's' ?></span>
            </div>

            <?php if (!$selectedProperty): ?>
                <div class="empty-gallery">No properties are available yet.</div>
            <?php elseif (!$images): ?>
                <div class="empty-gallery">No images have been uploaded for this property yet.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
                    <?php foreach ($images as $image): ?>
                        <div class="col">
                            <figure class="photo-card">
                                <img src="<?= $escape($image['image_path']) ?>" alt="Property photo" loading="lazy">
                                <figcaption>
                                    <span><?= $image['is_primary'] ? 'Cover photo' : 'Property photo' ?></span>
                                    <?php if ($image['is_primary']): ?>
                                        <span class="cover-badge">Primary</span>
                                    <?php endif; ?>
                                </figcaption>
                            </figure>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <footer class="page-footer mt-5 pt-3">
            <span>EstateFlow · Owner workspace</span>
            <a href="../owner_dashboard.php">Return to dashboard</a>
        </footer>
    </main>
</body>
</html>
