<?php
if (!isset($managementPage) || !is_array($managementPage)) {
  header('Location: users_dashboard.php');
  exit;
}

session_start();
if (!isset($_SESSION['email'])) {
  header('Location: index.php');
  exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: unauthorize.php');
  exit;
}

$adminPageTitle = $managementPage['title'];
$escape = static function ($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$statusClasses = [
  'Active' => 'success',
  'Confirmed' => 'success',
  'Paid' => 'success',
  'Completed' => 'success',
  'Delivered' => 'success',
  'Approved' => 'success',
  'Pending' => 'pending',
  'In progress' => 'pending',
  'Scheduled' => 'pending',
  'Draft' => 'neutral',
  'Read' => 'neutral',
  'Review' => 'pending',
  'Overdue' => 'danger',
  'Cancelled' => 'danger',
  'Inactive' => 'danger',
  'Unread' => 'info',
  'New' => 'info',
];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#101d35">
  <meta name="description" content="<?= $escape($managementPage['description']) ?>">
  <title><?= $escape($managementPage['title']) ?> | EstateFlow Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../shared/assets/css/global.css">
  <link rel="stylesheet" href="assets/css/admin/dashboard.css">
  <link rel="stylesheet" href="assets/css/admin/management.css">
</head>

<body class="admin-dashboard">
  <a class="skip-link" href="#main-content">Skip to main content</a>
  <div class="admin-layout" id="admin-layout">
    <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
    <?php include __DIR__ . '/../shared/partials/admin_leftber.php'; ?>

    <div class="main-shell">
      <?php include __DIR__ . '/../shared/partials/header.php'; ?>

      <main class="dashboard-main" id="main-content">
        <section class="page-heading management-heading" aria-labelledby="management-title">
          <div>
            <p class="page-eyebrow"><span class="live-dot"></span> <?= $escape($managementPage['category']) ?></p>
            <h1 id="management-title"><?= $escape($managementPage['title']) ?></h1>
            <p class="page-subtitle"><?= $escape($managementPage['description']) ?></p>
          </div>
          <span class="sample-label"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Sample data preview</span>
        </section>

        <section class="row g-3 management-stats" aria-label="<?= $escape($managementPage['title']) ?> summary">
          <?php foreach ($managementPage['stats'] as $stat) { ?>
            <div class="col-12 col-sm-6 col-xl-3">
              <article class="management-stat-card">
                <span class="management-stat-icon <?= $escape($stat['color']) ?>"><i class="<?= $escape($stat['icon']) ?>" aria-hidden="true"></i></span>
                <p><?= $escape($stat['label']) ?></p>
                <strong><?= $escape($stat['value']) ?></strong>
                <small><?= $escape($stat['note']) ?></small>
              </article>
            </div>
          <?php } ?>
        </section>

        <?php if (($managementPage['kind'] ?? '') === 'analytics') { ?>
          <section class="analytics-panel" aria-labelledby="analytics-chart-title">
            <div class="analytics-chart-card">
              <div class="analytics-heading">
                <div>
                  <h2 id="analytics-chart-title"><?= $escape($managementPage['chartTitle']) ?></h2>
                  <p><?= $escape($managementPage['chartDescription']) ?></p>
                </div>
                <span class="analytics-period">Last 6 months <i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span>
              </div>
              <div class="analytics-chart" role="img" aria-label="<?= $escape($managementPage['chartTitle']) ?> over the last six months">
                <?php foreach ($managementPage['chartBars'] as $bar) { ?>
                  <div class="analytics-bar-group">
                    <span class="analytics-bar-value"><?= $escape($bar['value']) ?></span>
                    <div class="analytics-bar-track"><span style="height: <?= $escape($bar['height']) ?>"></span></div>
                    <span class="analytics-bar-label"><?= $escape($bar['label']) ?></span>
                  </div>
                <?php } ?>
              </div>
            </div>
            <div class="analytics-breakdown-card">
              <h2>Portfolio mix</h2>
              <p>Property occupancy by category</p>
              <?php foreach ($managementPage['breakdown'] as $item) { ?>
                <div class="analytics-breakdown">
                  <div><span class="analytics-key <?= $escape($item['color']) ?>"></span><?= $escape($item['label']) ?><strong><?= $escape($item['value']) ?>%</strong></div>
                  <div class="analytics-progress"><span class="<?= $escape($item['color']) ?>" style="width: <?= $escape($item['value']) ?>%"></span></div>
                </div>
              <?php } ?>
            </div>
          </section>
        <?php } ?>

        <?php if (($managementPage['kind'] ?? '') === 'settings') { ?>
          <section class="management-panel settings-panel" aria-labelledby="settings-panel-title">
            <div class="management-panel-heading">
              <div>
                <h2 id="settings-panel-title">Workspace preferences</h2>
                <p>Review the current workspace defaults.</p>
              </div>
              <button class="btn btn-primary management-action" type="button" disabled>Save changes</button>
            </div>
            <div class="settings-grid">
              <?php foreach ($managementPage['settings'] as $setting) { ?>
                <article class="setting-card">
                  <div>
                    <h3><?= $escape($setting['title']) ?></h3>
                    <p><?= $escape($setting['description']) ?></p>
                  </div>
                  <?php if ($setting['control'] === 'toggle') { ?>
                    <span class="setting-toggle is-on" aria-label="<?= $escape($setting['value']) ?>"><span></span></span>
                  <?php } else { ?>
                    <span class="setting-value"><?= $escape($setting['value']) ?></span>
                  <?php } ?>
                </article>
              <?php } ?>
            </div>
            <p class="management-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Settings are shown as a UI preview and are not saved.</p>
          </section>
        <?php } else { ?>
          <section class="management-panel" aria-labelledby="management-list-title">
            <div class="management-panel-heading">
              <div>
                <h2 id="management-list-title"><?= $escape($managementPage['listTitle']) ?></h2>
                <p><?= $escape($managementPage['listDescription']) ?></p>
              </div>
              <button class="btn btn-outline-secondary management-action" type="button">
                <i class="fa-solid fa-arrow-down-to-line me-2" aria-hidden="true"></i>Export
              </button>
            </div>
            <div class="management-toolbar">
              <label class="management-search" for="management-search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <span class="visually-hidden">Search <?= $escape(strtolower($managementPage['title'])) ?></span>
                <input id="management-search" class="form-control" type="search" placeholder="Search <?= $escape(strtolower($managementPage['title'])) ?>">
              </label>
              <button class="btn btn-outline-secondary management-action" type="button">
                <i class="fa-solid fa-filter me-2" aria-hidden="true"></i>Filters
              </button>
            </div>
            <div class="table-responsive">
              <table class="table management-table align-middle mb-0">
                <thead>
                  <tr>
                    <?php foreach ($managementPage['columns'] as $column) { ?>
                      <th scope="col"><?= $escape($column['label']) ?></th>
                    <?php } ?>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($managementPage['rows'] as $row) { ?>
                    <tr>
                      <?php foreach ($managementPage['columns'] as $column) {
                        $cell = $row[$column['key']];
                        ?>
                        <td>
                          <?php if (($column['type'] ?? '') === 'status') {
                            $statusClass = $statusClasses[$cell] ?? 'neutral';
                            ?>
                            <span class="management-status status-<?= $escape($statusClass) ?>"><span></span><?= $escape($cell) ?></span>
                          <?php } else { ?>
                            <?= $escape($cell) ?>
                          <?php } ?>
                        </td>
                      <?php } ?>
                      <td><button class="management-more" type="button" aria-label="More actions for <?= $escape(reset($row)) ?>"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i></button></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <div class="management-table-footer">
              <span>Showing <strong>1–<?= $escape(count($managementPage['rows'])) ?></strong> sample records</span>
              <nav aria-label="<?= $escape($managementPage['title']) ?> pages">
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item disabled"><span class="page-link" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></span></li>
                  <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
                  <li class="page-item"><span class="page-link">2</span></li>
                  <li class="page-item"><span class="page-link">3</span></li>
                  <li class="page-item"><span class="page-link">...</span></li>
                  <li class="page-item"><span class="page-link"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span></li>
                </ul>
              </nav>
            </div>
          </section>
        <?php } ?>

        <footer class="management-footer">
          <span>EstateFlow Admin <span aria-hidden="true">·</span> Administrator workspace</span>
          <span>All information on this page is sample preview content.</span>
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

      menuButton.addEventListener('click', () => setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true'));
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
