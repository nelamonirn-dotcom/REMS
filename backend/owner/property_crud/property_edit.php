
<!-- database Connection -->
 <?php
session_start();
include_once __DIR__ . "/dbconfig.php";
// ?>   


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
        $ownerPageTitle = 'Edit Property';
        $ownerActivePage = '';
        include __DIR__ . '/../include/owner_leftber.php';
        ?>
        <div class="owner-main-shell">
            <?php include __DIR__ . '/../../shared/partials/header.php'; ?>
            <main class="container py-4">
    <h3>Student Update from</h3>

<?php
//Display Student Record
$id=$_GET['id'];




// Update student Record
if($_SERVER['REQUEST_METHOD']=='POST'){
    // data received from entry from
    $name = $_POST ['name'];
    $email = $_POST ['email'];
    $phone = $_POST ['phone'];
 
    
$result = $conn->query("UPDATE allstudents SET name = '$name', email ='$email', phone= '$phone' WHERE id = '$id'") ;

// Update student Record
// $result = $conn->query("INSERT INTO allstudents (id,name,email,phone) VALUES (NULL,'$name','$email','$phone')");


if ($conn->affected_rows){

echo " <div class = 'message'> Update success</div>";
}

}
$data = $conn->query("SELECT * FROM allstudents WHERE id = '$id' ");
$row= $data->fetch_object();

?>

    <form action="" method="post">
        <input type="text" name="name" placeholder="Enter your name" value="<?php echo $row->name;?>"><br>
        <input type="email" name="email" placeholder="Enter your email" value="<?php echo $row->email;?>"><br>
        <input type="text" name="phone" placeholder="Enter your  phone number" value="<?php echo $row->phone;?>"><br>
        <input type="submit" name="submit"  value="Update">
        
    </form>
    <br>
    <a href="index.php">Back to student list</a> <br><br>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>