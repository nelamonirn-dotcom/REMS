<?php
session_start();

if (!isset($_SESSION['email'])) {
  header("Location: index.php");
  exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: unauthorize.php");
  exit;
}

$adminPageTitle = 'Bookings';

include_once __DIR__ . '/dbconfig.php'; // database connection ($conn)

// ---------- Filters (GET) ----------
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 10;

// ---------- Summary cards ----------
$stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'cancelled' => 0];
$r = $conn->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status");
while ($row = $r->fetch_assoc()) {
  $stats[$row['status']] = (int)$row['c'];
  $stats['total']       += (int)$row['c'];
}
$approvedPct = $stats['total'] > 0 ? round($stats['approved'] / $stats['total'] * 100) : 0;

// ---------- Where conditions ----------
$where  = [];
$params = [];
$types  = '';

if (in_array($statusFilter, ['pending', 'approved', 'cancelled'], true)) {
  $where[]  = "b.status = ?";
  $params[] = $statusFilter;
  $types   .= 's';
}
if ($search !== '') {
  $where[]  = "(b.booking_id LIKE ? OR b.customer_id LIKE ? OR p.title LIKE ? OR p.address LIKE ?)";
  $like     = "%$search%";
  array_push($params, $like, $like, $like, $like);
  $types   .= 'ssss';
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// ---------- Count (for pagination) ----------
$countSql = "SELECT COUNT(*) FROM bookings b
             LEFT JOIN properties p ON p.property_id = b.property_id" . $whereSql;
$stmt = $conn->prepare($countSql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$filtered   = (int)$stmt->get_result()->fetch_row()[0];
$totalPages = max(1, (int)ceil($filtered / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ---------- Bookings list ----------
$sql = "SELECT b.booking_id, b.customer_id, b.booking_date, b.status,
               p.title, p.address, p.price, p.monthly_rent
        FROM bookings b
        LEFT JOIN properties p ON p.property_id = b.property_id"
     . $whereSql . "
        ORDER BY b.booking_date DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params2 = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($types . 'ii', ...$params2);
$stmt->execute();
$bookings = $stmt->get_result();

$from = $filtered > 0 ? $offset + 1 : 0;
$to   = min($offset + $perPage, $filtered);

// ---------- Helpers ----------
function pageUrl($p) {
  $q = $_GET;
  $q['page'] = $p;
  return '?' . http_build_query($q);
}
function statusClass($s) {
  return $s === 'approved' ? 'status-confirmed' : ($s === 'cancelled' ? 'status-cancelled' : 'status-pending');
}
function statusLabel($s) {
  return $s === 'approved' ? 'Confirmed' : ucfirst($s);
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#101d35">
  <meta name="description" content="Review and manage property booking requests in the EstateFlow admin workspace.">
  <title>Bookings | EstateFlow Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../shared/assets/css/global.css">
  <link rel="stylesheet" href="assets/css/admin/dashboard.css">
  <link rel="stylesheet" href="assets/css/admin/bookings.css">
</head>

<body class="admin-dashboard">
  <a class="skip-link" href="#main-content">Skip to main content</a>
  <div class="admin-layout" id="admin-layout">
    <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
    <?php include __DIR__ . '/../shared/partials/admin_leftber.php'; ?>

    <div class="main-shell">
      <?php include __DIR__ . '/../shared/partials/header.php'; ?>

      <main class="dashboard-main" id="main-content">
        <section class="page-heading bookings-heading" aria-labelledby="bookings-title">
          <div>
            <p class="page-eyebrow"><span class="live-dot"></span> Management</p>
            <h1 id="bookings-title">Bookings</h1>
            <p class="page-subtitle">Track reservations and review incoming booking requests.</p>
          </div>
        </section>

        <section class="row g-3 booking-stats" aria-label="Booking summary">
          <div class="col-12 col-sm-6 col-xl-3">
            <article class="booking-stat-card">
              <span class="booking-stat-icon booking-stat-icon-blue"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span>
              <p>Total bookings</p>
              <strong><?= $stats['total'] ?></strong>
              <small>All time</small>
            </article>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <article class="booking-stat-card">
              <span class="booking-stat-icon booking-stat-icon-amber"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
              <p>Pending review</p>
              <strong><?= $stats['pending'] ?></strong>
              <small>Needs your attention</small>
            </article>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <article class="booking-stat-card">
              <span class="booking-stat-icon booking-stat-icon-green"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
              <p>Confirmed</p>
              <strong><?= $stats['approved'] ?></strong>
              <small><?= $approvedPct ?>% of all bookings</small>
            </article>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <article class="booking-stat-card">
              <span class="booking-stat-icon booking-stat-icon-red"><i class="fa-solid fa-ban" aria-hidden="true"></i></span>
              <p>Cancelled</p>
              <strong><?= $stats['cancelled'] ?></strong>
              <small>All time</small>
            </article>
          </div>
        </section>

        <section class="booking-panel" aria-labelledby="booking-list-title">
          <div class="booking-panel-heading">
            <div>
              <h2 id="booking-list-title">All bookings</h2>
              <p>Review booking details and current status.</p>
            </div>
          </div>

          <form class="booking-toolbar" method="get" action="">
            <label class="booking-search" for="booking-search">
              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
              <span class="visually-hidden">Search bookings</span>
              <input id="booking-search" name="q" class="form-control" type="search" placeholder="Search by ID, customer or property" value="<?= htmlspecialchars($search) ?>">
            </label>
            <label class="visually-hidden" for="booking-status">Filter by status</label>
            <select class="form-select booking-filter" id="booking-status" name="status" onchange="this.form.submit()">
              <option value="">All statuses</option>
              <option value="approved"  <?= $statusFilter === 'approved'  ? 'selected' : '' ?>>Confirmed</option>
              <option value="pending"   <?= $statusFilter === 'pending'   ? 'selected' : '' ?>>Pending</option>
              <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <button class="btn btn-outline-secondary" type="submit">Search</button>
          </form>

          <div class="table-responsive">
            <table class="table booking-table align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col">Booking ID</th>
                  <th scope="col">Customer</th>
                  <th scope="col">Property</th>
                  <th scope="col">Booking date</th>
                  <th scope="col">Amount</th>
                  <th scope="col">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($bookings->num_rows > 0): ?>
                  <?php while ($b = $bookings->fetch_assoc()): ?>
                    <tr>
                      <td><span class="booking-id">#BK-<?= (int)$b['booking_id'] ?></span></td>
                      <td>
                        <div class="guest-cell">
                          <span class="guest-avatar guest-avatar-blue">C<?= (int)$b['customer_id'] ?></span>
                          <span><strong>Customer #<?= (int)$b['customer_id'] ?></strong></span>
                        </div>
                      </td>
                      <td>
                        <strong class="property-name"><?= htmlspecialchars($b['title'] ?? 'Property removed') ?></strong>
                        <small class="property-location"><?= htmlspecialchars($b['address'] ?? '') ?></small>
                      </td>
                      <td><?= htmlspecialchars(date('M d, Y', strtotime($b['booking_date']))) ?></td>
                      <td class="booking-amount">
                        <?php if ($b['monthly_rent'] !== null): ?>
                          ৳ <?= number_format($b['monthly_rent']) ?>/month
                        <?php elseif ($b['price'] !== null): ?>
                          ৳ <?= number_format($b['price']) ?>
                        <?php else: ?>
                          —
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="booking-status <?= statusClass($b['status']) ?>"><span></span><?= statusLabel($b['status']) ?></span>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="6" class="text-center py-4">Kono booking pawa jayni.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="booking-table-footer">
            <span>Showing <strong><?= $from ?>–<?= $to ?></strong> of <strong><?= $filtered ?></strong> bookings</span>
            <?php if ($totalPages > 1): ?>
              <nav aria-label="Bookings pages">
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= pageUrl($page - 1) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
                  </li>
                  <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>>
                      <a class="page-link" href="<?= pageUrl($i) ?>"><?= $i ?></a>
                    </li>
                  <?php endfor; ?>
                  <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= pageUrl($page + 1) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                  </li>
                </ul>
              </nav>
            <?php endif; ?>
          </div>
        </section>

        <footer class="bookings-footer">
          <span>EstateFlow Admin <span aria-hidden="true">·</span> Administrator workspace</span>
        </footer>
      </main>
    </div>
  </div>
  <script>
    (() => {
      const layout = document.querySelector('#admin-layout');
      const sidebar = document.querySelector('#admin-sidebar');
      const collapseButton = document.querySelector('#sidebar-collapse');
      const menuButton = document.querySelector('#mobile-menu-toggle');
      const backdrop = document.querySelector('#sidebar-backdrop');

      const setMenuOpen = (isOpen) => {
        sidebar.classList.toggle('is-open', isOpen);
        backdrop.hidden = !isOpen;
        menuButton.setAttribute('aria-expanded', String(isOpen));
        menuButton.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
        menuButton.innerHTML = `<i class="fa-solid ${isOpen ? 'fa-xmark' : 'fa-bars'}" aria-hidden="true"></i>`;
      };

      collapseButton.addEventListener('click', () => {
        const isCollapsed = layout.classList.toggle('is-collapsed');
        collapseButton.setAttribute('aria-expanded', String(!isCollapsed));
        collapseButton.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
        collapseButton.innerHTML = `<i class="fa-solid ${isCollapsed ? 'fa-angles-right' : 'fa-angles-left'}" aria-hidden="true"></i>`;
      });

      menuButton.addEventListener('click', () => {
        setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true');
      });
      backdrop.addEventListener('click', () => setMenuOpen(false));
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMenuOpen(false);
      });
      window.addEventListener('resize', () => {
        if (window.innerWidth > 700) setMenuOpen(false);
      });
    })();
  </script>
</body>

</html>