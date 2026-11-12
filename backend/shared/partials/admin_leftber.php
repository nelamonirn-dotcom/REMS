
  <?php $currentAdminPage = basename($_SERVER['PHP_SELF'] ?? ''); ?>
  <aside class="sidebar" id="admin-sidebar" aria-label="Administrator navigation">
      <div class="sidebar-brand">
        <a class="brand-lockup" href="../../frontend/user/index.php" aria-label="EstateFlow home">
          <span class="brand-symbol" aria-hidden="true"><i class="fa-solid fa-house-chimney"></i></span>
          <span class="brand-name">Estate<span>Flow</span></span>
        </a>
        <button
          class="icon-button sidebar-collapse"
          id="sidebar-collapse"
          type="button"
          aria-label="Collapse sidebar"
          aria-expanded="true"
          title="Collapse sidebar">
          <i class="fa-solid fa-angles-left" aria-hidden="true"></i>
        </button>
      </div>

      <div class="workspace-switcher">
        <span class="workspace-avatar" aria-hidden="true">E</span>
        <span class="workspace-copy">
          <span class="workspace-label">Workspace</span>
          <span class="workspace-name">EstateFlow Realty</span>
        </span>
        <i class="fa-solid fa-chevron-down workspace-chevron" aria-hidden="true"></i>
      </div>

      <nav class="sidebar-nav" aria-label="Dashboard sections">
        <p class="nav-caption">Workspace</p>
        <a class="sidebar-link<?= $currentAdminPage === 'users_dashboard.php' ? ' is-active' : '' ?>" href="users_dashboard.php"<?= $currentAdminPage === 'users_dashboard.php' ? ' aria-current="page"' : '' ?> title="Overview">
          <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
          <span>Overview</span>
        </a>
        <p class="nav-caption">Management</p>
       

        <a class="sidebar-link<?= $currentAdminPage === 'bookings.php' ? ' is-active' : '' ?>" href="bookings.php"<?= $currentAdminPage === 'bookings.php' ? ' aria-current="page"' : '' ?> title="Bookings">
          <i class="fa-solid fa-calendar-check" aria-hidden="true"></i><span>Bookings</span>
          <span class="nav-count">8</span>
        </a>
        <a class="sidebar-link<?= $currentAdminPage === 'property_visits.php' ? ' is-active' : '' ?>" href="property_visits.php"<?= $currentAdminPage === 'property_visits.php' ? ' aria-current="page"' : '' ?> title="Property visits">
          <i class="fa-solid fa-calendar-days" aria-hidden="true"></i><span>Property visits</span>
        </a>
        <a class="sidebar-link<?= $currentAdminPage === 'agreements.php' ? ' is-active' : '' ?>" href="agreements.php"<?= $currentAdminPage === 'agreements.php' ? ' aria-current="page"' : '' ?> title="Agreements">
          <i class="fa-solid fa-file-signature" aria-hidden="true"></i><span>Agreements</span>
        </a>

        <p class="nav-caption">Finance &amp; operations</p>
        <a class="sidebar-link<?= $currentAdminPage === 'payments.php' ? ' is-active' : '' ?>" href="payments.php"<?= $currentAdminPage === 'payments.php' ? ' aria-current="page"' : '' ?> title="Payments">
          <i class="fa-solid fa-wallet" aria-hidden="true"></i><span>Payments</span>
        </a>
        <a class="sidebar-link<?= $currentAdminPage === 'commissions.php' ? ' is-active' : '' ?>" href="commissions.php"<?= $currentAdminPage === 'commissions.php' ? ' aria-current="page"' : '' ?> title="Commissions">
          <i class="fa-solid fa-percent" aria-hidden="true"></i><span>Commissions</span>
        </a>
        <a class="sidebar-link<?= $currentAdminPage === 'expenses.php' ? ' is-active' : '' ?>" href="expenses.php"<?= $currentAdminPage === 'expenses.php' ? ' aria-current="page"' : '' ?> title="Expenses">
          <i class="fa-solid fa-receipt" aria-hidden="true"></i><span>Expenses</span>
        </a>
        <a class="sidebar-link<?= $currentAdminPage === 'maintenance.php' ? ' is-active' : '' ?>" href="maintenance.php"<?= $currentAdminPage === 'maintenance.php' ? ' aria-current="page"' : '' ?> title="Maintenance">
          <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i><span>Maintenance</span>
          <span class="nav-count nav-count-warm">3</span>
        </a>
        <a class="sidebar-link<?= $currentAdminPage === 'reports_analytics.php' ? ' is-active' : '' ?>" href="reports_analytics.php"<?= $currentAdminPage === 'reports_analytics.php' ? ' aria-current="page"' : '' ?> title="Reports &amp; analytics">
          <i class="fa-solid fa-chart-line" aria-hidden="true"></i><span>Reports &amp; analytics</span>
        </a>

        <p class="nav-caption">Administration</p>
        <a class="sidebar-link<?= $currentAdminPage === 'notifications.php' ? ' is-active' : '' ?>" href="notifications.php"<?= $currentAdminPage === 'notifications.php' ? ' aria-current="page"' : '' ?> title="Notifications">
          <i class="fa-regular fa-bell" aria-hidden="true"></i><span>Notifications</span>
        </a>
        <a class="sidebar-link<?= $currentAdminPage === 'users_roles.php' ? ' is-active' : '' ?>" href="users_roles.php"<?= $currentAdminPage === 'users_roles.php' ? ' aria-current="page"' : '' ?> title="Users &amp; roles">
          <i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>Users &amp; roles</span>
        </a>
        <a class="sidebar-link<?= $currentAdminPage === 'settings.php' ? ' is-active' : '' ?>" href="settings.php"<?= $currentAdminPage === 'settings.php' ? ' aria-current="page"' : '' ?> title="Settings">
          <i class="fa-solid fa-gear" aria-hidden="true"></i><span>Settings</span>
        </a>
      </nav>

      <div class="sidebar-bottom">
        <div class="sidebar-help">
          <span class="help-icon"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></span>
          <span class="help-copy"><strong>Need a hand?</strong><small>Visit the help center</small></span>
          <i class="fa-solid fa-arrow-up-right-from-square help-arrow" aria-hidden="true"></i>
        </div>
        <div class="sidebar-profile">
          <span class="profile-avatar"></span>
          <span class="profile-copy"><strong></strong><small>Administrator</small></span>
          <a class="profile-menu" href="../shared/partials/logout.php" aria-label="Sign out"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></a>
        </div>
      </div>
    </aside>
