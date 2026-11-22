<?php if (isset($ownerPageTitle)): ?>
  <?php
  $ownerPageBase = $ownerPageBase ?? '';
  $ownerName = $_SESSION['name'] ?? 'Property Owner';
  $ownerInitial = strtoupper(substr($ownerName, 0, 1));
  ?>
  <header class="owner-topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="owner-menu-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#ownerSidebar" aria-controls="ownerSidebar" aria-label="Open navigation">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
      </button>
      <nav class="owner-breadcrumb" aria-label="Breadcrumb">
        <span>Owner workspace</span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <strong><?= htmlspecialchars($ownerPageTitle, ENT_QUOTES, 'UTF-8') ?></strong>
      </nav>
    </div>
    <div class="owner-topbar-actions">
      <span class="owner-today"><i class="fa-regular fa-calendar" aria-hidden="true"></i><span id="owner-date"><?= htmlspecialchars(date('D, M j'), ENT_QUOTES, 'UTF-8') ?></span></span>
      <a class="owner-icon-button owner-notifications" href="<?= htmlspecialchars($ownerPageBase . 'property_crud/notifications.php', ENT_QUOTES, 'UTF-8') ?>" aria-label="View notifications">
        <i class="fa-regular fa-bell" aria-hidden="true"></i>
      </a>
      <span class="owner-topbar-divider" aria-hidden="true"></span>
      <a class="owner-topbar-profile" href="<?= htmlspecialchars($ownerPageBase . 'property_crud/profile.php', ENT_QUOTES, 'UTF-8') ?>">
        <span class="owner-avatar"><?= htmlspecialchars($ownerInitial, ENT_QUOTES, 'UTF-8') ?></span>
        <strong><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></strong>
      </a>
      <a class="owner-icon-button" href="<?= htmlspecialchars($ownerPageBase . '../shared/partials/logout.php', ENT_QUOTES, 'UTF-8') ?>" aria-label="Sign out" title="Sign out">
        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
      </a>
    </div>
  </header>
<?php else: ?>
<?php
$adminName = $_SESSION['name'] ?? 'Administrator';
$adminInitial = strtoupper(substr($adminName, 0, 1));
?>
<header class="topbar">
        <div class="topbar-start">
          <button
            class="icon-button mobile-menu-toggle"
            id="mobile-menu-toggle"
            type="button"
            aria-label="Open navigation"
            aria-expanded="false"
            aria-controls="admin-sidebar">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
          </button>
          <div class="breadcrumb"><span>Workspace</span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i><strong><?= htmlspecialchars($adminPageTitle ?? 'Overview', ENT_QUOTES, 'UTF-8') ?></strong></div>
        </div>
        <div class="topbar-actions">
          <div class="topbar-date"><i class="fa-regular fa-calendar" aria-hidden="true"></i><span id="current-date"><?= htmlspecialchars(date('D, M j'), ENT_QUOTES, 'UTF-8') ?></span></div>
          <a class="icon-button notification-button" href="notifications.php" aria-label="View notifications" title="View notifications">
            <i class="fa-regular fa-bell" aria-hidden="true"></i>
          </a>
          <span class="topbar-divider" aria-hidden="true"></span>
          <a class="topbar-user" href="../shared/partials/logout.php" aria-label="Sign out" title="Sign out">
            <span class="profile-avatar"><?= htmlspecialchars($adminInitial, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="topbar-user-name"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></span>
            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
          </a>
        </div>
      </header>
<?php endif; ?>