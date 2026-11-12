<?php
session_start();

if (!isset($_SESSION['email'])) {
  header("Location: ../admin/index.php");
  exit;
}

if (($_SESSION['role'] ?? '') !== 'agent') {
  header("Location: ../admin/unauthorize.php");
  exit;
}

$agentName = $_SESSION['name'] ?? 'Agent';
$agentEmail = $_SESSION['email'] ?? '';
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#101d35">
  <meta name="description" content="Agent workspace for assigned properties, customers, visits, bookings and commission.">
  <title>Agent Dashboard | EstateFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="../shared/assets/css/global.css">
  <link rel="stylesheet" href="../admin/assets/css/admin/dashboard.css">
</head>

<body class="admin-dashboard agent-dashboard">
  <a class="skip-link" href="#main-content">Skip to main content</a>
  <div class="admin-layout" id="admin-layout">
    <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
   <-----start lefbar------>
 <?php include __DIR__ . '/../shared/partials/agents_leftber.php'; ?>
<--endlefbar-->
    <div class="main-shell">
      <header class="topbar">
        <div class="topbar-start">
          <button class="icon-button mobile-menu-toggle" id="mobile-menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="admin-sidebar">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
          </button>
          <nav class="breadcrumb" aria-label="Breadcrumb"><span>Workspace</span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i><strong>Agent dashboard</strong></nav>
        </div>
        <div class="topbar-actions">
          <div class="topbar-date"><i class="fa-regular fa-calendar" aria-hidden="true"></i><span id="current-date"></span></div>
          <a class="icon-button notification-button" href="#notifications" aria-label="View notifications, 2 unread" title="View notifications">
            <i class="fa-regular fa-bell" aria-hidden="true"></i><span class="notification-dot"></span>
          </a>
          <span class="topbar-divider" aria-hidden="true"></span>
          <a class="topbar-user" href="#profile" aria-label="View profile">
            <span class="profile-avatar"><?= $escape(strtoupper(substr($agentName, 0, 1))) ?></span>
            <span class="topbar-user-name"><?= $escape($agentName) ?></span>
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
          </a>
        </div>
      </header>

      <main class="dashboard-main" id="main-content">
        <section class="page-heading" id="overview" aria-labelledby="welcome-heading">
          <div>
            <p class="page-eyebrow"><span class="live-dot"></span> Agent workspace</p>
            <h1 id="welcome-heading"><span id="greeting">Welcome</span>, <?= $escape($agentName) ?> <span class="wave" aria-hidden="true">✦</span></h1>
            <p class="page-subtitle">Your assigned properties, customers and next steps at a glance.</p>
          </div>
          <div class="heading-actions">
            <span class="sample-label"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Sample data preview</span>
            <a class="button button-primary" href="#property-management"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Manage properties</span></a>
          </div>
        </section>

        <div class="agent-preview-notice" role="note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <span>Dashboard records are examples. Actions demonstrate the interface only; changes are not saved to the database.</span>
        </div>

        <section class="stat-grid" aria-label="Agent workspace summary">
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Assigned properties</span><span class="stat-icon stat-icon-blue"><i class="fa-solid fa-building" aria-hidden="true"></i></span></div>
            <div class="stat-value">12</div>
            <div class="stat-foot"><span class="trend trend-positive">8 active</span><span>in your portfolio</span></div>
            <span class="stat-spark spark-blue" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Commission earned</span><span class="stat-icon stat-icon-green"><i class="fa-solid fa-coins" aria-hidden="true"></i></span></div>
            <div class="stat-value">৳48,500</div>
            <div class="stat-foot"><span class="trend trend-positive">This month</span><span>৳12,000 pending</span></div>
            <span class="stat-spark spark-green" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Active bookings</span><span class="stat-icon stat-icon-purple"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span></div>
            <div class="stat-value">18</div>
            <div class="stat-foot"><span class="trend trend-positive">4 need review</span><span>assigned to you</span></div>
            <span class="stat-spark spark-purple" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Upcoming visits</span><span class="stat-icon stat-icon-amber"><i class="fa-regular fa-calendar-days" aria-hidden="true"></i></span></div>
            <div class="stat-value">07</div>
            <div class="stat-foot"><span class="trend trend-attention">2 to confirm</span><span>this week</span></div>
            <span class="stat-spark spark-amber" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
        </section>

        <section class="agent-module" id="properties" aria-labelledby="properties-title">
          <div class="agent-module-heading">
            <div><p class="page-eyebrow">Portfolio</p><h2 id="properties-title">My Properties</h2><p>Properties assigned to your account.</p></div>
            <a class="view-link" href="#property-management">Property management <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
          </div>
          <article class="panel agent-data-panel">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Property</th><th>Location</th><th>Type</th><th>Price</th><th>Status</th></tr></thead>
                <tbody>
                  <tr><td><span class="agent-table-property"><span class="property-thumb thumb-one"><i class="fa-solid fa-building" aria-hidden="true"></i></span><strong>Lakeview Heights, Apt 5B</strong></span></td><td>Gulshan 2, Dhaka</td><td>Apartment</td><td class="amount-cell">৳85,000 / mo</td><td><span class="status-badge status-confirmed"><i></i>Rented</span></td></tr>
                  <tr><td><span class="agent-table-property"><span class="property-thumb thumb-two"><i class="fa-solid fa-building" aria-hidden="true"></i></span><strong>Palm Grove Residence</strong></span></td><td>Dhanmondi, Dhaka</td><td>Apartment</td><td class="amount-cell">৳62,000 / mo</td><td><span class="status-badge status-review"><i></i>Available</span></td></tr>
                  <tr><td><span class="agent-table-property"><span class="property-thumb thumb-three"><i class="fa-solid fa-building" aria-hidden="true"></i></span><strong>Cedar Court, Unit 3C</strong></span></td><td>Banani, Dhaka</td><td>Condominium</td><td class="amount-cell">৳98,000 / mo</td><td><span class="status-badge status-confirmed"><i></i>Rented</span></td></tr>
                </tbody>
              </table>
            </div>
            <div class="agent-panel-foot">Showing 3 sample properties from your assigned portfolio.</div>
          </article>
        </section>

        <section class="agent-module" id="property-management" aria-labelledby="property-management-title">
          <div class="agent-module-heading">
            <div><p class="page-eyebrow">Listings</p><h2 id="property-management-title">Property Management</h2><p>Add or update property records, subject to your account permissions.</p></div>
          </div>
          <article class="panel agent-action-panel">
            <span class="agent-action-icon"><i class="fa-solid fa-building-circle-check" aria-hidden="true"></i></span>
            <div><h3>Manage assigned listings</h3><p>Property changes require the appropriate permission. This preview does not save listing changes.</p></div>
            <button class="button button-primary" type="button" data-preview-message="Property creation and updates need to be connected to the authorized property-management backend.">Add or update property</button>
          </article>
        </section>

        <section class="agent-module" id="customers" aria-labelledby="customers-title">
          <div class="agent-module-heading">
            <div><p class="page-eyebrow">Relationships</p><h2 id="customers-title">Customer Management</h2><p>Manage customers associated with your assigned listings.</p></div>
            <button class="button button-primary" type="button" data-preview-message="Customer records are examples. Connect customer management to the backend to add or edit customers."><i class="fa-solid fa-plus" aria-hidden="true"></i> Add customer</button>
          </div>
          <article class="panel agent-data-panel">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Customer</th><th>Interested in</th><th>Last contact</th><th>Follow-up</th></tr></thead>
                <tbody>
                  <tr><td><span class="customer-cell"><span class="table-avatar avatar-lilac">NK</span><span><strong>Nadia Karim</strong><small>nadia@example.com</small></span></span></td><td>Lakeview Heights</td><td>Today</td><td><span class="status-badge status-confirmed"><i></i>Visit scheduled</span></td></tr>
                  <tr><td><span class="customer-cell"><span class="table-avatar avatar-blue">IH</span><span><strong>Imran Hossain</strong><small>imran@example.com</small></span></span></td><td>Palm Grove Residence</td><td>Yesterday</td><td><span class="status-badge status-pending"><i></i>Follow up</span></td></tr>
                  <tr><td><span class="customer-cell"><span class="table-avatar avatar-green">MC</span><span><strong>Maliha Chowdhury</strong><small>maliha@example.com</small></span></span></td><td>Cedar Court, Unit 3C</td><td>Nov 10</td><td><span class="status-badge status-review"><i></i>New inquiry</span></td></tr>
                </tbody>
              </table>
            </div>
            <div class="agent-panel-foot">Customer information shown here is sample data.</div>
          </article>
        </section>

        <section class="agent-module" id="visits" aria-labelledby="visits-title">
          <div class="agent-module-heading">
            <div><p class="page-eyebrow">Schedule</p><h2 id="visits-title">Visit Management</h2><p>Schedule, confirm and update property visits.</p></div>
            <button class="button button-primary" type="button" data-preview-message="Visit scheduling is a preview only and is not saved. Connect this action to the visits backend."><i class="fa-solid fa-plus" aria-hidden="true"></i> Schedule visit</button>
          </div>
          <article class="panel agent-operation-panel">
            <div class="agent-operation-row">
              <span class="agent-operation-date">12<small>NOV</small></span><div class="agent-operation-copy"><strong>Lakeview Heights, Apt 5B</strong><small>Nadia Karim · Today, 10:30 AM</small></div>
              <span class="status-badge status-confirmed" data-visit-status="nadia"><i></i>Confirmed</span>
              <button class="agent-text-button" type="button" data-update-visit="nadia" data-preview-message="Visit confirmation is only changed in this preview; it is not saved.">Update</button>
            </div>
            <div class="agent-operation-row">
              <span class="agent-operation-date">12<small>NOV</small></span><div class="agent-operation-copy"><strong>Palm Grove Residence</strong><small>Imran Hossain · Today, 1:00 PM</small></div>
              <span class="status-badge status-pending" data-visit-status="imran"><i></i>Awaiting confirmation</span>
              <button class="agent-text-button" type="button" data-confirm-visit="imran" data-preview-message="Visit confirmation is only changed in this preview; it is not saved.">Confirm</button>
            </div>
            <div class="agent-operation-row">
              <span class="agent-operation-date">13<small>NOV</small></span><div class="agent-operation-copy"><strong>Cedar Court, Unit 3C</strong><small>Maliha Chowdhury · Tomorrow, 11:00 AM</small></div>
              <span class="status-badge status-confirmed" data-visit-status="maliha"><i></i>Confirmed</span>
              <button class="agent-text-button" type="button" data-update-visit="maliha" data-preview-message="Visit details are not saved. Connect updates to the visits backend.">Update</button>
            </div>
          </article>
        </section>

        <section class="agent-module" id="bookings" aria-labelledby="bookings-title">
          <div class="agent-module-heading">
            <div><p class="page-eyebrow">Reservations</p><h2 id="bookings-title">Booking Management</h2><p>View and process bookings assigned to you.</p></div>
          </div>
          <article class="panel agent-data-panel">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Booking</th><th>Customer</th><th>Property</th><th>Request date</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                  <tr><td>BK-2048</td><td>Nadia Karim</td><td>Lakeview Heights, Apt 5B</td><td>Nov 11</td><td><span class="status-badge status-pending" data-booking-status="2048"><i></i>Pending</span></td><td><button class="agent-text-button" type="button" data-process-booking="2048" data-preview-message="Booking status changed in this preview only; processing is not saved.">Process</button></td></tr>
                  <tr><td>BK-2047</td><td>Imran Hossain</td><td>Palm Grove Residence</td><td>Nov 10</td><td><span class="status-badge status-review"><i></i>In review</span></td><td><button class="agent-text-button" type="button" data-preview-message="Booking actions need to be connected to the authorized bookings backend.">View</button></td></tr>
                  <tr><td>BK-2044</td><td>Maliha Chowdhury</td><td>Cedar Court, Unit 3C</td><td>Nov 08</td><td><span class="status-badge status-confirmed"><i></i>Confirmed</span></td><td><button class="agent-text-button" type="button" data-preview-message="Booking actions need to be connected to the authorized bookings backend.">View</button></td></tr>
                </tbody>
              </table>
            </div>
            <div class="agent-panel-foot">Only bookings assigned to this agent should be processed.</div>
          </article>
        </section>

        <section class="agent-module" id="commission" aria-labelledby="commission-title">
          <div class="agent-module-heading">
            <div><p class="page-eyebrow">Earnings</p><h2 id="commission-title">Commission</h2><p>Your earned and pending commission.</p></div>
          </div>
          <div class="agent-commission-grid">
            <article class="panel agent-commission-card"><span class="stat-icon stat-icon-green"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span><div><small>Earned this month</small><strong>৳48,500</strong><p>Across completed transactions</p></div></article>
            <article class="panel agent-commission-card"><span class="stat-icon stat-icon-amber"><i class="fa-regular fa-clock" aria-hidden="true"></i></span><div><small>Pending commission</small><strong>৳12,000</strong><p>Awaiting transaction completion</p></div></article>
          </div>
        </section>

        <section class="agent-module" id="notifications" aria-labelledby="notifications-title">
          <div class="agent-module-heading">
            <div><p class="page-eyebrow">Updates</p><h2 id="notifications-title">Notifications</h2><p>Recent booking and visit updates.</p></div>
          </div>
          <article class="panel agent-notification-panel">
            <div class="agent-notification-row"><span class="agent-notification-icon notification-blue"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span><div><strong>New visit request</strong><p>Nadia Karim requested a viewing at Lakeview Heights.</p></div><small>Today · 9:15 AM</small></div>
            <div class="agent-notification-row"><span class="agent-notification-icon notification-green"><i class="fa-solid fa-house" aria-hidden="true"></i></span><div><strong>Booking updated</strong><p>Palm Grove Residence booking is ready for your review.</p></div><small>Yesterday · 4:40 PM</small></div>
          </article>
        </section>

        <section class="agent-module" id="profile" aria-labelledby="profile-title">
          <div class="agent-module-heading">
            <div><p class="page-eyebrow">Account</p><h2 id="profile-title">Profile</h2><p>Update your personal information.</p></div>
          </div>
          <form class="panel agent-profile-form" id="agent-profile-form">
            <label class="form-field" for="agent-profile-name">Full name<input id="agent-profile-name" name="name" type="text" value="<?= $escape($agentName) ?>" required></label>
            <label class="form-field" for="agent-profile-email">Email address<input id="agent-profile-email" name="email" type="email" value="<?= $escape($agentEmail) ?>" required></label>
            <p class="agent-profile-note">Profile edits in this preview are not saved to your account.</p>
            <button class="button button-primary" type="submit">Update profile</button>
            <p class="agent-feedback" id="agent-feedback" role="status" aria-live="polite" hidden></p>
          </form>
        </section>

        <p class="agent-action-feedback" id="agent-action-feedback" role="status" aria-live="polite" hidden></p>
        <footer class="dashboard-footer"><span>EstateFlow · Agent workspace</span><span>Sample data preview · changes are not saved.</span></footer>
      </main>
    </div>
  </div>

  <script>
    (() => {
      const layout = document.getElementById('admin-layout');
      const sidebar = document.getElementById('admin-sidebar');
      const collapseButton = document.getElementById('sidebar-collapse');
      const mobileMenuButton = document.getElementById('mobile-menu-toggle');
      const backdrop = document.getElementById('sidebar-backdrop');
      const navLinks = [...sidebar.querySelectorAll('.sidebar-link[href^="#"]')];
      const actionFeedback = document.getElementById('agent-action-feedback');

      const updateNavigation = () => {
        const activeHash = window.location.hash || '#overview';
        navLinks.forEach((link) => {
          const isActive = link.getAttribute('href') === activeHash;
          link.classList.toggle('is-active', isActive);
          if (isActive) link.setAttribute('aria-current', 'page');
          else link.removeAttribute('aria-current');
        });
      };

      const setMobileMenuOpen = (isOpen) => {
        sidebar.classList.toggle('is-open', isOpen);
        backdrop.hidden = !isOpen;
        mobileMenuButton.setAttribute('aria-expanded', String(isOpen));
        mobileMenuButton.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
        mobileMenuButton.innerHTML = `<i class="fa-solid ${isOpen ? 'fa-xmark' : 'fa-bars'}" aria-hidden="true"></i>`;
        document.body.classList.toggle('menu-is-open', isOpen);
      };

      collapseButton.addEventListener('click', () => {
        const isCollapsed = layout.classList.toggle('is-collapsed');
        collapseButton.setAttribute('aria-expanded', String(!isCollapsed));
        collapseButton.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
        collapseButton.title = isCollapsed ? 'Expand sidebar' : 'Collapse sidebar';
        collapseButton.innerHTML = `<i class="fa-solid ${isCollapsed ? 'fa-angles-right' : 'fa-angles-left'}" aria-hidden="true"></i>`;
      });

      mobileMenuButton.addEventListener('click', () => {
        setMobileMenuOpen(mobileMenuButton.getAttribute('aria-expanded') !== 'true');
      });
      backdrop.addEventListener('click', () => setMobileMenuOpen(false));
      sidebar.addEventListener('click', (event) => {
        if (event.target.closest('.sidebar-link[href^="#"]')) setMobileMenuOpen(false);
      });
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMobileMenuOpen(false);
      });
      window.addEventListener('resize', () => {
        if (window.innerWidth > 700) setMobileMenuOpen(false);
      });
      window.addEventListener('hashchange', updateNavigation);
      navLinks.forEach((link) => link.addEventListener('click', updateNavigation));

      document.getElementById('current-date').textContent = new Intl.DateTimeFormat('en', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
      }).format(new Date());
      const hour = new Date().getHours();
      document.getElementById('greeting').textContent = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
      updateNavigation();

      document.querySelectorAll('[data-preview-message]').forEach((button) => {
        button.addEventListener('click', () => {
          actionFeedback.textContent = button.dataset.previewMessage;
          actionFeedback.hidden = false;
          actionFeedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
      });

      document.querySelectorAll('[data-confirm-visit], [data-update-visit]').forEach((button) => {
        button.addEventListener('click', () => {
          if (button.hasAttribute('data-confirm-visit')) {
            const status = document.querySelector(`[data-visit-status="${button.dataset.confirmVisit}"]`);
            status.className = 'status-badge status-confirmed';
            status.innerHTML = '<i></i>Confirmed';
            button.textContent = 'Update';
            button.dataset.updateVisit = button.dataset.confirmVisit;
            button.removeAttribute('data-confirm-visit');
          }
          actionFeedback.textContent = button.dataset.previewMessage;
          actionFeedback.hidden = false;
        });
      });

      document.querySelectorAll('[data-process-booking]').forEach((button) => {
        button.addEventListener('click', () => {
          const status = document.querySelector(`[data-booking-status="${button.dataset.processBooking}"]`);
          status.className = 'status-badge status-review';
          status.innerHTML = '<i></i>In review';
          button.textContent = 'In review';
          button.disabled = true;
          actionFeedback.textContent = button.dataset.previewMessage;
          actionFeedback.hidden = false;
        });
      });

      document.getElementById('agent-profile-form').addEventListener('submit', (event) => {
        event.preventDefault();
        const feedback = document.getElementById('agent-feedback');
        feedback.textContent = 'Your profile changes are shown for preview only and have not been saved.';
        feedback.hidden = false;
      });
    })();
  </script>
</body>

</html>
