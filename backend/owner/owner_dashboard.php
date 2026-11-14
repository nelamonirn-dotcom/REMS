<?php
session_start();

if (!isset($_SESSION['email'])) {
  header('Location: ../admin/index.php');
  exit;
}

if (($_SESSION['role'] ?? '') !== 'owner') {
  header('Location: ../admin/unauthorize.php');
  exit;
}

$ownerName = $_SESSION['name'] ?? 'Property Owner';
$ownerEmail = $_SESSION['email'] ?? '';
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');


?>
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#172d50">
  <meta name="description" content="Manage your properties, bookings, payments and maintenance in the EstateFlow owner workspace.">
  <title>Owner Dashboard | EstateFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/owner/dashboard.css">
</head>

<body class="owner-dashboard">
  <a class="owner-skip-link" href="#owner-main">Skip to dashboard</a>
  <div class="owner-layout">
    <?php include __DIR__ . '/../shared/partials/owner_leftber.php'; ?>

    <div class="owner-main-shell">
      <header class="owner-topbar">
        <div class="d-flex align-items-center gap-3">
          <button class="owner-menu-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#ownerSidebar" aria-controls="ownerSidebar" aria-label="Open navigation">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
          </button>
          <nav class="owner-breadcrumb" aria-label="Breadcrumb"><span>Owner workspace</span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i><strong>Overview</strong></nav>
        </div>
        <div class="owner-topbar-actions">
          <span class="owner-today"><i class="fa-regular fa-calendar" aria-hidden="true"></i><span id="owner-date"></span></span>
          <a class="owner-icon-button owner-notifications" href="#notifications" aria-label="View notifications, 3 unread">
            <i class="fa-regular fa-bell" aria-hidden="true"></i><span></span>
          </a>
          <span class="owner-topbar-divider"></span>
          <a class="owner-topbar-profile" href="#profile"><span class="owner-avatar"><?= $escape(strtoupper(substr($ownerName, 0, 1))) ?></span><strong><?= $escape($ownerName) ?></strong><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></a>
        </div>
      </header>

      <main class="owner-content" id="owner-main">
        <section class="owner-welcome" id="overview">
          <div>
            <p class="owner-eyebrow"><span></span> OWNER WORKSPACE</p>
            <h1><span id="owner-greeting">Welcome</span>, <?= $escape($ownerName) ?> <span aria-hidden="true">✦</span></h1>
            <p class="owner-subtitle">A clear view of your properties, payments, and what needs attention.</p>
          </div>
          <div class="owner-heading-actions">
            <span class="owner-preview-label"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Sample data preview</span>
            <a href="./property_crud/Addproperty.php" class="btn owner-primary-button"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add property</a>
          </div>
        </section>

        <div class="owner-preview-notice" role="note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>This dashboard uses sample records. Forms and uploads are previews and do not save to the database.</span></div>

        <section class="row g-3 owner-stat-grid" aria-label="Property portfolio summary">
          <div class="col-12 col-sm-6 col-xl-3"><article class="owner-stat-card"><div class="owner-stat-head"><span>Owned properties</span><span class="owner-stat-icon icon-blue"><i class="fa-solid fa-building" aria-hidden="true"></i></span></div><strong class="owner-stat-value">12</strong><div class="owner-stat-foot"><span class="stat-positive">8 available</span><span>in your portfolio</span></div></article></div>
          <div class="col-12 col-sm-6 col-xl-3"><article class="owner-stat-card"><div class="owner-stat-head"><span>Payments received</span><span class="owner-stat-icon icon-green"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span></div><strong class="owner-stat-value">৳ 3.42L</strong><div class="owner-stat-foot"><span class="stat-positive">This month</span><span>৳ 42,000 pending</span></div></article></div>
          <div class="col-12 col-sm-6 col-xl-3"><article class="owner-stat-card"><div class="owner-stat-head"><span>Active bookings</span><span class="owner-stat-icon icon-purple"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span></div><strong class="owner-stat-value">09</strong><div class="owner-stat-foot"><span class="stat-neutral">3 need review</span><span>assigned properties</span></div></article></div>
          <div class="col-12 col-sm-6 col-xl-3"><article class="owner-stat-card"><div class="owner-stat-head"><span>Open maintenance</span><span class="owner-stat-icon icon-amber"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i></span></div><strong class="owner-stat-value">02</strong><div class="owner-stat-foot"><span class="stat-attention">1 needs attention</span><span>open requests</span></div></article></div>
        </section>

        <section class="owner-section-heading" id="properties">
          <div><h2>My Properties</h2><p>Your owned properties and current listing status.</p></div>
          <a href="property_crud/myproperty.php">View all properties <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </section>

        <section class="row g-3 owner-property-grid" aria-label="Owned properties">
          <div class="col-12 col-md-6 col-xl-4"><article class="owner-property-card"><div class="property-image property-image-one" role="img" aria-label="Modern apartment exterior"><span class="property-type-pill">Apartment</span><span class="property-listing-status"><span></span> Rented</span></div><div class="property-card-body"><div class="property-card-heading"><div><h3>Lakeview Heights</h3><p><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Gulshan 2, Dhaka</p></div></div><div class="property-specs"><span><i class="fa-solid fa-bed" aria-hidden="true"></i> 3 beds</span><span><i class="fa-solid fa-bath" aria-hidden="true"></i> 2 baths</span><span><i class="fa-solid fa-vector-square" aria-hidden="true"></i> 1,450 sqft</span></div><div class="property-card-footer"><strong>৳ 85,000 <small>/ month</small></strong><a href="#property-images">Manage images</a></div></div></article></div>
          <div class="col-12 col-md-6 col-xl-4"><article class="owner-property-card"><div class="property-image property-image-two" role="img" aria-label="Contemporary residential apartment building"><span class="property-type-pill">Apartment</span><span class="property-listing-status status-available"><span></span> Available</span></div><div class="property-card-body"><div class="property-card-heading"><div><h3>Palm Grove Residence</h3><p><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Dhanmondi, Dhaka</p></div></div><div class="property-specs"><span><i class="fa-solid fa-bed" aria-hidden="true"></i> 2 beds</span><span><i class="fa-solid fa-bath" aria-hidden="true"></i> 2 baths</span><span><i class="fa-solid fa-vector-square" aria-hidden="true"></i> 1,180 sqft</span></div><div class="property-card-footer"><strong>৳ 62,000 <small>/ month</small></strong><a href="#property-images">Manage images</a></div></div></article></div>
          <div class="col-12 col-md-6 col-xl-4"><article class="owner-property-card"><div class="property-image property-image-three" role="img" aria-label="Bright modern apartment interior"><span class="property-type-pill">Condominium</span><span class="property-listing-status"><span></span> Rented</span></div><div class="property-card-body"><div class="property-card-heading"><div><h3>Cedar Court, Unit 3C</h3><p><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Banani, Dhaka</p></div></div><div class="property-specs"><span><i class="fa-solid fa-bed" aria-hidden="true"></i> 3 beds</span><span><i class="fa-solid fa-bath" aria-hidden="true"></i> 3 baths</span><span><i class="fa-solid fa-vector-square" aria-hidden="true"></i> 1,700 sqft</span></div><div class="property-card-footer"><strong>৳ 98,000 <small>/ month</small></strong><a href="#property-images">Manage images</a></div></div></article></div>
        </section>

       

        
        <section class="owner-module-section" id="bookings" aria-labelledby="bookings-title">
          <div class="owner-section-heading"><div><h2 id="bookings-title">Bookings</h2><p>Review booking requests for your properties.</p></div><span class="owner-section-count">4 requests</span></div>
          <article class="owner-panel owner-table-panel"><div class="owner-table-scroll"><table class="owner-data-table"><thead><tr><th>Customer</th><th>Property</th><th>Requested</th><th>Status</th><th>Action</th></tr></thead><tbody>
            <tr><td><strong>Nadia Karim</strong><small>nadia@example.com</small></td><td>Lakeview Heights</td><td>Nov 11</td><td><span class="owner-status status-pending">Pending</span></td><td><button class="owner-inline-action" type="button" data-preview-message="Booking decisions are a preview and are not saved. Connect booking actions to your backend.">Review</button></td></tr>
            <tr><td><strong>Imran Hossain</strong><small>imran@example.com</small></td><td>Palm Grove Residence</td><td>Nov 10</td><td><span class="owner-status status-review">In review</span></td><td><button class="owner-inline-action" type="button" data-preview-message="Booking details shown here are sample data.">View</button></td></tr>
            <tr><td><strong>Maliha Chowdhury</strong><small>maliha@example.com</small></td><td>Cedar Court, Unit 3C</td><td>Nov 08</td><td><span class="owner-status status-rented">Confirmed</span></td><td><button class="owner-inline-action" type="button" data-preview-message="Booking details shown here are sample data.">View</button></td></tr>
          </tbody></table></div></article>
        </section>

        <section class="owner-module-section" id="payments" aria-labelledby="payments-title">
          <div class="owner-section-heading"><div><h2 id="payments-title">Payments</h2><p>See received payments and pending balances.</p></div></div>
          <div class="owner-finance-summary"><article class="owner-panel"><small>Received this month</small><strong>৳ 3,42,000</strong><span>Across 8 properties</span></article><article class="owner-panel"><small>Pending payments</small><strong>৳ 42,000</strong><span>2 payments due</span></article></div>
          <article class="owner-panel owner-table-panel"><div class="owner-table-scroll"><table class="owner-data-table"><thead><tr><th>Payment</th><th>Property</th><th>Date due</th><th>Amount</th><th>Status</th></tr></thead><tbody>
            <tr><td><strong>Rent · November</strong><small>Nadia Karim</small></td><td>Lakeview Heights</td><td>Nov 05</td><td>৳ 85,000</td><td><span class="owner-status status-rented">Received</span></td></tr>
            <tr><td><strong>Rent · November</strong><small>Imran Hossain</small></td><td>Palm Grove Residence</td><td>Nov 15</td><td>৳ 62,000</td><td><span class="owner-status status-pending">Pending</span></td></tr>
            <tr><td><strong>Rent · November</strong><small>Maliha Chowdhury</small></td><td>Cedar Court, Unit 3C</td><td>Nov 03</td><td>৳ 98,000</td><td><span class="owner-status status-rented">Received</span></td></tr>
          </tbody></table></div></article>
        </section>
        <section class="owner-module-section" id="notifications" aria-labelledby="notifications-title">
          <div class="owner-section-heading"><div><h2 id="notifications-title">Notifications</h2><p>Booking and payment updates for your properties.</p></div></div>
          <article class="owner-panel owner-notification-list">
            <div class="owner-notification-item"><span class="activity-icon activity-blue"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span><div><strong>New booking request</strong><p>Nadia Karim submitted a request for Lakeview Heights.</p></div><small>Today · 9:15 AM</small></div>
            <div class="owner-notification-item"><span class="activity-icon activity-green"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span><div><strong>Payment received</strong><p>November rent payment received for Cedar Court.</p></div><small>Yesterday · 4:40 PM</small></div>
            <div class="owner-notification-item"><span class="activity-icon activity-amber"><i class="fa-solid fa-clock" aria-hidden="true"></i></span><div><strong>Payment due soon</strong><p>Palm Grove Residence rent is due on November 15.</p></div><small>Yesterday · 10:20 AM</small></div>
          </article>
        </section>

        <section class="owner-module-section" id="profile" aria-labelledby="profile-title">
          <div class="owner-section-heading"><div><h2 id="profile-title">Profile</h2><p>Manage your personal information.</p></div></div>
          <form class="owner-panel owner-profile-form" id="owner-profile-form">
            <label class="owner-form-field">Full name<input name="name" type="text" value="<?= $escape($ownerName) ?>" required></label>
            <label class="owner-form-field">Email address<input name="email" type="email" value="<?= $escape($ownerEmail) ?>" required></label>
            <label class="owner-form-field">Phone number<input name="phone" type="tel" placeholder="Add a phone number"></label>
            <div class="owner-form-actions owner-form-wide"><button class="btn owner-primary-button" type="submit"><i class="fa-regular fa-floppy-disk" aria-hidden="true"></i> Save profile</button><span class="owner-form-feedback" id="profile-feedback" role="status" aria-live="polite" hidden></span></div>
          </form>
        </section>

        <p class="owner-action-feedback" id="owner-action-feedback" role="status" aria-live="polite" hidden></p>
        <footer class="owner-footer"><span>EstateFlow <span aria-hidden="true">·</span> Owner workspace</span><span>Dashboard content is sample preview data.</span></footer>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
</body>

</html>
