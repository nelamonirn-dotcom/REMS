
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="../assets/css/owner/dashboard.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="owner-layout">
        <?php
        $ownerPageBase = '../';
        $ownerPageTitle = 'Delete Property';
        $ownerActivePage = '';
        include __DIR__ . '/../include/owner_leftber.php';
        ?>
        <div class="owner-main-shell">
            <?php include __DIR__ . '/../../shared/partials/header.php'; ?>
            <main class="container py-4">
    <h1>abc</h1>
    <?php
  $id = $_GET['id'];
include_once __DIR__ . "/dbconfig.php"; // database Connection

    $conn->query (" DELETE FROM allstudents WHERE id ='$id'");

    if ($conn->affected_rows){

header("Location: index.php");
}
        ?>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>