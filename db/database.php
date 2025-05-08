<?php
// Database connection
$host = "localhost";
$user = "roou130348899_";
$password = "LockerRentThing_69"; 
$dbname = "u130348899_LockerRental";

$conn = new mysqli($host, $user, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}