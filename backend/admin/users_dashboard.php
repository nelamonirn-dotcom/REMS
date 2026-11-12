<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: index.php");
  exit;
}

if (($_SESSION['role'] ?? '') !== "admin") {
  header("Location: unauthorize.php");
  exit;
}

if($_SERVER['REQUEST_METHOD']=='POST'){
    // data received from entry from
    $title = $_POST ['title'];
    $property_type = $_POST ['property_type'];
    $status = $_POST ['status'];
    $address = $_POST ['address'];
    $status = $_POST ['status'];
    $status = $_POST ['status'];
    $status = $_POST ['status'];
    include_once("dbconfig.php"); // database Connection

$result = $conn->query("INSERT INTO allstudents (id,name,email,phone) VALUES (NULL,'$name','$email','$phone')");


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
  <meta
    name="description"
    content="EstateFlow administrator dashboard overview for properties, bookings, visits, and operations.">
       <!--start leftbar-->
  <?php include __DIR__ . '/../shared/partials/home_styles.php'; ?>
  <!--end leftbar-->
</head>

<body class="admin-dashboard">
  <a class="skip-link" href="#main-content">Skip to main content</a>
  <div class="admin-layout" id="admin-layout">
    <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
     <!--start leftbar-->
  <?php include __DIR__ . '/../shared/partials/admin_leftber.php'; ?>
  <!--end leftbar-->
    

    <div class="main-shell">
        <!--start leftbar-->
  <?php include __DIR__ . '/../shared/partials/header.php'; ?>
  <!--end leftbar-->

      <main class="dashboard-main" id="main-content">
        <section class="page-heading" id="overview" aria-labelledby="welcome-heading">
          <div>
            <p class="page-eyebrow"><span class="live-dot"></span> Admin workspace</p>
            <h1 id="welcome-heading"><span id="greeting">Wellcame,Admin</span>, <span class="wave" aria-hidden="true">✦</span></h1>
            <p class="page-subtitle">Here’s what’s happening across your properties today.</p>
          </div>
          <div class="heading-actions">
            <span class="sample-label"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Sample data preview</span>
            <button class="button button-primary" id="open-property-modal" type="button">
              <i class="fa-solid fa-plus" aria-hidden="true"></i>

              <span>Add property</span>
            </button>
          </div>
        </section>

        <section class="stat-grid" aria-label="Portfolio overview">
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Total properties</span><span class="stat-icon stat-icon-blue"><i class="fa-solid fa-building" aria-hidden="true"></i></span></div>
            <div class="stat-value">248</div>
            <div class="stat-foot"><span class="trend trend-positive"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i> 12.8%</span><span>vs. last month</span></div>
            <span class="stat-spark spark-blue" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Monthly revenue</span><span class="stat-icon stat-icon-green"><i class="fa-solid fa-coins" aria-hidden="true"></i></span></div>
            <div class="stat-value">৳48.2<span class="stat-unit">L</span></div>
            <div class="stat-foot"><span class="trend trend-positive"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i> 8.2%</span><span>vs. last month</span></div>
            <span class="stat-spark spark-green" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Active bookings</span><span class="stat-icon stat-icon-purple"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span></div>
            <div class="stat-value">18</div>
            <div class="stat-foot"><span class="trend trend-positive"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i> 4.6%</span><span>vs. last month</span></div>
            <span class="stat-spark spark-purple" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
          <article class="stat-card">
            <div class="stat-top"><span class="stat-label">Pending requests</span><span class="stat-icon stat-icon-amber"><i class="fa-regular fa-clock" aria-hidden="true"></i></span></div>
            <div class="stat-value">07</div>
            <div class="stat-foot"><span class="trend trend-attention"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Needs review</span><span>3 new today</span></div>
            <span class="stat-spark spark-amber" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
          </article>
        </section>

       

        
            <!--start leftbar-->
  <?php include __DIR__ . '/../shared/partials/footer.php'; ?>
  <!--end leftbar-->
      </main>
    </div>
  </div>

  <dialog class="property-dialog" id="property-dialog" aria-labelledby="property-dialog-title">
    <form class="property-form" id="property-form" method="post">
      <div class="dialog-heading">
        <div><span class="dialog-icon"><i class="fa-solid fa-house-circle-check" aria-hidden="true"></i></span>
          <h2 id="property-dialog-title">Add a property</h2>
          <p>Complete the details that match the property record.</p>
        </div>
        <button class="icon-button dialog-close" id="close-property-modal" type="button" aria-label="Close dialog"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </div>
      <div class="form-grid">
        <label class="form-field form-field-wide" for="property-title">Title
          <input id="property-title" name="title" type="text" maxlength="100" placeholder="e.g. Lakeview Heights" required>
        </label>
        <label class="form-field" for="property-form-type">Property type
          <select id="property-form-type" name="property_type" required>
            <option value="">Select type</option>
            <option value="flat">Flat</option>
            <option value="land">Land</option>
            <option value="commercial">Commercial</option>
          </select>
        </label>
        <label class="form-field" for="property-status">Status
          <select id="property-status" name="status" required>
            <option value="available">Available</option>
            <option value="sold">Sold</option>
            <option value="rented">Rented</option>
          </select>
        </label>
        <label class="form-field form-field-wide" for="property-address">Address
          <input id="property-address" name="address" type="text" maxlength="255" placeholder="Area, city, and street address" required>
        </label>
        <label class="form-field" for="property-owner">Property owner
          <input id="property-owner" name="owner_id" type="number" min="1" step="1" placeholder="Owner user ID" required>
        </label>
        <label class="form-field" for="property-area">Area (sq ft)
          <input id="property-area" name="area_sqft" type="number" min="0" step="0.01" placeholder="e.g. 1450">
        </label>
        <label class="form-field" for="property-price">Sale price (৳)
          <input id="property-price" name="price" type="number" min="0" step="0.01" placeholder="Optional">
        </label>
        <label class="form-field" for="property-rent">Monthly rent (৳)
          <input id="property-rent" name="monthly_rent" type="number" min="0" step="0.01" placeholder="Optional">
        </label>
        <label class="form-field" for="property-bedrooms">Bedrooms
          <input id="property-bedrooms" name="bedrooms" type="number" min="0" step="1" placeholder="Optional">
        </label>
        <label class="form-field" for="property-bathrooms">Bathrooms
          <input id="property-bathrooms" name="bathrooms" type="number" min="0" step="1" placeholder="Optional">
        </label>
        <label class="form-field form-field-wide" for="property-description">Description
          <textarea id="property-description" name="description" rows="4" maxlength="65535" placeholder="Describe the property"></textarea>
        </label>
      </div>
      <p class="form-notice" id="property-form-notice" role="status" hidden></p>
      <div class="dialog-actions"><button class="button button-quiet" id="cancel-property-modal" type="button">Cancel</button><button class="button button-primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Preview fields</button></div>
    </form>
  </dialog>
</body>

</html>