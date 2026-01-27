<?php
require 'db/database.php';

header('Content-Type: text/plain');

echo "Checking Database Config...\n";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully.\n\n";

echo "Checking 'notifications' table...\n";
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows == 0) {
    echo "ERROR: Table 'notifications' does NOT exist!\n";
    echo "Attempting to create it now...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(20) DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "SUCCESS: Table 'notifications' created successfully.\n";
    } else {
        echo "ERROR: Error creating table: " . $conn->error . "\n";
    }
} else {
    echo "SUCCESS: Table 'notifications' exists.\n";
    echo "Columns:\n";
    $cols = $conn->query("SHOW COLUMNS FROM notifications");
    while($row = $cols->fetch_assoc()) {
        echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

echo "\nChecking Admin Users...\n";
$admins = $conn->query("SELECT user_id, username, role FROM users WHERE role IN ('Admin', 'admin', 'Administrator')");
echo "Found " . $admins->num_rows . " Admin(s):\n";
while($row = $admins->fetch_assoc()) {
    echo " - ID: " . $row['user_id'] . ", User: " . $row['username'] . ", Role: " . $row['role'] . "\n";
}
?>
