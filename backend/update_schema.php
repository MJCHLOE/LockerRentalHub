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
    
    // Check for end_date column
    $checkCol = "SHOW COLUMNS FROM rentals LIKE 'end_date'";
    $resultCol = $conn->query($checkCol);
    if ($resultCol->num_rows == 0) {
        $alterSql = "ALTER TABLE rentals ADD COLUMN end_date DATETIME NULL AFTER rental_date";
        if ($conn->query($alterSql) === TRUE) {
             echo "Successfully added 'end_date' column to 'rentals' table.<br>";
        } else {
             echo "Error adding 'end_date' column: " . $conn->error . "<br>";
        }
    } else {
        echo "'end_date' column already exists in 'rentals'.<br>";
    }

} else {
    echo "Table 'rentals' does NOT exist.<br>";
}

// Create notifications table
$checkNotif = "SHOW TABLES LIKE 'notifications'";
$resultNotif = $conn->query($checkNotif);
if ($resultNotif->num_rows == 0) {
    $createSql = "CREATE TABLE notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    if ($conn->query($createSql) === TRUE) {
        echo "Successfully created 'notifications' table.<br>";
    } else {
        echo "Error creating 'notifications' table: " . $conn->error . "<br>";
    }
} else {
    echo "'notifications' table already exists.<br>";
}

$conn->close();
?>
