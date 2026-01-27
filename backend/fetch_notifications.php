<?php
session_start();
require 'db/database.php';
require 'Notification.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$notification = new Notification($conn);
$user_id = $_SESSION['user_id'];

$unreadCount = $notification->countUnread($user_id);
$notifications = $notification->getAll($user_id);

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'notifications' => $notifications
]);
?>
