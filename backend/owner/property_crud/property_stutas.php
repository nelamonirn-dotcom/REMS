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

$stmt = $conn->prepare("SELECT * FROM properties WHERE owner_id = ?");
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = count($properties);
$available = count(array_filter($properties, fn($p) => $p['status'] === 'available'));
$rented = count(array_filter($properties, fn($p) => $p['status'] === 'rented'));
$sold = count(array_filter($properties, fn($p) => $p['status'] === 'sold'));
$arts = ['art-lakeview', 'art-palm', 'art-cedar'];

?>



<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Review the status of your property listings.">
    <title>Property Status | EstateFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/owner/property-status.css">
</head>
<body class="property-status-page">
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
                <h1 class="display-6 fw-bold mb-2">Property Status</h1>
                <p class="text-secondary mb-0">Check availability and pricing across your property listings.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="myproperty.php">My properties</a>
                <a class="btn btn-primary" href="Addproperty.php">＋ Add property</a>
            </div>
        </header>

        <section class="row g-3 mb-4" aria-label="Property status totals">
            <div class="col-12 col-sm-4">
                <article class="summary-card h-100">
                    <span class="summary-label">Available</span>
                    <strong class="status-text-available"><?= str_pad($available, 2, '0', STR_PAD_LEFT) ?></strong>
                </article>
            </div>
            <div class="col-12 col-sm-4">
                <article class="summary-card h-100">
                    <span class="summary-label">Rented</span>
                    <strong class="status-text-rented"><?= str_pad($rented, 2, '0', STR_PAD_LEFT) ?></strong>
                </article>
            </div>
            <div class="col-12 col-sm-4">
                <article class="summary-card h-100">
                    <span class="summary-label">Sold</span>
                    <strong class="status-text-sold"><?= str_pad($sold, 2, '0', STR_PAD_LEFT) ?></strong>
                </article>
            </div>
        </section>

        <section class="listing-panel" aria-labelledby="listing-title">
            <div class="listing-panel-heading d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 id="listing-title" class="h4 mb-1">Listing overview</h2>
                    <p class="text-secondary mb-0">Status and key details for each property.</p>
                </div>
                <span class="listing-count"><?= $total ?> listings</span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Property</th>
                            <th scope="col">Type</th>
                            <th scope="col">Area</th>
                            <th scope="col">Price / rent</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($properties as $p): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($p['title']) ?></strong>
                                <small><?= htmlspecialchars($p['address']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($p['property_type']) ?></td>
                            <td><?= number_format($p['area_sqft']) ?> sq. ft.</td>
                            <td><span class="price-caption"><?= $p['status'] === 'rented' ? 'Monthly rent' : 'Price' ?></span>৳ <?= number_format($p['price']) ?></td>
                            <td><span class="status-badge status-<?= htmlspecialchars($p['status']) ?>"><?= htmlspecialchars(ucfirst($p['status'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <footer class="page-footer mt-5 pt-3">
            <span>EstateFlow · Owner workspace</span>
            <a href="../owner_dashboard.php">Return to dashboard</a>
        </footer>
    </main>
</body>
</html>