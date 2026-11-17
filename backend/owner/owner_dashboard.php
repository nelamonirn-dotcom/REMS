<?php
session_start();
include_once __DIR__ . '/property_crud/dbconfig.php'; // dbconfig.php যেখানে আছে সেই পথ দাও

if (!isset($_SESSION['email'])) {
  header('Location: ../admin/index.php');
  exit;
}

if (($_SESSION['role'] ?? '') !== 'owner') {
  header('Location: ../admin/unauthorize.php');
  exit;
}

$ownerName = $_SESSION['name'] ?? 'Property Owner';
$ownerEmail = $_SESSION['email'] ?? '';
$ownerPageBase = '';
$ownerPageTitle = 'Overview';
$ownerActivePage = 'dashboard';
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

// ডাটাবেস থেকে এই owner-এর প্রপার্টি
$owner_id = $_SESSION['user_id'];
// owner_id যদি INT হয়, তাহলে "s" এর জায়গায় "i" দাও
$stmt = $conn->prepare("SELECT * FROM properties WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $owner_id);
$stmt->execute();
$properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalProps = count($properties);
$availableProps = count(array_filter($properties, fn($p) => ($p['status'] ?? '') === 'available'));
$imgClasses = ['property-image-one', 'property-image-two', 'property-image-three'];

// Booking approve / cancel (শুধু নিজের প্রপার্টির booking বদলানো যাবে)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['new_status'])) {
  if (in_array($_POST['new_status'], ['approved', 'cancelled'], true)) {
    $bid = (int)$_POST['booking_id'];
    $newStatus = $_POST['new_status'];
    $upd = $conn->prepare("UPDATE bookings b JOIN properties p ON p.property_id = b.property_id SET b.status = ? WHERE b.booking_id = ? AND p.owner_id = ?");
    $upd->bind_param("sis", $newStatus, $bid, $owner_id);
    $upd->execute();
  }
  header('Location: owner_dashboard.php#bookings');
  exit;
}

