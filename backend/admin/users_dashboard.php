<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: index.php");
  exit;
}

if (($_SESSION['role'] ?? '') !== "admin") {
  header("Location: unauthorize.php");
  exit;
}

include_once __DIR__ . '/dbconfig.php'; // database connection ($conn)

// ---------- Add property (form submit) ----------
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title         = trim($_POST['title'] ?? '');
  $property_type = $_POST['property_type'] ?? '';
  $status        = $_POST['status'] ?? 'available';
  $address       = trim($_POST['address'] ?? '');
  $owner_id      = (int)($_POST['owner_id'] ?? 0);
  $area_sqft     = ($_POST['area_sqft'] ?? '') !== '' ? (float)$_POST['area_sqft'] : null;
  $price         = ($_POST['price'] ?? '') !== '' ? (float)$_POST['price'] : null;
  $monthly_rent  = ($_POST['monthly_rent'] ?? '') !== '' ? (float)$_POST['monthly_rent'] : null;
  // bedrooms column nai, tai flat_details e save hobe
  $flat_details  = ($_POST['bedrooms'] ?? '') !== '' ? trim($_POST['bedrooms']) : null;
  $bathrooms     = ($_POST['bathrooms'] ?? '') !== '' ? (int)$_POST['bathrooms'] : null;
  $description   = trim($_POST['description'] ?? '');

  if ($title === '' || $address === '' || $owner_id < 1) {
    $formError = 'Title, address ar owner ID dite hobe.';
  } elseif (!in_array($property_type, ['flat', 'land', 'commercial'], true)) {
    $formError = 'Property type thik nai.';
  } elseif (!in_array($status, ['available', 'sold', 'rented'], true)) {
    $formError = 'Status thik nai.';
  } else {
    try {
      $stmt = $conn->prepare("INSERT INTO properties
        (owner_id, title, property_type, address, area_sqft, price, monthly_rent, flat_details, bathrooms, status, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param('isssdddsiss',
        $owner_id, $title, $property_type, $address,
        $area_sqft, $price, $monthly_rent, $flat_details, $bathrooms, $status, $description);
      $stmt->execute();
      header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?added=1");
      exit;
    } catch (Throwable $e) {
      $formError = 'Property save hoy ni: ' . $e->getMessage();
    }
  }
}

// ---------- Taka formatter ----------
function takaParts($n) {
  $n = (float)$n;
  if ($n >= 100000) {
    return ['৳' . rtrim(rtrim(number_format($n / 100000, 1), '0'), '.'), 'L'];
  }
  return ['৳' . number_format($n), ''];
}

$monthStart = date('Y-m-01');
$prevStart  = date('Y-m-01', strtotime('-1 month', strtotime($monthStart)));

// ---------- Stat cards ----------
$totalProperties = $addedThisMonth = 0;
$r = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(created_at >= '$monthStart'), 0) AS added FROM properties");
if ($r && ($s = $r->fetch_assoc())) {
  $totalProperties = (int)$s['total'];
  $addedThisMonth  = (int)$s['added'];
}

