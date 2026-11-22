<?php 
// cannection with mySQL

$host = "localhost";
$user = "root";
$pass = "";
$db = "estateflow_db";
$conn = new mysqli ($host, $user,$pass,$db);
if(!$conn){
    die("Database connection failed:" . mysqli_connect_error());
}


?>