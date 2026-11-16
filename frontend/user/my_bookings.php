<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: ../../backend/admin/index.php");
  exit;
}

if (($_SESSION['role'] ?? '') !== "customer") {
  header("Location: ../../backend/admin/unauthorize.php");
  exit;
}

// dbconfig.php koi ache seta auto khuje nibe
$dbCandidates = [
  __DIR__ . '/dbconfig.php',
  __DIR__ . '/../dbconfig.php',
  __DIR__ . '/../../dbconfig.php',
  __DIR__ . '/../../backend/dbconfig.php',
  __DIR__ . '/../../backend/admin/dbconfig.php',
];
foreach ($dbCandidates as $file) {
  if (file_exists($file)) { include_once $file; break; }
}
if (!isset($conn)) {
  die('dbconfig.php khuje pawa jayni. $dbCandidates er path gulo check koro.');
}

$userId = (int)($_SESSION['user_id'] ?? 0);

// ---------- Amar bookings ----------
$stmt = $conn->prepare("SELECT b.booking_id, b.property_id, b.booking_date, b.status,
                               p.title, p.address, p.property_type, p.price, p.monthly_rent
                        FROM bookings b
                        LEFT JOIN properties p ON p.property_id = b.property_id
                        WHERE b.customer_id = ?
                        ORDER BY b.booking_date DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$bookings = $stmt->get_result();
$total    = $bookings->num_rows;

// ---------- Amar visits ----------
$stmt2 = $conn->prepare("SELECT v.visit_id, v.property_id, v.visit_time, v.status,
                                p.title, p.address
                         FROM visits v
                         LEFT JOIN properties p ON p.property_id = v.property_id
                         WHERE v.customer_id = ?
                         ORDER BY v.visit_time DESC");
$stmt2->bind_param('i', $userId);
$stmt2->execute();
$visits = $stmt2->get_result();

function statusLabel($s) {
  return $s === 'approved' ? 'Confirmed' : ucfirst($s);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#102b2b">
  <title>My bookings | EstateFlow</title>
  <link rel="stylesheet" href="partials/style.css">
  <style>
    .my-section { padding: 40px 0 20px; }
    .my-section .section-head { margin-bottom: 20px; }
    .my-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:24px; margin-bottom:50px; }
    .my-card { border:1px solid #e3e3e3; border-radius:14px; padding:20px; background:#fff; }
    .my-card h3 { margin:10px 0 4px; }
    .badge { display:inline-block; font-size:12px; padding:3px 10px; border-radius:20px; background:#e8f1f1; margin-right:6px; }
    .badge-pending, .badge-scheduled { background:#fff4d6; }
    .badge-approved, .badge-completed { background:#dcf5e3; }
    .badge-cancelled { background:#fde1e1; }
    .my-meta { color:#555; font-size:14px; margin:8px 0 14px; }
    .my-empty { color:#555; margin-bottom:40px; }
  </style>
</head>
<body>

  <!--start header-->
  <?php include __DIR__ . '/partials/customar_header.php'; ?>
  <!--end header-->
  <style>
    .customer-subnav { background:#102b2b; }
    .customer-subnav .shell { display:flex; gap:22px; padding:12px 0; flex-wrap:wrap; }
    .customer-subnav a { color:#e8f1f1; text-decoration:none; font-size:15px; font-weight:500; padding-bottom:4px; border-bottom:2px solid transparent; }
    .customer-subnav a:hover, .customer-subnav a.active { color:#fff; border-bottom-color:#fff; }
  </style>
  <nav class="customer-subnav" aria-label="Customer menu">
    <div class="shell">
      <a href="customers_dashboard.php">Home</a>
      <a href="customers_dashboard.php#properties">Properties</a>
      <a href="my_bookings.php" class="active">My bookings</a>
    </div>
  </nav>
  

  <main>
    <section class="my-section">
      <div class="shell">
        <div class="section-head">
          <h2>My bookings</h2>
          <p><?= $total ?> ta booking</p>
        </div>

        <?php if ($total > 0): ?>
          <div class="my-grid">
            <?php while ($b = $bookings->fetch_assoc()): ?>
              <article class="my-card">
                <span class="badge"><?= htmlspecialchars(ucfirst($b['property_type'] ?? 'Property')) ?></span>
                <span class="badge badge-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars(statusLabel($b['status'])) ?></span>
                <h3><?= htmlspecialchars($b['title'] ?? 'Property removed') ?></h3>
                <p><?= htmlspecialchars($b['address'] ?? '') ?></p>
                <p class="my-meta">
                  Booking date: <?= htmlspecialchars(date('M d, Y', strtotime($b['booking_date']))) ?><br>
                  <?php if ($b['monthly_rent'] !== null): ?>
                    Rent: ৳<?= number_format($b['monthly_rent']) ?>/month
                  <?php elseif ($b['price'] !== null): ?>
                    Price: ৳<?= number_format($b['price']) ?>
                  <?php endif; ?>
                </p>
                <?php if (!empty($b['property_id'])): ?>
                  <a class="button" href="property-details.php?id=<?= (int)$b['property_id'] ?>">View property</a>
                <?php endif; ?>
              </article>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p class="my-empty">Tumi ekhono kono property booking koro ni.</p>
        <?php endif; ?>

        <div class="section-head">
          <h2>My visits</h2>
          <p>Tomar schedule kora property visit</p>
        </div>

        <?php if ($visits->num_rows > 0): ?>
          <div class="my-grid">
            <?php while ($v = $visits->fetch_assoc()): ?>
              <article class="my-card">
                <span class="badge badge-<?= htmlspecialchars($v['status']) ?>"><?= htmlspecialchars(ucfirst($v['status'])) ?></span>
                <h3><?= htmlspecialchars($v['title'] ?? 'Property removed') ?></h3>
                <p><?= htmlspecialchars($v['address'] ?? '') ?></p>
                <p class="my-meta">Visit: <?= htmlspecialchars(date('M d, Y · h:i A', strtotime($v['visit_time']))) ?></p>
              </article>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p class="my-empty">Kono visit schedule kora nai.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <!--footer-->
  <?php include __DIR__ . '/partials/footer.php'; ?>
  <!--footer-->
  <script src="partials/script.js" defer></script>
</body>
</html>