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

$owner_id = (int)$_SESSION['user_id'];

// Booking actions (shudhu nijer property-r booking e)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $bookingId = (int)($_POST['booking_id'] ?? 0); 
  $action    = $_POST['action'] ?? '';
  $map       = ['approve' => 'approved', 'cancel' => 'cancelled'];

  if ($bookingId > 0 && $action === 'mark_visited') {
    $conn->begin_transaction();

    $bookingStmt = $conn->prepare("SELECT b.property_id, b.customer_id
                                   FROM bookings b
                                   JOIN properties p ON p.property_id = b.property_id
                                   WHERE b.booking_id = ? AND p.owner_id = ? AND b.status = 'approved'
                                   FOR UPDATE");
    $bookingStmt->bind_param('ii', $bookingId, $owner_id);
    $bookingStmt->execute();
    $booking = $bookingStmt->get_result()->fetch_assoc();

    if (!$booking) {
      $conn->rollback();
      $visitMessage = 'unavailable';
    } else {
      $completedStmt = $conn->prepare("SELECT visit_id FROM visits
                                       WHERE booking_id = ? AND status = 'completed'
                                       LIMIT 1");
      $completedStmt->bind_param('i', $bookingId);
      $completedStmt->execute();

      if ($completedStmt->get_result()->num_rows > 0) {
        $conn->commit();
        $visitMessage = 'already_completed';
      } else {
        $scheduledStmt = $conn->prepare("SELECT visit_id FROM visits
                                         WHERE booking_id = ? AND status = 'scheduled'
                                         ORDER BY visit_time
                                         LIMIT 1");
        $scheduledStmt->bind_param('i', $bookingId);
        $scheduledStmt->execute();
        $scheduledVisit = $scheduledStmt->get_result()->fetch_assoc();

        if ($scheduledVisit) {
          $visitStmt = $conn->prepare("UPDATE visits
                                       SET status = 'completed', visit_time = NOW()
                                       WHERE visit_id = ?");
          $visitStmt->bind_param('i', $scheduledVisit['visit_id']);
        } else {
          $agentStmt = $conn->prepare("SELECT a.agent_id
                                       FROM agent_properties ap
                                       JOIN agents a ON a.agent_id = ap.agent_id AND a.status = 'active'
                                       WHERE ap.property_id = ?
                                       ORDER BY ap.assigned_at, ap.agent_id
                                       LIMIT 1");
          $agentStmt->bind_param('i', $booking['property_id']);
          $agentStmt->execute();
          $agent = $agentStmt->get_result()->fetch_assoc();

          if ($agent) {
            $agentId = (int)$agent['agent_id'];
            $visitStmt = $conn->prepare("INSERT INTO visits
                                           (booking_id, property_id, customer_id, agent_id, visit_time, status)
                                         VALUES (?, ?, ?, ?, NOW(), 'completed')");
            $visitStmt->bind_param('iiii', $bookingId, $booking['property_id'], $booking['customer_id'], $agentId);
          } else {
            $visitStmt = $conn->prepare("INSERT INTO visits
                                           (booking_id, property_id, customer_id, visit_time, status)
                                         VALUES (?, ?, ?, NOW(), 'completed')");
            $visitStmt->bind_param('iii', $bookingId, $booking['property_id'], $booking['customer_id']);
          }
        }

        $visitStmt->execute();
        $conn->commit();
        $visitMessage = 'completed';
      }
    }

    header('Location: bookings.php?visit=' . urlencode($visitMessage));
    exit;
  } elseif ($bookingId > 0 && isset($map[$action])) {
    $newStatus = $map[$action];
    $stmt = $conn->prepare("UPDATE bookings b
                            JOIN properties p ON p.property_id = b.property_id
                            SET b.status = ?
                            WHERE b.booking_id = ? AND p.owner_id = ?");
    $stmt->bind_param('sii', $newStatus, $bookingId, $owner_id);
    $stmt->execute();
  }
  header('Location: bookings.php');
  exit;
}

// Owner-er property gulor shob booking
$stmt = $conn->prepare("SELECT b.booking_id, b.booking_date, b.status, b.created_at,
                               p.title, p.address,
                               u.name AS customer_name, u.email AS customer_email,
                               EXISTS(SELECT 1 FROM visits v
                                      WHERE v.booking_id = b.booking_id AND v.status = 'completed') AS visit_completed,
                               (SELECT pay.status
                                FROM agreements ag
                                JOIN payments pay ON pay.agreement_id = ag.agreement_id
                                WHERE ag.booking_id = b.booking_id
                                ORDER BY pay.payment_id DESC
                                LIMIT 1) AS payment_status,
                               (SELECT pay.payment_type
                                FROM agreements ag
                                JOIN payments pay ON pay.agreement_id = ag.agreement_id
                                WHERE ag.booking_id = b.booking_id
                                ORDER BY pay.payment_id DESC
                                LIMIT 1) AS payment_type
                        FROM bookings b
                        JOIN properties p ON p.property_id = b.property_id
                        JOIN customers c  ON c.customer_id = b.customer_id
                        JOIN users u      ON u.user_id = c.user_id
                        WHERE p.owner_id = ?
                        ORDER BY b.created_at DESC");
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total    = count($bookings);
$pending  = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
$approved = count(array_filter($bookings, fn($b) => $b['status'] === 'approved'));

$avatars = ['avatar-blue', 'avatar-green', 'avatar-purple', 'avatar-amber'];
$labels  = ['pending' => 'Pending review', 'approved' => 'Approved', 'cancelled' => 'Cancelled'];

function initials($name) {
  $parts = preg_split('/\s+/', trim($name));
  $out = strtoupper(mb_substr($parts[0] ?? '', 0, 1));
  if (count($parts) > 1) $out .= strtoupper(mb_substr(end($parts), 0, 1));
  return $out ?: '?';
}
function h($s) { return htmlspecialchars((string)$s); }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Review booking requests for your properties.">
    <title>Bookings | EstateFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="../assets/css/owner/dashboard.css">
    <link rel="stylesheet" href="../assets/css/owner/bookings.css">
    <link rel="stylesheet" href="../../shared/assets/css/palette.css">
    <style>
        .status-cancelled { background:#fdecea; color:#b3261e; }
        .action-form { display:inline-block; margin:0; }
    </style>
</head>
<body class="owner-bookings-page">
    <div class="owner-layout">
        <?php
        $ownerPageBase = '../';
        $ownerPageTitle = 'Bookings';
        $ownerActivePage = 'bookings';
        include __DIR__ . '/../include/owner_leftber.php';
        ?>
        <div class="owner-main-shell">
            <?php include __DIR__ . '/../../shared/partials/header.php'; ?>
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
                <h1 class="display-6 fw-bold mb-2">Bookings</h1>
                <p class="text-secondary mb-0">Keep track of booking requests for your properties.</p>
            </div>
            <a class="btn btn-outline-primary" href="myproperty.php">View my properties</a>
        </header>

        <?php
        $visitMessages = [
            'completed' => ['success', 'Visit marked as completed. Admin can now see it in Property visits.'],
            'already_completed' => ['info', 'This booking already has a completed visit.'],
            'unavailable' => ['danger', 'Only approved bookings for your properties can be marked as visited.'],
        ];
        $visitNotice = $visitMessages[$_GET['visit'] ?? ''] ?? null;
        ?>
        <?php if ($visitNotice): ?>
            <div class="alert alert-<?= h($visitNotice[0]) ?> alert-dismissible fade show" role="status">
                <?= h($visitNotice[1]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <section class="row g-3 mb-4" aria-label="Booking totals">
            <div class="col-12 col-sm-4">
                <article class="summary-card h-100">
                    <span class="summary-label">Total requests</span>
                    <strong><?= str_pad($total, 2, '0', STR_PAD_LEFT) ?></strong>
                </article>
            </div>
            <div class="col-12 col-sm-4">
                <article class="summary-card h-100">
                    <span class="summary-label">Pending review</span>
                    <strong class="status-text-pending"><?= str_pad($pending, 2, '0', STR_PAD_LEFT) ?></strong>
                </article>
            </div>
            <div class="col-12 col-sm-4">
                <article class="summary-card h-100">
                    <span class="summary-label">Approved</span>
                    <strong class="status-text-approved"><?= str_pad($approved, 2, '0', STR_PAD_LEFT) ?></strong>
                </article>
            </div>
        </section>

        <section class="booking-panel" aria-labelledby="booking-list-title">
            <div class="booking-panel-heading d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 id="booking-list-title" class="h4 mb-1">Booking requests</h2>
                    <p class="text-secondary mb-0">Recent requests for your listed properties.</p>
                </div>
                <span class="request-count"><?= $total ?> request<?= $total === 1 ? '' : 's' ?></span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Customer</th>
                            <th scope="col">Property</th>
                            <th scope="col">Requested on</th>
                            <th scope="col">Viewing date</th>
                            <th scope="col">Status</th>
                            <th scope="col">Payment</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($total === 0): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-4">Ekhono kono booking request ashe ni.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $i => $b): ?>
                        <tr>
                            <td>
                                <span class="customer-avatar <?= $avatars[$i % 4] ?>" aria-hidden="true"><?= h(initials($b['customer_name'])) ?></span>
                                <span class="customer-info"><strong><?= h($b['customer_name']) ?></strong><small><?= h($b['customer_email']) ?></small></span>
                            </td>
                            <td><strong><?= h($b['title']) ?></strong><small><?= h($b['address']) ?></small></td>
                            <td><?= h(date('M d, Y', strtotime($b['created_at']))) ?></td>
                            <td><?= h(date('M d, Y · g:i A', strtotime($b['booking_date']))) ?></td>
                            <td><span class="status-badge status-<?= h($b['status']) ?>"><?= h($labels[$b['status']] ?? ucfirst($b['status'])) ?></span></td>
                            <td>
                                <?php if ($b['payment_status'] === 'paid'): ?>
                                    <span class="badge text-bg-success">Paid · <?= h(ucfirst($b['payment_type'])) ?></span>
                                <?php elseif ($b['payment_status'] !== null): ?>
                                    <span class="badge text-bg-warning">Awaiting payment</span>
                                <?php else: ?>
                                    <span class="text-secondary">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($b['status'] === 'pending'): ?>
                                    <form class="action-form" method="post">
                                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                                        <button class="btn btn-sm btn-success" name="action" value="approve" type="submit">Approve</button>
                                        <button class="btn btn-sm btn-outline-danger" name="action" value="cancel" type="submit"
                                                onclick="return confirm('Ei booking cancel korben?');">Cancel</button>
                                    </form>
                                <?php elseif ($b['status'] === 'approved'): ?>
                                    <form class="action-form" method="post">
                                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                                        <?php if ($b['visit_completed']): ?>
                                            <span class="badge text-bg-success">Visited</span>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-primary" name="action" value="mark_visited" type="submit"
                                                    onclick="return confirm('Mark this property visit as completed? Admin will be able to see it.');">Mark visited</button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" name="action" value="cancel" type="submit"
                                                onclick="return confirm('Ei booking cancel korben?');">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-secondary">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <footer class="page-footer mt-5 pt-3">
            <span>EstateFlow · Owner workspace</span>
            <a href="../owner_dashboard.php">Return to dashboard</a>
        </footer>
    </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>