<?php
// Database connection
$host = "194.59.164.68";
$user = "u130348899_locker";
$password = "Lockerdummy12"; 
$dbname = "u130348899_lockerdummy";

$conn = new mysqli($host, $user, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>