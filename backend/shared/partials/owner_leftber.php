<?php
$ownerName = $_SESSION['name'] ?? 'Property Owner';
$ownerInitial = strtoupper(substr($ownerName, 0, 1));
?>
<aside class="owner-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="ownerSidebar" aria-labelledby="ownerSidebarTitle">
  <div class="owner-sidebar-head">
    <a class="owner-brand" href="owner_dashboard.php#overview" aria-label="EstateFlow owner dashboard">
      <span class="owner-brand-mark"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i></span>
      <span>Estate<span>Flow</span></span>
    </a>
    <button class="owner-sidebar-collapse" id="owner-sidebar-collapse" type="button" aria-label="Collapse sidebar" aria-expanded="true" title="Collapse sidebar">
      <i class="fa-solid fa-angles-left" aria-hidden="true"></i>
    </button>
    <button class="btn-close d-lg-none" type="button" data-bs-dismiss="offcanvas" data-bs-target="#ownerSidebar" aria-label="Close navigation"></button>
  </div>

  <div class="owner-workspace">
    <span class="workspace-avatar"><?= htmlspecialchars($ownerInitial, ENT_QUOTES, 'UTF-8') ?></span>
    <span><small>Workspace</small><strong>Owner workspace</strong></span>
    <i class="fa-solid fa-chevron-down workspace-chevron" aria-hidden="true"></i>
  </div>

  <nav class="owner-nav" aria-labelledby="ownerSidebarTitle">
    <h2 id="ownerSidebarTitle" class="owner-nav-label">Workspace</h2>
    <a class="owner-nav-link is-active" href="#overview" aria-current="page" title="Overview"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i><span>Overview</span></a>
    <h2 class="owner-nav-label">Operations</h2>
    <a class="owner-nav-link" href="property_crud/myproperty.php" title="My Properties"><i class="fa-solid fa-building" aria-hidden="true"></i><span>My Properties</span><span class="owner-nav-count">12</span></a>
    <a class="owner-nav-link" href="property_crud/Addproperty.php" title="Add Property"><i class="fa-solid fa-list-check" aria-hidden="true"></i><span>Add Property</span></a>
    <a class="owner-nav-link" href="property_crud/property_stutas.php" title="Property Status"><i class="fa-solid fa-signal" aria-hidden="true"></i><span>Property Status</span></a>
    <a class="owner-nav-link" href="property_crud/bookings.php" title="Bookings"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i><span>Bookings</span><span class="owner-nav-count owner-count-warm">4</span></a>
    <a class="owner-nav-link" href="#payments" title="Payments"><i class="fa-solid fa-wallet" aria-hidden="true"></i><span>Payments</span></a>
    <a class="owner-nav-link" href="property_crud/property_Images.php" title="Property Images"><i class="fa-regular fa-images" aria-hidden="true"></i><span>Property Images</span></a>
    <a class="owner-nav-link" href="property_crud/notifications.php" title="Notifications"><i class="fa-regular fa-bell" aria-hidden="true"></i><span>Notifications</span><span class="owner-nav-count owner-count-warm">3</span></a>
    <h2 class="owner-nav-label owner-nav-label-spaced">Account</h2>
    <a class="owner-nav-link" href="property_crud/profile.php" title="Profile"><i class="fa-regular fa-user" aria-hidden="true"></i><span>Profile</span></a>
  </nav>

  <div class="owner-sidebar-bottom">
    <div class="owner-help-card">
      <span class="owner-help-icon"><i class="fa-regular fa-circle-question" aria-hidden="true"></i></span>
      <span class="owner-help-copy"><strong>Need a hand?</strong><small>Contact the property team</small></span>
      <a href="property_crud/Need_ahand.php" aria-label="Open help and support"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
    </div>
    <div class="owner-account">
      <span class="owner-avatar"><?= htmlspecialchars($ownerInitial, ENT_QUOTES, 'UTF-8') ?></span>
      <span class="owner-account-name"><strong><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></strong><small>Property owner</small></span>
      <a class="owner-logout" href="../shared/partials/logout.php" aria-label="Sign out" title="Sign out"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></a>
    </div>
  </div>
</aside>
