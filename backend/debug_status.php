<?php
// backend/debug_status.php
session_start();
require 'db/database.php';
header('Content-Type: text/plain');

echo "--- SESSION DEBUG ---\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . "\n";

if (!isset($_SESSION['user_id'])) {
    die("\nNO USER LOGGED IN (Session empty or expired). Fetch would fail.");
}

$user_id = $_SESSION['user_id'];
echo "\n--- DB NOTIFICATION CHECK ---\n";
echo "Checking notifications for User ID: $user_id\n";

if ($conn->connect_error) {
    die("DB Connect Error: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
echo "Total Notifications: " . $res['count'] . "\n";

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Latest 3 Notifications:\n";
    while($row = $result->fetch_assoc()) {
        echo "- [{$row['created_at']}] {$row['title']}: {$row['message']}\n";
    }
} else {
    echo "No notifications found in database for this user.\n";
}
?>
