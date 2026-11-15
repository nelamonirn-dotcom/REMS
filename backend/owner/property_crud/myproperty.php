<?php
session_start();
include_once 'dbconfig.php';

if (!isset($_SESSION['email'])) {
    header('Location: ../admin/index.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../admin/unauthorize.php');
    exit;
}

$owner_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM properties WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = count($properties);
$available = count(array_filter($properties, fn($p) => ($p['status'] ?? '') === 'available'));
$arts = ['art-lakeview', 'art-palm', 'art-cedar'];

$imageUrl = static function (?string $path): string {
    if (empty($path)) {
        return '';
    }
    return implode('/', array_map('rawurlencode', explode('/', $path)));
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="View and manage your property listings.">
    <title>My Properties | EstateFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/owner/myproperty.css">
</head>
<body class="my-properties-page">
    <main class="container py-4 py-lg-5">
        <nav class="page-nav d-flex align-items-center justify-content-between mb-5" aria-label="Page navigation">
            <a class="brand-mark text-decoration-none" href="../owner_dashboard.php">
                <span class="brand-icon" aria-hidden="true">E</span>
                <span>EstateFlow</span>
            </a>
            <a class="btn btn-outline-secondary" href="../owner_dashboard.php">Back to dashboard</a>
        </nav>

        <header class="page-heading d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
            <div>
                <p class="eyebrow mb-2">OWNER WORKSPACE</p>
                <h1 class="display-6 fw-bold mb-2">My Properties</h1>
                <p class="text-secondary mb-0">Review your listings, availability, and property details.</p>
            </div>
            <a class="btn btn-primary btn-lg add-property-button" href="Addproperty.php">＋ Add property</a>
        </header>

        <section class="row g-3 mb-4" aria-label="Property totals">
            <div class="col-12 col-sm-4">
                <article class="summary-card h-100">
                    <span class="summary-label">Total properties</span>
                    <strong><?= str_pad($total, 2, '0', STR_PAD_LEFT) ?></strong>
                </article>
            </div>
            <div class="col-12 col-sm-4">
                <article class="summary-card h-100">
                    <span class="summary-label">Available</span>
                    <strong class="text-success"><?= str_pad($available, 2, '0', STR_PAD_LEFT) ?></strong>
                </article>
            </div>
            <div class="col-12 col-sm-4">
                <article class="summary-card h-100">
                    <span class="summary-label">Rented or sold</span>
                    <strong><?= str_pad($total - $available, 2, '0', STR_PAD_LEFT) ?></strong>
                </article>
            </div>
        </section>

        <section aria-label="Property listings">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <h2 class="h4 mb-0">All listings</h2>
                <span class="text-secondary small"><?= $total ?> properties</span>
            </div>

            <?php if ($total === 0): ?>
                <p class="text-secondary">You haven't added any properties yet.</p>
            <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php foreach ($properties as $i => $p): ?>
                    <?php
                    $img = $imageUrl($p['imagas'] ?? '');
                    $isRent = !empty($p['monthly_rent']) && $p['monthly_rent'] > 0;
                    $status = $p['status'] ?? '';
                    $details = trim((string)($p['flat_details'] ?? ''));
                    ?>
                <div class="col">
                    <article class="card property-card h-100">
                        <div class="property-art <?= $arts[$i % 3] ?>"
                             <?php if ($img): ?>style="background-image:url('<?= htmlspecialchars($img, ENT_QUOTES) ?>'); background-size:cover; background-position:center;"<?php endif; ?>
                             aria-label="Property illustration">
                            <span class="property-type"><?= htmlspecialchars($p['property_type'] ?? '') ?></span>
                            <span class="status-badge <?= $status === 'available' ? 'status-available' : 'status-rented' ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <h3 class="h5 card-title mb-1"><?= htmlspecialchars($p['title'] ?? '') ?></h3>
                            <p class="property-address mb-3"><?= htmlspecialchars($p['address'] ?? '') ?></p>
                            <div class="property-specs">
                                <span><strong><?= $details !== '' ? htmlspecialchars($details) : '—' ?></strong> bedrooms</span>
                                <span><strong><?= number_format((float)($p['area_sqft'] ?? 0)) ?></strong> sq. ft.</span>
                            </div>
                            <div class="property-price mt-auto pt-3">
                                <span><?= $isRent ? 'Monthly rent' : 'Price' ?></span>
                                <strong>৳ <?= number_format((float)($isRent ? $p['monthly_rent'] : ($p['price'] ?? 0))) ?></strong>
                            </div>
                        </div>
                    </article>
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