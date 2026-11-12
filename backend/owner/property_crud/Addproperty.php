<?php
session_start();
include_once 'dbconfig.php';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  // data received from entry form
  $owner_id = $_SESSION['user_id'];
  $title = $_POST['title'];
  $property_type = $_POST['property_type'];
  $area_sqft = $_POST['area_sqft'];
  $address = $_POST['address'];
  $price = $_POST['price'];
  $monthly_rent = $_POST['monthly_rent'];
  $bedrooms = $_POST['bedrooms'];
  $bathrooms = $_POST['bathrooms'];
  $status = $_POST['status'];
  $description = $_POST['description'];

  

  $result = $conn->query("INSERT INTO properties (owner_id,title,property_type,area_sqft,address,price,monthly_rent,bedrooms,bathrooms,status,description) VALUES ('$owner_id','$title','$property_type','$area_sqft','$address','$price','$monthly_rent','$bedrooms','$bathrooms','$status','$description')");

  if ($conn->affected_rows) {
    header('Location: ../owner_dashboard.php');
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/owner/add-property.css">
</head>
<body class="add-property-page">
    <main class="container py-5">
        <div class="card property-card mx-auto">
            <div class="property-card-header card-body p-4 p-md-5 pb-4">
                <a href="index.php" class="link-secondary text-decoration-none small">&larr; Back to property list</a>
                <h1 class="h3 mt-3 mb-2">Add New Property</h1>
                <p class="text-secondary mb-0">Enter the property details below.</p>
            </div>

            <div class="card-body p-4 p-md-5 pt-4">
                <form action="" method="post">
                    <div class="row g-4">
                        

                        <div class="col-12">
                            <label for="title" class="form-label">Property title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" maxlength="100" placeholder="e.g. Modern 3-bedroom apartment" required>
                        </div>

                        <div class="col-md-6">
                            <label for="property_type" class="form-label">Property type <span class="text-danger">*</span></label>
                            <select class="form-select" id="property_type" name="property_type" required>
                                <option value="" selected disabled>Select property type</option>
                                <option value="flat">Flat</option>
                                <option value="land">Land</option>
                                <option value="commercial">Commercial</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="area_sqft" class="form-label">Area (sq. ft.)</label>
                            <input type="number" class="form-control" id="area_sqft" name="area_sqft" min="0" step="0.01" placeholder="e.g. 1200">
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address" name="address" maxlength="255" placeholder="Street, area, city" required>
                        </div>

                        <div class="col-md-6">
                            <label for="price" class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" placeholder="Enter price">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="monthly_rent" class="form-label">Monthly rent</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" class="form-control" id="monthly_rent" name="monthly_rent" min="0" step="0.01" placeholder="Enter monthly rent">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="bedrooms" class="form-label">Bedrooms</label>
                            <input type="number" class="form-control" id="bedrooms" name="bedrooms" min="0" step="1" placeholder="Number of bedrooms">
                        </div>

                        <div class="col-md-6">
                            <label for="bathrooms" class="form-label">Bathrooms</label>
                            <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="0" step="1" placeholder="Number of bathrooms">
                        </div>

                        <div class="col-12">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available" selected>Available</option>
                                <option value="sold">Sold</option>
                                <option value="rented">Rented</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Add more details about the property"></textarea>
                        </div>

                        <div class="col-12 d-flex flex-column flex-sm-row justify-content-end gap-2 pt-2">
                            <a href="" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                            <button type="submit" name="submit" class="btn btn-primary px-4 py-2">Save Property</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
