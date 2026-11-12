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
?>
<?php
if($_SERVER['REQUEST_METHOD']=='POST'){
    // data received from entry from
    $date = $_POST ['date'];
    $status = $_POST ['status'];
   
    include_once("dbconfig.php"); // database Connection

$result = $conn->query("INSERT INTO allstudents (booking_id, property_id, customer_id, date,status,phone) VALUES (NULL,NULL, NULL,'$date','$status')");


if ($conn->affected_rows){

echo "success";
}

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
          <span class="sample-label"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Sample data preview</span>
        </section>

        <section class="row g-3 booking-stats" aria-label="Booking summary">
          <div class="col-12 col-sm-6 col-xl-3">
            <article class="booking-stat-card">
              <span class="booking-stat-icon booking-stat-icon-blue"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span>
              <p>Total bookings</p>
              <strong>128</strong>
              <small><span class="booking-trend"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i> 12.5%</span> vs. last month</small>
            </article>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <article class="booking-stat-card">
              <span class="booking-stat-icon booking-stat-icon-amber"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
              <p>Pending review</p>
              <strong>08</strong>
              <small>Needs your attention</small>
            </article>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <article class="booking-stat-card">
              <span class="booking-stat-icon booking-stat-icon-green"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
              <p>Confirmed</p>
              <strong>96</strong>
              <small>75% of all bookings</small>
            </article>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <article class="booking-stat-card">
              <span class="booking-stat-icon booking-stat-icon-red"><i class="fa-solid fa-ban" aria-hidden="true"></i></span>
              <p>Cancelled</p>
              <strong>24</strong>
              <small>This month</small>
            </article>
          </div>
        </section>

        <section class="booking-panel" aria-labelledby="booking-list-title">
          <div class="booking-panel-heading">
            <div>
              <h2 id="booking-list-title">All bookings</h2>
              <p>Review booking details and current status.</p>
            </div>
            <button class="btn btn-outline-secondary booking-export" type="button">
              <i class="fa-solid fa-arrow-down-to-line me-2" aria-hidden="true"></i>Export
            </button>
          </div>

          <div class="booking-toolbar">
            <label class="booking-search" for="booking-search">
              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
              <span class="visually-hidden">Search bookings</span>
              <input id="booking-search" class="form-control" type="search" placeholder="Search by guest, property or ID">
            </label>
            <label class="visually-hidden" for="booking-status">Filter by status</label>
            <select class="form-select booking-filter" id="booking-status">
              <option selected>All statuses</option>
              <option>Confirmed</option>
              <option>Pending</option>
              <option>Cancelled</option>
            </select>
            <label class="visually-hidden" for="booking-date">Filter by date</label>
            <select class="form-select booking-filter" id="booking-date">
              <option selected>All dates</option>
              <option>This month</option>
              <option>Last month</option>
            </select>
          </div>

          <div class="table-responsive">
            <table class="table booking-table align-middle mb-0">
              <thead>
                <tr>
                  <th scope="col">Booking ID</th>
                  <th scope="col">Guest</th>
                  <th scope="col">Property</th>
                  <th scope="col">Booking date</th>
                  <th scope="col">Amount</th>
                  <th scope="col">Status</th>
                  <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><span class="booking-id">#BK-2048</span></td>
                  <td><div class="guest-cell"><span class="guest-avatar guest-avatar-blue">AR</span><span><strong>Arif Rahman</strong><small>arif.rahman@email.com</small></span></div></td>
                  <td><strong class="property-name">Lakeview Heights, Apt 5B</strong><small class="property-location">Gulshan, Dhaka</small></td>
                  <td>Nov 08, 2026</td>
                  <td class="booking-amount">৳ 85,000</td>
                  <td><span class="booking-status status-confirmed"><span></span>Confirmed</span></td>
                  <td><button class="booking-more" type="button" aria-label="More actions for booking BK-2048"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i></button></td>
                </tr>
                <tr>
                  <td><span class="booking-id">#BK-2047</span></td>
                  <td><div class="guest-cell"><span class="guest-avatar guest-avatar-purple">SN</span><span><strong>Sadia Noor</strong><small>sadia.noor@email.com</small></span></div></td>
                  <td><strong class="property-name">The Grand Residence</strong><small class="property-location">Banani, Dhaka</small></td>
                  <td>Nov 07, 2026</td>
                  <td class="booking-amount">৳ 120,000</td>
                  <td><span class="booking-status status-pending"><span></span>Pending</span></td>
                  <td><button class="booking-more" type="button" aria-label="More actions for booking BK-2047"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i></button></td>
                </tr>
                <tr>
                  <td><span class="booking-id">#BK-2046</span></td>
                  <td><div class="guest-cell"><span class="guest-avatar guest-avatar-green">TH</span><span><strong>Tariq Hasan</strong><small>tariq.hasan@email.com</small></span></div></td>
                  <td><strong class="property-name">Palm Grove Apartment</strong><small class="property-location">Dhanmondi, Dhaka</small></td>
                  <td>Nov 06, 2026</td>
                  <td class="booking-amount">৳ 65,000</td>
                  <td><span class="booking-status status-confirmed"><span></span>Confirmed</span></td>
                  <td><button class="booking-more" type="button" aria-label="More actions for booking BK-2046"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i></button></td>
                </tr>
                <tr>
                  <td><span class="booking-id">#BK-2045</span></td>
                  <td><div class="guest-cell"><span class="guest-avatar guest-avatar-orange">FM</span><span><strong>Farhana Mahmud</strong><small>farhana.mahmud@email.com</small></span></div></td>
                  <td><strong class="property-name">Riverside Tower, Unit 8A</strong><small class="property-location">Uttara, Dhaka</small></td>
                  <td>Nov 05, 2026</td>
                  <td class="booking-amount">৳ 92,000</td>
                  <td><span class="booking-status status-cancelled"><span></span>Cancelled</span></td>
                  <td><button class="booking-more" type="button" aria-label="More actions for booking BK-2045"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i></button></td>
                </tr>
                <tr>
                  <td><span class="booking-id">#BK-2044</span></td>
                  <td><div class="guest-cell"><span class="guest-avatar guest-avatar-teal">MI</span><span><strong>Mahin Islam</strong><small>mahin.islam@email.com</small></span></div></td>
                  <td><strong class="property-name">Cedar Court, Apartment 3C</strong><small class="property-location">Bashundhara, Dhaka</small></td>
                  <td>Nov 04, 2026</td>
                  <td class="booking-amount">৳ 78,000</td>
                  <td><span class="booking-status status-confirmed"><span></span>Confirmed</span></td>
                  <td><button class="booking-more" type="button" aria-label="More actions for booking BK-2044"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="booking-table-footer">
            <span>Showing <strong>1–5</strong> of <strong>128</strong> bookings</span>
            <nav aria-label="Bookings pages">
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a></li>
                <li class="page-item active" aria-current="page"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><span class="page-link page-ellipsis">...</span></li>
                <li class="page-item"><a class="page-link" href="#">26</a></li>
                <li class="page-item"><a class="page-link" href="#" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></li>
              </ul>
            </nav>
          </div>
        </section>

      <form action="" method="post">



        <footer class="bookings-footer">
          <span>EstateFlow Admin <span aria-hidden="true">·</span> Administrator workspace</span>
          <span>Booking information shown is sample preview content.</span>
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
