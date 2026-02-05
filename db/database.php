<?php
// Database connection
$host = "localhost";
$user = "u130348899_rentlocker";
$password = "RentLockerz_101"; 
$dbname = "u130348899_Locker_Rental";

$conn = new mysqli($host, $user, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>