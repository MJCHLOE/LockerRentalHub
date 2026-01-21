<?php
require 'db/database.php';

// Add profile_pic column to users table if it doesn't exist
$sql = "SHOW COLUMNS FROM users LIKE 'profile_pic'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $alterSql = "ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT 'default_profile.jpg'";
    if ($conn->query($alterSql) === TRUE) {
        echo "Successfully added 'profile_pic' column to 'users' table.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "'profile_pic' column already exists.<br>";
}

// Check rentals table name
$checkTable = "SHOW TABLES LIKE 'rentals'";
$resultTable = $conn->query($checkTable);
if ($resultTable->num_rows > 0) {
    echo "Table 'rentals' exists.<br>";
} else {
    echo "Table 'rentals' does NOT exist. Checking for 'rental'...<br>";
    $checkRental = "SHOW TABLES LIKE 'rental'";
    $resultRental = $conn->query($checkRental);
    if ($resultRental->num_rows > 0) {
        echo "Table 'rental' exists (singular).<br>";
    } else {
        echo "Neither 'rentals' nor 'rental' table exists!<br>";
    }
}

$conn->close();
?>