$revenueNow = $revenuePrev = 0;
$r = $conn->query("SELECT
    COALESCE(SUM(CASE WHEN paid_at >= '$monthStart' THEN amount END), 0) AS cur,
    COALESCE(SUM(CASE WHEN paid_at >= '$prevStart' AND paid_at < '$monthStart' THEN amount END), 0) AS prev
  FROM payments WHERE status = 'paid'");
if ($r && ($s = $r->fetch_assoc())) {
  $revenueNow  = (float)$s['cur'];
  $revenuePrev = (float)$s['prev'];
}
$revenueChange = $revenuePrev > 0 ? round(($revenueNow - $revenuePrev) / $revenuePrev * 100, 1) : null;
[$revNum, $revUnit] = takaParts($revenueNow);

$scheduledVisits = $visitsToday = 0;
$r = $conn->query("SELECT COALESCE(SUM(status = 'scheduled'), 0) AS scheduled,
                          COALESCE(SUM(status = 'scheduled' AND DATE(visit_time) = CURDATE()), 0) AS today
                   FROM visits");
if ($r && ($s = $r->fetch_assoc())) {
  $scheduledVisits = (int)$s['scheduled'];
  $visitsToday     = (int)$s['today'];
}

$pendingCommissions = $newCommissionsToday = 0;
$r = $conn->query("SELECT COALESCE(SUM(payment_status = 'pending'), 0) AS pending,
                          COALESCE(SUM(DATE(created_at) = CURDATE()), 0) AS today
                   FROM commissions");
if ($r && ($s = $r->fetch_assoc())) {
  $pendingCommissions   = (int)$s['pending'];
  $newCommissionsToday  = (int)$s['today'];
}

// ---------- Recent properties ----------
$recentProperties = $conn->query("SELECT property_id, title, property_type, address, price, monthly_rent, status
                                  FROM properties
                                  ORDER BY created_at DESC
                                  LIMIT 5");

// ---------- Upcoming visits ----------
$upcomingVisits = $conn->query("SELECT v.visit_id, v.visit_time, p.title, p.address
                                FROM visits v
                                LEFT JOIN properties p ON p.property_id = v.property_id
                                WHERE v.status = 'scheduled' AND v.visit_time >= NOW()
                                ORDER BY v.visit_time ASC
                                LIMIT 5");
?>




<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#fff2f2">
  <meta
    name="description"
    content="EstateFlow administrator dashboard overview for properties, bookings, visits, and operations.">
  <title>Admin Dashboard | EstateFlow</title>
       <!--start leftbar-->
  <?php include __DIR__ . '/../shared/partials/home_styles.php'; ?>
  <!--end leftbar-->
</head>

<body class="admin-dashboard">
  <a class="skip-link" href="#main-content">Skip to main content</a>
  <div class="admin-layout" id="admin-layout">
    <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
     <!--start leftbar-->
  <?php include __DIR__ . '/../shared/partials/admin_leftber.php'; ?>
  <!--end leftbar-->
    

    <div class="main-shell">
        <!--start leftbar-->
  <?php include __DIR__ . '/../shared/partials/header.php'; ?>
  <!--end leftbar-->

      <main class="dashboard-main" id="main-content">
        <?php if (isset($_GET['added'])): ?>
          <p class="form-notice" role="status" style="margin-bottom:16px;">Property successfully add hoyeche.</p>
        <?php endif; ?>

        <section class="page-heading" id="overview" aria-labelledby="welcome-heading">
          <div>
            <p class="page-eyebrow"><span class="live-dot"></span> Admin workspace</p>
            <h1 id="welcome-heading"><span id="greeting">Welcome, Admin</span> <span class="wave" aria-hidden="true">✦</span></h1>
            <p class="page-subtitle">Here’s what’s happening across your properties today.</p>
          </div>
          <div class="heading-actions">
            <button class="button button-primary" id="open-property-modal" type="button">
              <i class="fa-solid fa-plus" aria-hidden="true"></i>

              <span>Add property</span>
            </button>
          </div>
        </section>

        <section class="stat-grid" aria-label="Portfolio overview">
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Total properties</span><span class="stat-icon stat-icon-blue"><i class="fa-solid fa-building" aria-hidden="true"></i></span></div>
            <div class="stat-value"><?= $totalProperties ?></div>
            <div class="stat-foot"><span class="trend trend-positive"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i> +<?= $addedThisMonth ?></span><span>added this month</span></div>
            <span class="stat-spark spark-blue" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Monthly revenue</span><span class="stat-icon stat-icon-green"><i class="fa-solid fa-coins" aria-hidden="true"></i></span></div>
            <div class="stat-value"><?= htmlspecialchars($revNum) ?><?php if ($revUnit): ?><span class="stat-unit"><?= $revUnit ?></span><?php endif; ?></div>
            <div class="stat-foot">
              <?php if ($revenueChange === null): ?>
                <span>Paid payments this month</span>
              <?php else: ?>
                <span class="trend <?= $revenueChange >= 0 ? 'trend-positive' : 'trend-attention' ?>"><i class="fa-solid <?= $revenueChange >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?>" aria-hidden="true"></i> <?= abs($revenueChange) ?>%</span><span>vs. last month</span>
              <?php endif; ?>
            </div>
            <span class="stat-spark spark-green" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Scheduled visits</span><span class="stat-icon stat-icon-purple"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span></div>
            <div class="stat-value"><?= sprintf('%02d', $scheduledVisits) ?></div>
            <div class="stat-foot"><span class="trend trend-positive"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i> <?= $visitsToday ?></span><span>visits today</span></div>
            <span class="stat-spark spark-purple" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Pending commissions</span><span class="stat-icon stat-icon-amber"><i class="fa-regular fa-clock" aria-hidden="true"></i></span></div>
            <div class="stat-value"><?= sprintf('%02d', $pendingCommissions) ?></div>
            <div class="stat-foot"><span class="trend trend-attention"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Needs review</span><span><?= $newCommissionsToday ?> new today</span></div>
            <span class="stat-spark spark-amber" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
        </section>

        <style>
          .dash-panels { display:grid; grid-template-columns:2fr 1fr; gap:20px; margin:24px 0; }
          @media (max-width: 900px) { .dash-panels { grid-template-columns:1fr; } }
          .dash-panel { background:#fff; border:1px solid #e3e6ee; border-radius:14px; padding:20px; }
          .dash-panel h2 { font-size:18px; margin:0 0 4px; }
          .dash-panel > p { margin:0 0 14px; color:#6b7280; font-size:14px; }
          .dash-table { width:100%; border-collapse:collapse; font-size:14px; }
          .dash-table th { text-align:left; color:#6b7280; font-weight:600; padding:8px 6px; border-bottom:1px solid #e3e6ee; }
          .dash-table td { padding:10px 6px; border-bottom:1px solid #f0f2f7; }
          .dash-table small { display:block; color:#6b7280; }
          .dash-badge { display:inline-block; font-size:12px; padding:3px 10px; border-radius:20px; background:#eef2ff; }
          .dash-list { list-style:none; margin:0; padding:0; }
          .dash-list li { padding:10px 0; border-bottom:1px solid #f0f2f7; }
          .dash-list li:last-child { border-bottom:0; }
          .dash-list strong { display:block; }
          .dash-list small { color:#6b7280; }
          .dash-empty { color:#6b7280; padding:10px 0; }
        </style>

        <section class="dash-panels" aria-label="Recent activity">
          <article class="dash-panel">
            <h2>Recent properties</h2>
            <p>Latest 5 properties added.</p>
            <div style="overflow-x:auto;">
              <table class="dash-table">
                <thead>
                  <tr><th>Property</th><th>Type</th><th>Price / Rent</th><th>Status</th></tr>
                </thead>
                <tbody>
                  <?php if ($recentProperties && $recentProperties->num_rows > 0): ?>
                    <?php while ($rp = $recentProperties->fetch_assoc()): ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($rp['title']) ?></strong><small><?= htmlspecialchars($rp['address']) ?></small></td>
                        <td><?= htmlspecialchars(ucfirst($rp['property_type'])) ?></td>
                        <td>
                          <?php if ($rp['price'] !== null): ?>৳ <?= number_format($rp['price']) ?><?php endif; ?>
                          <?php if ($rp['monthly_rent'] !== null): ?><?= $rp['price'] !== null ? '<br>' : '' ?>৳ <?= number_format($rp['monthly_rent']) ?>/month<?php endif; ?>
                          <?php if ($rp['price'] === null && $rp['monthly_rent'] === null): ?>—<?php endif; ?>
                        </td>
                        <td><span class="dash-badge"><?= htmlspecialchars(ucfirst($rp['status'])) ?></span></td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr><td colspan="4" class="dash-empty">Ekhono kono property nai.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </article>

          <article class="dash-panel">
            <h2>Upcoming visits</h2>
            <p>Next 5 scheduled visits.</p>
            <ul class="dash-list">
              <?php if ($upcomingVisits && $upcomingVisits->num_rows > 0): ?>
                <?php while ($uv = $upcomingVisits->fetch_assoc()): ?>
                  <li>
                    <strong><?= htmlspecialchars($uv['title'] ?? 'Property removed') ?></strong>
                    <small><?= htmlspecialchars(date('M d · h:i A', strtotime($uv['visit_time']))) ?><?= !empty($uv['address']) ? ' · ' . htmlspecialchars($uv['address']) : '' ?></small>
                  </li>
                <?php endwhile; ?>
              <?php else: ?>
                <li class="dash-empty">Kono upcoming visit nai.</li>
              <?php endif; ?>
            </ul>
          </article>
        </section>

            <!--start leftbar-->
  <?php include __DIR__ . '/../shared/partials/footer.php'; ?>
  <!--end leftbar-->
      </main>
    </div>
  </div>

  <dialog class="property-dialog" id="property-dialog" aria-labelledby="property-dialog-title">
    <form class="property-form" id="property-form" method="post" action="">
      <div class="dialog-heading">
        <div><span class="dialog-icon"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i></span>
          <h2 id="property-dialog-title">Add a property</h2>
          <p>Complete the details that match the property record.</p>
        </div>
        <button class="icon-button dialog-close" id="close-property-modal" type="button" aria-label="Close dialog"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </div>
      <div class="form-grid">
        <label class="form-field form-field-wide" for="property-title">Title
          <input id="property-title" name="title" type="text" maxlength="100" placeholder="e.g. Lakeview Heights" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        </label>
        <label class="form-field" for="property-form-type">Property type
          <select id="property-form-type" name="property_type" required>
            <option value="">Select type</option>
            <?php foreach (['flat' => 'Flat', 'land' => 'Land', 'commercial' => 'Commercial'] as $v => $l): ?>
              <option value="<?= $v ?>" <?= ($_POST['property_type'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="form-field" for="property-status">Status
          <select id="property-status" name="status" required>
            <?php foreach (['available' => 'Available', 'sold' => 'Sold', 'rented' => 'Rented'] as $v => $l): ?>
              <option value="<?= $v ?>" <?= ($_POST['status'] ?? 'available') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="form-field form-field-wide" for="property-address">Address
          <input id="property-address" name="address" type="text" maxlength="255" placeholder="Area, city, and street address" required value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
        </label>
        <label class="form-field" for="property-owner">Property owner
          <input id="property-owner" name="owner_id" type="number" min="1" step="1" placeholder="Owner user ID" required value="<?= htmlspecialchars($_POST['owner_id'] ?? '') ?>">
        </label>
        <label class="form-field" for="property-area">Area (sq ft)
          <input id="property-area" name="area_sqft" type="number" min="0" step="0.01" placeholder="e.g. 1450" value="<?= htmlspecialchars($_POST['area_sqft'] ?? '') ?>">
        </label>
        <label class="form-field" for="property-price">Sale price (৳)
          <input id="property-price" name="price" type="number" min="0" step="0.01" placeholder="Optional" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
        </label>
        <label class="form-field" for="property-rent">Monthly rent (৳)
          <input id="property-rent" name="monthly_rent" type="number" min="0" step="0.01" placeholder="Optional" value="<?= htmlspecialchars($_POST['monthly_rent'] ?? '') ?>">
        </label>
        <label class="form-field" for="property-bedrooms">Bedrooms
          <input id="property-bedrooms" name="bedrooms" type="number" min="0" step="1" placeholder="Optional" value="<?= htmlspecialchars($_POST['bedrooms'] ?? '') ?>">
        </label>
        <label class="form-field" for="property-bathrooms">Bathrooms
          <input id="property-bathrooms" name="bathrooms" type="number" min="0" step="1" placeholder="Optional" value="<?= htmlspecialchars($_POST['bathrooms'] ?? '') ?>">
        </label>
        <label class="form-field form-field-wide" for="property-description">Description
          <textarea id="property-description" name="description" rows="4" maxlength="65535" placeholder="Describe the property"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </label>
      </div>
      <p class="form-notice" id="property-form-notice" role="status" <?= $formError === '' ? 'hidden' : '' ?>><?= htmlspecialchars($formError) ?></p>
      <div class="dialog-actions"><button class="button button-quiet" id="cancel-property-modal" type="button">Cancel</button><button class="button button-primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save property</button></div>
    </form>
  </dialog>

  <?php if ($formError !== ''): ?>
  <script>
    // error hole dialog abar khule dibe
    document.addEventListener('DOMContentLoaded', function () {
      var d = document.getElementById('property-dialog');
      if (d && !d.open) d.showModal();
    });
  </script>
  <?php endif; ?>
</body>

</html>