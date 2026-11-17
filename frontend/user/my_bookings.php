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
  if (file_exists($file)) {
    include_once $file;
    break;
  }
}
if (!isset($conn)) {
  die('dbconfig.php khuje pawa jayni. $dbCandidates er path gulo check koro.');
}

$userId = (int)($_SESSION['user_id'] ?? 0);

// ---------- CSRF + actions (edit / delete) ----------
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    die('Invalid request.');
  }
  $action = $_POST['action'] ?? '';
  $id     = (int)($_POST['id'] ?? 0);
  $msg    = 'error';

  if ($action === 'edit_booking') {
    $dt = strtotime($_POST['booking_date'] ?? '');
    if ($dt && $dt >= strtotime('today')) {
      $date = date('Y-m-d H:i:s', $dt);
      $s = $conn->prepare("UPDATE bookings SET booking_date = ?
                           WHERE booking_id = ? AND customer_id = ? AND status = 'pending'");
      $s->bind_param('sii', $date, $id, $userId);
      $s->execute();
      $msg = $s->affected_rows > 0 ? 'updated' : 'nochange';
    }
  } elseif ($action === 'delete_booking') {
    $conn->begin_transaction();
    $s = $conn->prepare("SELECT booking_id FROM bookings
                         WHERE booking_id = ? AND customer_id = ? AND status IN ('pending','cancelled')");
    $s->bind_param('ii', $id, $userId);
    $s->execute();
    if ($s->get_result()->num_rows === 1) {
      $d1 = $conn->prepare("DELETE FROM visits WHERE booking_id = ? AND customer_id = ?");
      $d1->bind_param('ii', $id, $userId);
      $d1->execute();
      $d2 = $conn->prepare("DELETE FROM bookings WHERE booking_id = ? AND customer_id = ?");
      $d2->bind_param('ii', $id, $userId);
      $d2->execute();
      $conn->commit();
      $msg = 'deleted';
    } else {
      $conn->rollback();
    }
  } elseif ($action === 'edit_visit') {
    $dt = strtotime($_POST['visit_time'] ?? '');
    if ($dt && $dt > time()) {
      $t = date('Y-m-d H:i:s', $dt);
      $s = $conn->prepare("UPDATE visits SET visit_time = ?
                           WHERE visit_id = ? AND customer_id = ? AND status = 'scheduled'");
      $s->bind_param('sii', $t, $id, $userId);
      $s->execute();
      $msg = $s->affected_rows > 0 ? 'updated' : 'nochange';
    }
  } elseif ($action === 'delete_visit') {
    $s = $conn->prepare("DELETE FROM visits
                         WHERE visit_id = ? AND customer_id = ? AND status = 'scheduled'");
    $s->bind_param('ii', $id, $userId);
    $s->execute();
    $msg = $s->affected_rows > 0 ? 'deleted' : 'error';
  }

  header("Location: my_bookings.php?msg=" . $msg);
  exit;
}

$flashMap = [
  'updated'  => ['ok',  'Updated successfully.'],
  'deleted'  => ['ok',  'Deleted successfully.'],
  'nochange' => ['err', 'Nothing was changed (only pending/scheduled items can be edited).'],
  'error'    => ['err', 'Something went wrong. Please check the date and try again.'],
];
$flash = $flashMap[$_GET['msg'] ?? ''] ?? null;

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

function statusLabel($s)
{
  return $s === 'approved' ? 'Confirmed' : ucfirst($s);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#fff2f2">
  <title>My bookings | EstateFlow</title>
  <link rel="stylesheet" href="partials/style.css">
  <style>
    .my-section {
      padding: 40px 0 20px;
    }

    .my-section .section-head {
      margin-bottom: 20px;
    }

    .my-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 24px;
      margin-bottom: 50px;
    }

    .my-card {
      border: 1px solid #e3e3e3;
      border-radius: 14px;
      padding: 20px;
      background: #fff;
    }

    .my-card h3 {
      margin: 10px 0 4px;
    }

    .badge {
      display: inline-block;
      font-size: 12px;
      padding: 3px 10px;
      border-radius: 20px;
      background: #e8f1f1;
      margin-right: 6px;
    }

    .badge-pending,
    .badge-scheduled {
      background: #fff4d6;
    }

    .badge-approved,
    .badge-completed {
      background: #dcf5e3;
    }

    .badge-cancelled {
      background: #fde1e1;
    }

    .my-meta {
      color: #555;
      font-size: 14px;
      margin: 8px 0 14px;
    }

    .my-empty {
      color: #555;
      margin-bottom: 40px;
    }

    /* edit / delete */
    .my-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 12px;
      align-items: flex-start;
    }

    .btn-sm {
      font-size: 13px;
      padding: 6px 14px;
      border-radius: 8px;
      border: 1px solid #ccc;
      background: #fff;
      cursor: pointer;
    }

    .btn-danger {
      color: #b42318;
      border-color: #f1b5b0;
    }

    .btn-danger:hover {
      background: #fde1e1;
    }

    .edit-box summary {
      list-style: none;
      display: inline-block;
    }

    .edit-box summary::-webkit-details-marker {
      display: none;
    }

    .edit-box form {
      margin-top: 10px;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .edit-box input[type=datetime-local] {
      padding: 6px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    .flash {
      padding: 10px 14px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-size: 14px;
    }

    .flash.ok {
      background: #dcf5e3;
    }

    .flash.err {
      background: #fde1e1;
    }
  </style>
</head>

<body>

  <!--start header-->
  <?php include __DIR__ . '/partials/customar_header.php'; ?>
  <!--end header-->
  <style>
    .customer-subnav {
      background: var(--green-deep);
    }

    .customer-subnav .shell {
      display: flex;
      gap: 22px;
      padding: 12px 0;
      flex-wrap: wrap;
    }

    .customer-subnav a {
      color: #f5f4e9;
      text-decoration: none;
      font-size: 15px;
      font-weight: 500;
      padding-bottom: 4px;
      border-bottom: 2px solid transparent;
    }

    .customer-subnav a:hover,
    .customer-subnav a.active {
      color: #fff2f2;
      border-bottom-color: var(--green);
    }
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

        <?php if ($flash): ?>
          <div class="flash <?= $flash[0] ?>"><?= htmlspecialchars($flash[1]) ?></div>
        <?php endif; ?>

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

                <div class="my-actions">
                  <?php if ($b['status'] === 'pending'): ?>
                    <details class="edit-box">
                      <summary class="btn-sm">Edit</summary>
                      <form method="post">
                        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                        <input type="hidden" name="action" value="edit_booking">
                        <input type="hidden" name="id" value="<?= (int)$b['booking_id'] ?>">
                        <input type="datetime-local" name="booking_date" required
                          value="<?= date('Y-m-d\TH:i', strtotime($b['booking_date'])) ?>">
                        <button class="btn-sm" type="submit">Save</button>
                      </form>
                    </details>
                  <?php endif; ?>
                  <?php if (in_array($b['status'], ['pending', 'cancelled'], true)): ?>
                    <form method="post" onsubmit="return confirm('Delete this booking? Its scheduled visits will be removed too.');">
                      <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                      <input type="hidden" name="action" value="delete_booking">
                      <input type="hidden" name="id" value="<?= (int)$b['booking_id'] ?>">
                      <button class="btn-sm btn-danger" type="submit">Delete</button>
                    </form>
                  <?php endif; ?>
                </div>
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

                <?php if ($v['status'] === 'scheduled'): ?>
                  <div class="my-actions">
                    <details class="edit-box">
                      <summary class="btn-sm">Reschedule</summary>
                      <form method="post">
                        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                        <input type="hidden" name="action" value="edit_visit">
                        <input type="hidden" name="id" value="<?= (int)$v['visit_id'] ?>">
                        <input type="datetime-local" name="visit_time" required
                          value="<?= date('Y-m-d\TH:i', strtotime($v['visit_time'])) ?>">
                        <button class="btn-sm" type="submit">Save</button>
                      </form>
                    </details>
                    <form method="post" onsubmit="return confirm('Delete this visit?');">
                      <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                      <input type="hidden" name="action" value="delete_visit">
                      <input type="hidden" name="id" value="<?= (int)$v['visit_id'] ?>">
                      <button class="btn-sm btn-danger" type="submit">Delete</button>
                    </form>
                  </div>
                <?php endif; ?>
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