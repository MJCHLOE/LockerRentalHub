<?php
session_start();
require 'db/database.php';
require 'Notification.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$notification = new Notification($conn);
$user_id = $_SESSION['user_id'];

if (isset($data['mark_all']) && $data['mark_all'] == true) {
    if ($notification->markAllAsRead($user_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark all as read']);
    }
} elseif (isset($data['notification_id'])) {
    if ($notification->markAsRead($data['notification_id'], $user_id)) {
        echo json_encode(['success' => true]);
    } else {
         echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
}
?>
