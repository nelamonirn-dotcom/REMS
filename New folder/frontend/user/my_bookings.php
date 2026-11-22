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
$customerId = 0;
if ($userId > 0) {
  $customerStmt = $conn->prepare('SELECT customer_id FROM customers WHERE user_id = ? LIMIT 1');
  $customerStmt->bind_param('i', $userId);
  $customerStmt->execute();
  $customer = $customerStmt->get_result()->fetch_assoc();
  $customerId = (int)($customer['customer_id'] ?? 0);
}

// ---------- CSRF + actions (edit / delete) ----------
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) {
    die('Invalid request.');
  }
  $action = $_POST['action'] ?? '';
  $idInput = $_POST['id'] ?? 0;
  $id     = is_scalar($idInput) ? (int)$idInput : 0;
  $msg    = 'error';

  if ($action === 'edit_booking') {
    $dt = strtotime($_POST['booking_date'] ?? '');
    if ($dt && $dt >= strtotime('today')) {
      $date = date('Y-m-d H:i:s', $dt);
      $s = $conn->prepare("UPDATE bookings SET booking_date = ?
                           WHERE booking_id = ? AND customer_id = ? AND status = 'pending'");
      $s->bind_param('sii', $date, $id, $customerId);
      $s->execute();
      $msg = $s->affected_rows > 0 ? 'updated' : 'nochange';
    }
  } elseif ($action === 'delete_booking') {
    $conn->begin_transaction();
    $s = $conn->prepare("SELECT booking_id FROM bookings
                         WHERE booking_id = ? AND customer_id = ? AND status IN ('pending','cancelled')");
    $s->bind_param('ii', $id, $customerId);
    $s->execute();
    if ($s->get_result()->num_rows === 1) {
      $d1 = $conn->prepare("DELETE FROM visits WHERE booking_id = ? AND customer_id = ?");
      $d1->bind_param('ii', $id, $customerId);
      $d1->execute();
      $d2 = $conn->prepare("DELETE FROM bookings WHERE booking_id = ? AND customer_id = ?");
      $d2->bind_param('ii', $id, $customerId);
      $d2->execute();
      $conn->commit();
      $msg = 'deleted';
    } else {
      $conn->rollback();
    }
  } elseif ($action === 'schedule_visit') {
    $visitInput = $_POST['visit_time'] ?? '';
    $visitDate = is_string($visitInput)
      ? DateTime::createFromFormat('Y-m-d\TH:i', $visitInput)
      : false;
    $dateErrors = DateTime::getLastErrors();

    if (
      !$visitDate
      || (is_array($dateErrors) && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
      || $visitDate->getTimestamp() <= time()
      || $customerId < 1
    ) {
      $msg = 'error';
    } else {
      $visitTime = $visitDate->format('Y-m-d H:i:s');
      $conn->begin_transaction();

      $bookingStmt = $conn->prepare("SELECT property_id
                                     FROM bookings
                                     WHERE booking_id = ? AND customer_id = ? AND status = 'approved'
                                     FOR UPDATE");
      $bookingStmt->bind_param('ii', $id, $customerId);
      $bookingStmt->execute();
      $approvedBooking = $bookingStmt->get_result()->fetch_assoc();

      if (!$approvedBooking) {
        $conn->rollback();
        $msg = 'not_approved';
      } else {
        $existingVisitStmt = $conn->prepare("SELECT visit_id FROM visits
                                             WHERE booking_id = ? AND status = 'scheduled'
                                             LIMIT 1");
        $existingVisitStmt->bind_param('i', $id);
        $existingVisitStmt->execute();

        if ($existingVisitStmt->get_result()->num_rows > 0) {
          $conn->rollback();
          $msg = 'exists';
        } else {
          $propertyId = (int)$approvedBooking['property_id'];
          $agentStmt = $conn->prepare("SELECT a.agent_id
                                       FROM agent_properties ap
                                       JOIN agents a ON a.agent_id = ap.agent_id AND a.status = 'active'
                                       WHERE ap.property_id = ?
                                       ORDER BY ap.assigned_at, ap.agent_id
                                       LIMIT 1");
          $agentStmt->bind_param('i', $propertyId);
          $agentStmt->execute();
          $assignedAgent = $agentStmt->get_result()->fetch_assoc();

          if ($assignedAgent) {
            $agentId = (int)$assignedAgent['agent_id'];
            $visitStmt = $conn->prepare("INSERT INTO visits
                                           (booking_id, property_id, customer_id, agent_id, visit_time)
                                         VALUES (?, ?, ?, ?, ?)");
            $visitStmt->bind_param('iiiis', $id, $propertyId, $customerId, $agentId, $visitTime);
          } else {
            $visitStmt = $conn->prepare("INSERT INTO visits
                                           (booking_id, property_id, customer_id, agent_id, visit_time)
                                         VALUES (?, ?, ?, NULL, ?)");
            $visitStmt->bind_param('iiis', $id, $propertyId, $customerId, $visitTime);
          }
          $visitStmt->execute();
          $conn->commit();
          $msg = 'scheduled';
        }
      }
    }
  } elseif ($action === 'edit_visit') {
    $dt = strtotime($_POST['visit_time'] ?? '');
    if ($dt && $dt > time()) {
      $t = date('Y-m-d H:i:s', $dt);
      $s = $conn->prepare("UPDATE visits SET visit_time = ?
                           WHERE visit_id = ? AND customer_id = ? AND status = 'scheduled'");
      $s->bind_param('sii', $t, $id, $customerId);
      $s->execute();
      $msg = $s->affected_rows > 0 ? 'updated' : 'nochange';
    }
  } elseif ($action === 'delete_visit') {
    $s = $conn->prepare("DELETE FROM visits
                         WHERE visit_id = ? AND customer_id = ? AND status = 'scheduled'");
    $s->bind_param('ii', $id, $customerId);
    $s->execute();
    $msg = $s->affected_rows > 0 ? 'deleted' : 'error';
  } elseif ($action === 'confirm_visit') {
    $s = $conn->prepare("UPDATE visits v
                         JOIN bookings b ON b.booking_id = v.booking_id
                         SET v.status = 'completed'
                         WHERE v.visit_id = ? AND v.customer_id = ?
                           AND v.status = 'scheduled' AND v.visit_time <= NOW()
                           AND b.status = 'approved'");
    $s->bind_param('ii', $id, $customerId);
    $s->execute();
    $msg = $s->affected_rows > 0 ? 'visit_completed' : 'visit_not_due';
  } elseif ($action === 'request_interest') {
    $intent = $_POST['intent'] ?? '';
    if ($id > 0 && $customerId > 0 && in_array($intent, ['buy', 'rent'], true)) {
      $conn->begin_transaction();
      $visitBookingStmt = $conn->prepare("SELECT b.booking_id, b.property_id, b.customer_id,
                                                 p.price, p.monthly_rent
                                          FROM bookings b
                                          JOIN properties p ON p.property_id = b.property_id
                                          JOIN visits v ON v.booking_id = b.booking_id
                                          WHERE b.booking_id = ? AND b.customer_id = ?
                                            AND b.status = 'approved' AND p.status = 'available'
                                            AND v.status = 'completed'
                                          LIMIT 1
                                          FOR UPDATE");
      $visitBookingStmt->bind_param('ii', $id, $customerId);
      $visitBookingStmt->execute();
      $completedBooking = $visitBookingStmt->get_result()->fetch_assoc();

      if (!$completedBooking) {
        $conn->rollback();
        $msg = 'visit_required';
      } else {
        $interestStmt = $conn->prepare("SELECT status FROM booking_interests
                                        WHERE booking_id = ?
                                        FOR UPDATE");
        $interestStmt->bind_param('i', $id);
        $interestStmt->execute();
        $existingInterest = $interestStmt->get_result()->fetch_assoc();

        if (($existingInterest['status'] ?? '') === 'invoiced') {
          $conn->rollback();
          $msg = 'interest_invoiced';
        } else {
          $amountInput = $intent === 'buy' ? $completedBooking['price'] : $completedBooking['monthly_rent'];
          $amount = is_numeric($amountInput) ? (float)$amountInput : 0;
          $agreementType = $intent === 'buy' ? 'sale' : 'rent';
          $paymentType = $agreementType;

          // Sandbox gateway limit is 500,000 per transaction. A bigger sale price is billed
          // as a 10% advance (max 500,000); the agreement keeps the full price.
          $onlineLimit = 500000;
          $payAmount = $amount;
          $payType = $paymentType;
          $isAdvance = false;
          if ($intent === 'buy' && $amount > $onlineLimit) {
            $payAmount = min(round($amount * 0.10, 2), $onlineLimit);
            $payType = 'advance';
            $isAdvance = true;
          }

          if ($amount < 10) {
            $conn->rollback();
            $msg = 'amount_unavailable';
          } elseif ($amount > $onlineLimit && !$isAdvance) {
            if ($existingInterest) {
              $updateInterest = $conn->prepare("UPDATE booking_interests
                                                SET interest_type = ?, status = 'pending', created_at = NOW()
                                                WHERE booking_id = ?");
              $updateInterest->bind_param('si', $intent, $id);
              $updateInterest->execute();
            } else {
              $insertInterest = $conn->prepare("INSERT INTO booking_interests (booking_id, interest_type)
                                                VALUES (?, ?)");
              $insertInterest->bind_param('is', $id, $intent);
              $insertInterest->execute();
            }
            $conn->commit();
            $msg = 'invoice_requested';
          } else {
            $amountValue = number_format($amount, 2, '.', '');
            $payAmountValue = number_format($payAmount, 2, '.', '');
            $agreementStmt = $conn->prepare("SELECT agreement_id, agreement_type
                                             FROM agreements
                                             WHERE booking_id = ? AND status = 'active'
                                             LIMIT 1
                                             FOR UPDATE");
            $agreementStmt->bind_param('i', $id);
            $agreementStmt->execute();
            $agreement = $agreementStmt->get_result()->fetch_assoc();

            if ($agreement) {
              if ($agreement['agreement_type'] !== $agreementType) {
                $conn->rollback();
                $msg = 'agreement_conflict';
              } else {
                if ($existingInterest) {
                  $updateInterest = $conn->prepare("UPDATE booking_interests
                                                    SET interest_type = ?, status = 'pending', created_at = NOW()
                                                    WHERE booking_id = ?");
                  $updateInterest->bind_param('si', $intent, $id);
                  $updateInterest->execute();
                } else {
                  $insertInterest = $conn->prepare("INSERT INTO booking_interests (booking_id, interest_type)
                                                    VALUES (?, ?)");
                  $insertInterest->bind_param('is', $id, $intent);
                  $insertInterest->execute();
                }
                $conn->commit();
                $msg = 'invoice_requested';
              }
            } else {
              $agreementStart = date('Y-m-d');
              $createAgreement = $conn->prepare("INSERT INTO agreements
                                                  (booking_id, property_id, customer_id, agreement_type, start_date, amount, status)
                                                VALUES (?, ?, ?, ?, ?, ?, 'active')");
              $createAgreement->bind_param(
                'iiisss',
                $completedBooking['booking_id'],
                $completedBooking['property_id'],
                $completedBooking['customer_id'],
                $agreementType,
                $agreementStart,
                $amountValue
              );
              $createAgreement->execute();
              $agreementId = (int)$conn->insert_id;

              $dueDate = date('Y-m-d');
              $createPayment = $conn->prepare("INSERT INTO payments
                                                (agreement_id, amount, due_date, payment_type, status)
                                               VALUES (?, ?, ?, ?, 'pending')");
              $createPayment->bind_param('idss', $agreementId, $payAmountValue, $dueDate, $payType);
              $createPayment->execute();

              if ($existingInterest) {
                $updateInterest = $conn->prepare("UPDATE booking_interests
                                                  SET interest_type = ?, status = 'invoiced', created_at = NOW()
                                                  WHERE booking_id = ?");
                $updateInterest->bind_param('si', $intent, $id);
                $updateInterest->execute();
              } else {
                $insertInterest = $conn->prepare("INSERT INTO booking_interests
                                                  (booking_id, interest_type, status)
                                                  VALUES (?, ?, 'invoiced')");
                $insertInterest->bind_param('is', $id, $intent);
                $insertInterest->execute();
              }
              $conn->commit();
              $msg = $isAdvance ? 'advance_created' : 'invoice_created';
            }
          }
        }
      }
    }
  }

  header("Location: my_bookings.php?msg=" . $msg);
  exit;
}

$flashMap = [
  'updated'  => ['ok',  'Updated successfully.'],
  'deleted'  => ['ok',  'Deleted successfully.'],
  'scheduled' => ['ok', 'Property visit scheduled successfully.'],
  'visit_completed' => ['ok', 'Visit marked as visited. You can now choose to buy or rent.'],
  'visit_not_due' => ['err', 'This visit can be marked completed after its scheduled time.'],
  'exists' => ['err', 'A visit is already scheduled for this booking.'],
  'not_approved' => ['err', 'A property visit is available after your booking is approved.'],
  'visit_required' => ['err', 'Complete a property visit before requesting to buy or rent.'],
  'invoice_created' => ['ok', 'Invoice created from the listed price. Continue to My payments to pay.'],
  'advance_created' => ['ok', 'The sale price is above the online payment limit, so a 10% advance invoice (max ৳500,000) was created. Continue to My payments to pay with bKash or card.'],
  'invoice_requested' => ['ok', 'Your request is with the admin. They will issue the correct payment invoice.'],
  'amount_unavailable' => ['err', 'This property has no valid listed price for that payment option.'],
  'agreement_conflict' => ['err', 'An active agreement for this booking has a different type. Contact the admin before paying.'],
  'interest_invoiced' => ['err', 'An invoice has already been created for this visit. Open My payments to continue.'],
  'nochange' => ['err', 'Nothing was changed (only pending/scheduled items can be edited).'],
  'error'    => ['err', 'Something went wrong. Please check the date and try again.'],
];
$flashCode = is_string($_GET['msg'] ?? null) ? $_GET['msg'] : '';
$flash = $flashMap[$flashCode] ?? null;

// ---------- Amar bookings ----------
$stmt = $conn->prepare("SELECT b.booking_id, b.property_id, b.booking_date, b.status,
                               p.title, p.address, p.property_type, p.price, p.monthly_rent,
                               (SELECT v.visit_time
                                FROM visits v
                                WHERE v.booking_id = b.booking_id AND v.status = 'scheduled'
                                ORDER BY v.visit_time
                                LIMIT 1) AS scheduled_visit_time,
                               EXISTS(SELECT 1 FROM visits vc
                                      WHERE vc.booking_id = b.booking_id AND vc.status = 'completed') AS visit_completed
                        FROM bookings b
                        LEFT JOIN properties p ON p.property_id = b.property_id
                        WHERE b.customer_id = ?
                        ORDER BY b.booking_date DESC");
$stmt->bind_param('i', $customerId);
$stmt->execute();
$bookings = $stmt->get_result();
$total    = $bookings->num_rows;

// ---------- Amar visits ----------
$stmt2 = $conn->prepare("SELECT v.visit_id, v.booking_id, v.property_id, v.visit_time, v.status,
                                b.status AS booking_status, p.title, p.address, p.status AS property_status,
                                p.price, p.monthly_rent,
                                bi.interest_type, bi.status AS interest_status,
                                (SELECT pay.status
                                 FROM agreements ag
                                 JOIN payments pay ON pay.agreement_id = ag.agreement_id
                                 WHERE ag.booking_id = b.booking_id
                                 ORDER BY pay.payment_id DESC
                                 LIMIT 1) AS payment_status
                         FROM visits v
                         JOIN bookings b ON b.booking_id = v.booking_id
                         LEFT JOIN properties p ON p.property_id = v.property_id
                         LEFT JOIN booking_interests bi ON bi.booking_id = b.booking_id
                         WHERE v.customer_id = ?
                         ORDER BY v.visit_time DESC");
$stmt2->bind_param('i', $customerId);
$stmt2->execute();
$visits = $stmt2->get_result();

function statusLabel($s)
{
  return $s === 'approved' ? 'Confirmed' : ucfirst($s);
}
function visitStatusLabel($s)
{
  return $s === 'completed' ? 'Visited' : ucfirst($s);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#e8f5e9">
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

    .visit-form {
      display: flex;
      align-items: end;
      flex-wrap: wrap;
      gap: 8px;
      margin: 14px 0;
    }

    .visit-form label {
      width: 100%;
      font-weight: 600;
    }

    .visit-form input {
      min-height: 36px;
      padding: 6px 8px;
      border: 1px solid #a5d6a7;
      border-radius: 8px;
    }

    .visit-form button {
      width: auto;
      margin: 0;
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
      color: #e8f5e9;
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
                <?php if (!empty($b['visit_completed'])): ?>
                  <span class="badge badge-completed">Visited</span>
                <?php endif; ?>
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

                <?php if ($b['status'] === 'approved' && !empty($b['scheduled_visit_time'])): ?>
                  <p class="my-meta">
                    Property visit scheduled:
                    <?= htmlspecialchars(date('M d, Y · h:i A', strtotime($b['scheduled_visit_time']))) ?>
                  </p>
                <?php elseif ($b['status'] === 'approved'): ?>
                  <form class="visit-form" method="post">
                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                    <input type="hidden" name="action" value="schedule_visit">
                    <input type="hidden" name="id" value="<?= (int)$b['booking_id'] ?>">
                    <label for="visit-time-<?= (int)$b['booking_id'] ?>">Schedule a property visit</label>
                    <input id="visit-time-<?= (int)$b['booking_id'] ?>" type="datetime-local"
                           name="visit_time" min="<?= date('Y-m-d\TH:i') ?>" required>
                    <button class="btn-sm" type="submit">Schedule visit</button>
                  </form>
                <?php elseif ($b['status'] === 'pending'): ?>
                  <p class="my-meta">Property visits become available after this booking is approved.</p>
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
          <p>Your scheduled and completed property visits</p>
        </div>

        <?php if ($visits->num_rows > 0): ?>
          <div class="my-grid">
            <?php while ($v = $visits->fetch_assoc()): ?>
              <article class="my-card">
                <span class="badge badge-<?= htmlspecialchars($v['status']) ?>"><?= htmlspecialchars(visitStatusLabel($v['status'])) ?></span>
                <h3><?= htmlspecialchars($v['title'] ?? 'Property removed') ?></h3>
                <p><?= htmlspecialchars($v['address'] ?? '') ?></p>
                <p class="my-meta">Visit: <?= htmlspecialchars(date('M d, Y · h:i A', strtotime($v['visit_time']))) ?></p>

                <?php if ($v['status'] === 'completed' && $v['interest_status'] === 'invoiced'): ?>
                  <?php if (($v['payment_status'] ?? '') === 'paid'): ?>
                    <p class="my-meta"><span class="badge badge-completed">Paid</span>
                      Your <?= htmlspecialchars(ucfirst($v['interest_type'])) ?> payment is complete.
                      <a href="payments.php">View payment</a>
                    </p>
                  <?php else: ?>
                    <p class="my-meta">
                      Your <?= htmlspecialchars(ucfirst($v['interest_type'])) ?> invoice is ready.
                      <a class="btn-sm" href="payments.php">Pay now (bKash / Card)</a>
                    </p>
                  <?php endif; ?>
                <?php elseif ($v['status'] === 'completed' && $v['booking_status'] === 'approved' && $v['property_status'] === 'available'): ?>
                  <?php if ($v['interest_status'] === 'pending'): ?>
                    <p class="my-meta">Your <?= htmlspecialchars(ucfirst($v['interest_type'])) ?> request is waiting for the admin to issue an invoice.</p>
                  <?php else: ?>
                    <p class="my-meta">Buy at the listed sale price or rent for one month. If the sale price is above ৳500,000, a 10% advance (max ৳500,000) is billed online. The amount is shown before you pay.</p>
                  <?php endif; ?>
                  <?php if ((float)$v['price'] > 0 || (float)$v['monthly_rent'] > 0): ?>
                    <form class="visit-form" method="post">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="action" value="request_interest">
                      <input type="hidden" name="id" value="<?= (int)$v['booking_id'] ?>">
                      <div class="my-actions">
                        <?php if ((float)$v['price'] > 0): ?>
                          <button class="btn-sm" name="intent" value="buy" type="submit">Buy · ৳<?= number_format((float)$v['price'], 2) ?></button>
                        <?php endif; ?>
                        <?php if ((float)$v['monthly_rent'] > 0): ?>
                          <button class="btn-sm" name="intent" value="rent" type="submit">Rent · ৳<?= number_format((float)$v['monthly_rent'], 2) ?>/month</button>
                        <?php endif; ?>
                      </div>
                    </form>
                  <?php else: ?>
                    <p class="my-meta">No sale price or monthly rent is listed for this property.</p>
                  <?php endif; ?>
                <?php elseif ($v['status'] === 'completed' && $v['property_status'] !== 'available'): ?>
                  <p class="my-meta">This property is no longer available for a new buy/rent request.</p>
                <?php elseif ($v['status'] === 'scheduled'): ?>
                  <div class="my-actions">
                    <?php if (strtotime($v['visit_time']) <= time()): ?>
                      <form method="post" onsubmit="return confirm('Confirm that you completed this property visit?');">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="confirm_visit">
                        <input type="hidden" name="id" value="<?= (int)$v['visit_id'] ?>">
                        <button class="btn-sm" type="submit">Mark as visited</button>
                      </form>
                    <?php else: ?>
                      <p class="my-meta">You can mark this visit after its scheduled time.</p>
                    <?php endif; ?>
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