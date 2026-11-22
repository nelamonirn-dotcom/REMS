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
$rawPropertyId = $_GET['id'] ?? $_POST['property_id'] ?? '';
$propertyId = is_scalar($rawPropertyId) ? (int)$rawPropertyId : 0;
if ($ownerId < 1 || $propertyId < 1) {
    http_response_code(404);
    exit('Property not found.');
}

if (empty($_SESSION['property_csrf'])) {
    $_SESSION['property_csrf'] = bin2hex(random_bytes(32));
}

$stmt = $conn->prepare('SELECT * FROM properties WHERE property_id = ? AND owner_id = ?');
$stmt->bind_param('ii', $propertyId, $ownerId);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
if (!$property) {
    http_response_code(404);
    exit('Property not found.');
}

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
    $title = $postString('title');
    $propertyType = $postString('property_type');
    $address = $postString('address');
    $areaSqft = $postString('area_sqft');
    $price = $postString('price');
    $monthlyRent = $postString('monthly_rent');
    $flatDetails = $postString('flat_details');
    $status = $postString('status');
    $description = $postString('description');

    if ($title === '' || strlen($title) > 100) {
        $errors[] = 'Enter a property title up to 100 characters.';
    }
    if (!in_array($propertyType, ['flat', 'land', 'commercial'], true)) {
        $errors[] = 'Choose a valid property type.';
    }
    if ($address === '' || strlen($address) > 255) {
        $errors[] = 'Enter an address up to 255 characters.';
    }
    if ($flatDetails === '') {
        $errors[] = 'Enter the bedroom/details value.';
    }
    if (!in_array($status, ['available', 'sold', 'rented'], true)) {
        $errors[] = 'Choose a valid property status.';
    }

    foreach (['Area' => $areaSqft, 'Price' => $price, 'Monthly rent' => $monthlyRent] as $label => $value) {
        if ($value !== '' && (!is_numeric($value) || (float)$value < 0)) {
            $errors[] = $label . ' must be a non-negative number.';
        }
    }

    if (!$errors) {
        $areaValue = $areaSqft === '' ? null : $areaSqft;
        $priceValue = $price === '' ? null : $price;
        $rentValue = $monthlyRent === '' ? null : $monthlyRent;
        $descriptionValue = $description === '' ? null : $description;
        $update = $conn->prepare(
            'UPDATE properties
             SET title = ?, property_type = ?, area_sqft = ?, address = ?, price = ?,
                 monthly_rent = ?, flat_details = ?, status = ?, description = ?
             WHERE property_id = ? AND owner_id = ?'
        );
        $update->bind_param(
            'sssssssssii',
            $title,
            $propertyType,
            $areaValue,
            $address,
            $priceValue,
            $rentValue,
            $flatDetails,
            $status,
            $descriptionValue,
            $propertyId,
            $ownerId
        );
        $update->execute();
        header('Location: myproperty.php?msg=updated');
        exit;
    }

    $property = array_merge($property, [
        'title' => $title,
        'property_type' => $propertyType,
        'address' => $address,
        'area_sqft' => $areaSqft,
        'price' => $price,
        'monthly_rent' => $monthlyRent,
        'flat_details' => $flatDetails,
        'status' => $status,
        'description' => $description,
    ]);
}

$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Property | EstateFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/owner/dashboard.css">
    <link rel="stylesheet" href="../../shared/assets/css/palette.css">
</head>
<body>
    <div class="owner-layout">
        <?php
        $ownerPageBase = '../';
        $ownerPageTitle = 'Edit Property';
        $ownerActivePage = 'properties';
        include __DIR__ . '/../include/owner_leftber.php';
        ?>
        <div class="owner-main-shell">
            <?php include __DIR__ . '/../../shared/partials/header.php'; ?>
            <main class="container py-4">
                <div class="card mx-auto" style="max-width: 760px;">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-4">Edit property</h1>
                        <?php if ($errors): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= $escape($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= $escape($_SESSION['property_csrf']) ?>">
                            <input type="hidden" name="property_id" value="<?= $propertyId ?>">
                            <div class="mb-3">
                                <label class="form-label" for="title">Property title</label>
                                <input class="form-control" id="title" name="title" maxlength="100" required value="<?= $escape($property['title']) ?>">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="property_type">Property type</label>
                                    <select class="form-select" id="property_type" name="property_type" required>
                                        <?php foreach (['flat' => 'Flat', 'land' => 'Land', 'commercial' => 'Commercial'] as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $property['property_type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="status">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <?php foreach (['available' => 'Available', 'sold' => 'Sold', 'rented' => 'Rented'] as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $property['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="address">Address</label>
                                    <input class="form-control" id="address" name="address" maxlength="255" required value="<?= $escape($property['address']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="area_sqft">Area (sq. ft.)</label>
                                    <input class="form-control" type="number" id="area_sqft" name="area_sqft" min="0" step="0.01" value="<?= $escape($property['area_sqft']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="flat_details">Bedrooms/details</label>
                                    <input class="form-control" id="flat_details" name="flat_details" required value="<?= $escape($property['flat_details']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="price">Price</label>
                                    <input class="form-control" type="number" id="price" name="price" min="0" step="0.01" value="<?= $escape($property['price']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="monthly_rent">Monthly rent</label>
                                    <input class="form-control" type="number" id="monthly_rent" name="monthly_rent" min="0" step="0.01" value="<?= $escape($property['monthly_rent']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?= $escape($property['description']) ?></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a class="btn btn-outline-secondary" href="myproperty.php">Cancel</a>
                                <button class="btn btn-primary" type="submit">Save changes</button>
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