// এই owner-এর প্রপার্টির সব booking
$bstmt = $conn->prepare("SELECT b.booking_id, b.customer_id, b.booking_date, b.status, b.created_at, p.title
                         FROM bookings b
                         JOIN properties p ON p.property_id = b.property_id
                         WHERE p.owner_id = ?
                         ORDER BY b.created_at DESC");
$bstmt->bind_param("s", $owner_id);
$bstmt->execute();
$bookings = $bstmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalBookings = count($bookings);
$pendingBookings = count(array_filter($bookings, fn($b) => ($b['status'] ?? '') === 'pending'));
$approvedBookings = count(array_filter($bookings, fn($b) => ($b['status'] ?? '') === 'approved'));
$bookingStatusClass = ['pending' => 'status-pending', 'approved' => 'status-rented', 'cancelled' => 'status-review'];

// Payments (payments টেবিল না থাকলে পেজ ভাঙবে না, খালি দেখাবে)
$payments = [];
$paymentsReady = true;
try {
  $pstmt = $conn->prepare("SELECT pay.payment_id, pay.amount, pay.due_date, pay.paid_date, pay.status, b.customer_id, p.title
                           FROM payments pay
                           JOIN bookings b ON b.booking_id = pay.booking_id
                           JOIN properties p ON p.property_id = b.property_id
                           WHERE p.owner_id = ?
                           ORDER BY pay.due_date DESC");
  $pstmt->bind_param("s", $owner_id);
  $pstmt->execute();
  $payments = $pstmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
  $paymentsReady = false;
}

$receivedThisMonth = 0.0;
$pendingTotal = 0.0;
$pendingCount = 0;
foreach ($payments as $pay) {
  if (($pay['status'] ?? '') === 'received') {
    $when = $pay['paid_date'] ?: $pay['due_date'];
    if ($when && date('Y-m', strtotime($when)) === date('Y-m')) {
      $receivedThisMonth += (float)$pay['amount'];
    }
  } else {
    $pendingTotal += (float)$pay['amount'];
    $pendingCount++;
  }
}

// Notifications: সর্বশেষ ৫টা booking-এর অবস্থা থেকে তৈরি
$notifications = [];
foreach (array_slice($bookings, 0, 5) as $b) {
  $st = $b['status'] ?? 'pending';
  $who = 'Customer #' . (int)$b['customer_id'];
  if ($st === 'approved') {
    $notifications[] = ['icon' => 'fa-solid fa-circle-check', 'cls' => 'activity-green', 'title' => 'Booking approved', 'text' => "You approved $who for " . $b['title'] . '.', 'time' => $b['created_at']];
  } elseif ($st === 'cancelled') {
    $notifications[] = ['icon' => 'fa-solid fa-ban', 'cls' => 'activity-amber', 'title' => 'Booking cancelled', 'text' => "Booking by $who for " . $b['title'] . ' was cancelled.', 'time' => $b['created_at']];
  } else {
    $notifications[] = ['icon' => 'fa-regular fa-calendar-check', 'cls' => 'activity-blue', 'title' => 'New booking request', 'text' => "$who requested " . $b['title'] . '.', 'time' => $b['created_at']];
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#fff2f2">
  <meta name="description" content="Manage your properties, bookings, payments and maintenance in the EstateFlow owner workspace.">
  <title>Owner Dashboard | EstateFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/owner/dashboard.css">
  <link rel="stylesheet" href="../shared/assets/css/palette.css">
</head>

<body class="owner-dashboard">
  <a class="owner-skip-link" href="#owner-main">Skip to dashboard</a>
  <div class="owner-layout">
      <?php include __DIR__ . '/include/owner_leftber.php'; ?>
    <div class="owner-main-shell">
      <?php include __DIR__ . '/../shared/partials/header.php'; ?>

      <main class="owner-content" id="owner-main">
        <section class="owner-welcome" id="overview">
          <div>
            <p class="owner-eyebrow"><span></span> OWNER WORKSPACE</p>
            <h1><span id="owner-greeting">Welcome</span>, <?= $escape($ownerName) ?> <span aria-hidden="true">✦</span></h1>
            <p class="owner-subtitle">A clear view of your properties, payments, and what needs attention.</p>
          </div>
          <div class="owner-heading-actions">
            <a href="./property_crud/Addproperty.php" class="btn owner-primary-button"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add property</a>
          </div>
        </section>

                <section class="row g-3 owner-stat-grid" aria-label="Property portfolio summary">
          <div class="col-12 col-sm-6 col-xl-3"><article class="owner-stat-card"><div class="owner-stat-head"><span>Owned properties</span><span class="owner-stat-icon icon-blue"><i class="fa-solid fa-building" aria-hidden="true"></i></span></div><strong class="owner-stat-value"><?= str_pad((string)$totalProps, 2, '0', STR_PAD_LEFT) ?></strong><div class="owner-stat-foot"><span class="stat-positive"><?= $availableProps ?> available</span><span>in your portfolio</span></div></article></div>
          <div class="col-12 col-sm-6 col-xl-3"><article class="owner-stat-card"><div class="owner-stat-head"><span>Payments received</span><span class="owner-stat-icon icon-green"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span></div><strong class="owner-stat-value">৳ <?= number_format($receivedThisMonth) ?></strong><div class="owner-stat-foot"><span class="stat-positive">This month</span><span>৳ <?= number_format($pendingTotal) ?> pending</span></div></article></div>
          <div class="col-12 col-sm-6 col-xl-3"><article class="owner-stat-card"><div class="owner-stat-head"><span>Active bookings</span><span class="owner-stat-icon icon-purple"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span></div><strong class="owner-stat-value"><?= str_pad((string)$approvedBookings, 2, '0', STR_PAD_LEFT) ?></strong><div class="owner-stat-foot"><span class="stat-neutral"><?= $pendingBookings ?> need review</span><span>approved bookings</span></div></article></div>
          <div class="col-12 col-sm-6 col-xl-3"><article class="owner-stat-card"><div class="owner-stat-head"><span>Open maintenance</span><span class="owner-stat-icon icon-amber"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i></span></div><strong class="owner-stat-value">00</strong><div class="owner-stat-foot"><span class="stat-neutral">No open requests</span><span>maintenance</span></div></article></div>
        </section>

        <section class="owner-section-heading" id="properties">
          <div><h2>My Properties</h2><p>Your owned properties and current listing status.</p></div>
          <a href="property_crud/myproperty.php">View all properties <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </section>

        <section class="row g-3 owner-property-grid" aria-label="Owned properties">
          <?php if ($totalProps === 0): ?>
            <div class="col-12"><p class="text-secondary">You haven't added any properties yet.</p></div>
          <?php endif; ?>

          <?php foreach (array_slice($properties, 0, 3) as $i => $p):
            $isRent = !empty($p['monthly_rent']) && $p['monthly_rent'] > 0;
            $status = $p['status'] ?? '';
            $img = !empty($p['imagas'])
              ? 'property_crud/' . implode('/', array_map('rawurlencode', explode('/', $p['imagas'])))
              : '';
          ?>
          <div class="col-12 col-md-6 col-xl-4">
            <article class="owner-property-card">
              <div class="property-image <?= $imgClasses[$i % 3] ?>" role="img" aria-label="<?= $escape((string)($p['title'] ?? '')) ?>"
                   <?php if ($img): ?>style="background-image:url('<?= $escape($img) ?>'); background-size:cover; background-position:center;"<?php endif; ?>>
                <span class="property-type-pill"><?= $escape(ucfirst((string)($p['property_type'] ?? ''))) ?></span>
                <span class="property-listing-status <?= $status === 'available' ? 'status-available' : '' ?>"><span></span> <?= $escape(ucfirst($status)) ?></span>
              </div>
              <div class="property-card-body">
                <div class="property-card-heading">
                  <div>
                    <h3><?= $escape((string)($p['title'] ?? '')) ?></h3>
                    <p><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= $escape((string)($p['address'] ?? '')) ?></p>
                  </div>
                </div>
                <div class="property-specs">
                  <span><i class="fa-solid fa-bed" aria-hidden="true"></i> <?= $escape((string)($p['flat_details'] ?? '—')) ?> beds</span>
                  <span><i class="fa-solid fa-vector-square" aria-hidden="true"></i> <?= number_format((float)($p['area_sqft'] ?? 0)) ?> sqft</span>
                </div>
                <div class="property-card-footer">
                  <strong>৳ <?= number_format((float)($isRent ? $p['monthly_rent'] : ($p['price'] ?? 0))) ?><?php if ($isRent): ?> <small>/ month</small><?php endif; ?></strong>
                  <a href="property_crud/myproperty.php">Details</a>
                </div>
              </div>
            </article>
          </div>
          <?php endforeach; ?>
        </section>

        <section class="owner-module-section" id="bookings" aria-labelledby="bookings-title">
          <div class="owner-section-heading"><div><h2 id="bookings-title">Bookings</h2><p>Review booking requests for your properties.</p></div><span class="owner-section-count"><?= $totalBookings ?> requests</span></div>
          <article class="owner-panel owner-table-panel"><div class="owner-table-scroll">
            <?php if ($totalBookings === 0): ?>
              <p class="text-secondary p-3 mb-0">No booking requests yet.</p>
            <?php else: ?>
            <table class="owner-data-table"><thead><tr><th>Customer</th><th>Property</th><th>Requested</th><th>Status</th><th>Action</th></tr></thead><tbody>
              <?php foreach ($bookings as $b):
                $bStatus = $b['status'] ?? 'pending';
              ?>
              <tr>
                <td><strong>Customer #<?= (int)$b['customer_id'] ?></strong></td>
                <td><?= $escape((string)$b['title']) ?></td>
                <td><?= $escape(date('M d, Y', strtotime((string)$b['booking_date']))) ?></td>
                <td><span class="owner-status <?= $bookingStatusClass[$bStatus] ?? '' ?>"><?= $escape(ucfirst($bStatus)) ?></span></td>
                <td>
                  <?php if ($bStatus === 'pending'): ?>
                  <form method="post" class="d-inline-flex gap-1">
                    <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                    <button class="owner-inline-action" type="submit" name="new_status" value="approved">Approve</button>
                    <button class="owner-inline-action" type="submit" name="new_status" value="cancelled">Cancel</button>
                  </form>
                  <?php else: ?>
                    <span class="text-secondary">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
          </div></article>
        </section>

        <section class="owner-module-section" id="payments" aria-labelledby="payments-title">
          <div class="owner-section-heading"><div><h2 id="payments-title">Payments</h2><p>See received payments and pending balances.</p></div></div>
          <div class="owner-finance-summary"><article class="owner-panel"><small>Received this month</small><strong>৳ <?= number_format($receivedThisMonth) ?></strong><span><?= count($payments) - $pendingCount ?> payments received</span></article><article class="owner-panel"><small>Pending payments</small><strong>৳ <?= number_format($pendingTotal) ?></strong><span><?= $pendingCount ?> payments due</span></article></div>
          <article class="owner-panel owner-table-panel"><div class="owner-table-scroll">
            <?php if (!$paymentsReady || count($payments) === 0): ?>
              <p class="text-secondary p-3 mb-0">No payments recorded yet.</p>
            <?php else: ?>
            <table class="owner-data-table"><thead><tr><th>Payment</th><th>Property</th><th>Date due</th><th>Amount</th><th>Status</th></tr></thead><tbody>
              <?php foreach ($payments as $pay): $received = ($pay['status'] ?? '') === 'received'; ?>
              <tr>
                <td><strong>Rent</strong><small>Customer #<?= (int)$pay['customer_id'] ?></small></td>
                <td><?= $escape((string)$pay['title']) ?></td>
                <td><?= $escape(date('M d', strtotime((string)$pay['due_date']))) ?></td>
                <td>৳ <?= number_format((float)$pay['amount']) ?></td>
                <td><span class="owner-status <?= $received ? 'status-rented' : 'status-pending' ?>"><?= $received ? 'Received' : 'Pending' ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody></table>
            <?php endif; ?>
          </div></article>
        </section>

        <section class="owner-module-section" id="notifications" aria-labelledby="notifications-title">
          <div class="owner-section-heading"><div><h2 id="notifications-title">Notifications</h2><p>Booking and payment updates for your properties.</p></div></div>
          <article class="owner-panel owner-notification-list">
            <?php if (count($notifications) === 0): ?>
              <p class="text-secondary p-3 mb-0">No notifications yet.</p>
            <?php endif; ?>
            <?php foreach ($notifications as $n): ?>
            <div class="owner-notification-item"><span class="activity-icon <?= $n['cls'] ?>"><i class="<?= $n['icon'] ?>" aria-hidden="true"></i></span><div><strong><?= $escape($n['title']) ?></strong><p><?= $escape($n['text']) ?></p></div><small><?= $escape(date('M d · g:i A', strtotime((string)$n['time']))) ?></small></div>
            <?php endforeach; ?>
          </article>
        </section>

        <section class="owner-module-section" id="profile" aria-labelledby="profile-title">
          <div class="owner-section-heading"><div><h2 id="profile-title">Profile</h2><p>Manage your personal information.</p></div></div>
          <form class="owner-panel owner-profile-form" id="owner-profile-form">
            <label class="owner-form-field">Full name<input name="name" type="text" value="<?= $escape($ownerName) ?>" required></label>
            <label class="owner-form-field">Email address<input name="email" type="email" value="<?= $escape($ownerEmail) ?>" required></label>
            <label class="owner-form-field">Phone number<input name="phone" type="tel" placeholder="Add a phone number"></label>
            <div class="owner-form-actions owner-form-wide"><button class="btn owner-primary-button" type="submit"><i class="fa-regular fa-floppy-disk" aria-hidden="true"></i> Save profile</button><span class="owner-form-feedback" id="profile-feedback" role="status" aria-live="polite" hidden></span></div>
          </form>
        </section>

        <p class="owner-action-feedback" id="owner-action-feedback" role="status" aria-live="polite" hidden></p>
        <footer class="owner-footer"><span>EstateFlow <span aria-hidden="true">·</span> Owner workspace</span><span>Owner dashboard</span></footer>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      var d = document.getElementById('owner-date');
      if (d) d.textContent = new Date().toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
      var g = document.getElementById('owner-greeting');
      if (g) { var h = new Date().getHours(); g.textContent = h < 12 ? 'Good morning' : (h < 18 ? 'Good afternoon' : 'Good evening'); }
    })();
  </script>

</body>

</html>