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
          <div class="topbar-date"><i class="fa-regular fa-calendar" aria-hidden="true"></i><span id="current-date">Today</span></div>
          <a class="icon-button notification-button" href="notifications.php" aria-label="Notifications, 3 unread" title="View notifications">
            <i class="fa-regular fa-bell" aria-hidden="true"></i><span class="notification-dot"></span>
          </a>
          <span class="topbar-divider" aria-hidden="true"></span>
          <a class="topbar-user" href="../shared/partials/logout.php" aria-label="Sign out" title="Sign out">
            <span class="profile-avatar"></span><span class="topbar-user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8') ?></span><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
          </a>
        </div>
      </header>