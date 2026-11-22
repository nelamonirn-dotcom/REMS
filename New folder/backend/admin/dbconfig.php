<?php
// Connection with mySQL

$conn = new mysqli("localhost", "root", "", "estateflow_db");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
