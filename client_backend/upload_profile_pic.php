<?php
session_start();
require_once '../db/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $userId = $_SESSION['user_id'];
    $targetDir = "../profile_pics/"; // Adjusted to point to web root's profile_pics
    $targetFile = $targetDir . "user_{$userId}.jpg";
    
    // Check if the directory exists and is writable
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Check if the file is an image
    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
    if ($check !== false) {
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
            // Return web-accessible URL
            echo json_encode(['success' => true, 'newSrc' => "/profile_pics/user_{$userId}.jpg"]);
        } else {
            $errorCode = $_FILES["profile_pic"]["error"];
            echo json_encode(['success' => false, 'message' => "Error moving uploaded file. Code: $errorCode"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File is not an image']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}
?>