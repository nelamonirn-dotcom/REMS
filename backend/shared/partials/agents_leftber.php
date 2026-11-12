<?php
$agentName = $_SESSION['name'] ?? 'Agent';
$agentInitial = strtoupper(substr($agentName, 0, 1));
?>
<aside class="sidebar" id="admin-sidebar" aria-label="Agent navigation">
  <div class="sidebar-brand">
    <a class="brand-lockup" href="agents_dashboard.php#overview" aria-label="EstateFlow agent dashboard">
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
    <span class="workspace-avatar" aria-hidden="true">A</span>
    <span class="workspace-copy">
      <span class="workspace-label">Workspace</span>
      <span class="workspace-name">Agent workspace</span>
    </span>
    <i class="fa-solid fa-chevron-down workspace-chevron" aria-hidden="true"></i>
  </div>

  <nav class="sidebar-nav" aria-label="Dashboard sections">
    <p class="nav-caption">Workspace</p>
    <a class="sidebar-link is-active" href="#overview" aria-current="page" title="Overview">
      <i class="fa-solid fa-chart-pie" aria-hidden="true"></i><span>Overview</span>
    </a>
    <p class="nav-caption">Operations</p>
    <a class="sidebar-link" href="#properties" title="My Properties">
      <i class="fa-solid fa-building" aria-hidden="true"></i><span>My Properties</span>
      <span class="nav-count">12</span>
    </a>
    <a class="sidebar-link" href="#property-management" title="Property Management">
      <i class="fa-solid fa-list-check" aria-hidden="true"></i><span>Property Management</span>
    </a>
    <a class="sidebar-link" href="#customers" title="Customer Management">
      <i class="fa-solid fa-users" aria-hidden="true"></i><span>Customer Management</span>
    </a>
    <a class="sidebar-link" href="#visits" title="Visit Management">
      <i class="fa-regular fa-calendar-days" aria-hidden="true"></i><span>Visit Management</span>
    </a>
    <a class="sidebar-link" href="#bookings" title="Booking Management">
      <i class="fa-regular fa-calendar-check" aria-hidden="true"></i><span>Booking Management</span>
      <span class="nav-count nav-count-warm">4</span>
    </a>
    <a class="sidebar-link" href="#commission" title="Commission">
      <i class="fa-solid fa-percent" aria-hidden="true"></i><span>Commission</span>
    </a>
    <a class="sidebar-link" href="#notifications" title="Notifications">
      <i class="fa-regular fa-bell" aria-hidden="true"></i><span>Notifications</span>
      <span class="nav-count nav-count-warm">2</span>
    </a>
    <p class="nav-caption">Account</p>
    <a class="sidebar-link" href="#profile" title="Profile">
      <i class="fa-regular fa-user" aria-hidden="true"></i><span>Profile</span>
    </a>
  </nav>

  <div class="sidebar-bottom">
    <div class="sidebar-help">
      <span class="help-icon"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></span>
      <span class="help-copy"><strong>Need a hand?</strong><small>Contact the property team</small></span>
      <a class="help-arrow" href="mailto:hello@estateflow.example" aria-label="Email support"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
    </div>
    <div class="sidebar-profile">
      <span class="profile-avatar"><?= htmlspecialchars($agentInitial, ENT_QUOTES, 'UTF-8') ?></span>
      <span class="profile-copy"><strong><?= htmlspecialchars($agentName, ENT_QUOTES, 'UTF-8') ?></strong><small>Property agent</small></span>
      <a class="profile-menu" href="../shared/partials/logout.php" aria-label="Sign out" title="Sign out"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></a>
    </div>
  </div>
</aside>
