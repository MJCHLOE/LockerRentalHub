<?php
// Database connection
$host = "localhost";
$user = "root";
$password = ""; 
$dbname = "lockerrentalhub";

$conn = new mysqli($host, $user, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}