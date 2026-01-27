<?php
require 'db/database.php';
require 'Notification.php';

header('Content-Type: text/plain');

echo "--- Admin Notification Debug Test ---\n";

// 1. Check DB Connection
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
echo "DB Connected.\n";

// 2. Check Admin Users Query
$sql = "SELECT user_id, username, role FROM users WHERE role IN ('Admin', 'admin', 'Administrator')";
$result = $conn->query($sql);

if (!$result) {
    echo "Query Error: " . $conn->error . "\n";
} else {
    echo "Found " . $result->num_rows . " Admin(s):\n";
    while ($row = $result->fetch_assoc()) {
        echo " - ID: {$row['user_id']} ({$row['username']}) [{$row['role']}]\n";
    }
}

// 3. Test Notification Class
echo "\nAttempting to send test notification via Notification class...\n";
$notify = new Notification($conn);
$success = $notify->notifyAdmins("Debug Test", "This is a test notification from the debugger.", "info");

if ($success) {
    echo "SUCCESS: notifyAdmins returned TRUE.\n";
    echo "Please check the Admin Dashboard bell icon.\n";
} else {
    echo "FAILURE: notifyAdmins returned FALSE.\n";
    echo "Possible reasons:\n";
    echo " - No users found with role 'Admin', 'admin', or 'Administrator'.\n";
    echo " - Database INSERT failed (check error log).\n";
}
?>
