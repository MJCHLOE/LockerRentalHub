<?php
session_start();
require '../db/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$uploadDir = '../client/profile_pics/';

// Create directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_pic'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF allow.']);
        exit;
    }
    
    // Generate unique filename to avoid caching issues
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    // Delete old profile pics for this user
    $files = glob($uploadDir . 'user_' . $userId . '_*');
    foreach ($files as $f) {
        if (is_file($f)) unlink($f);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Update database
        $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
        $stmt->bind_param("si", $filename, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['profile_pic'] = $filename; // Update session
            echo json_encode([
                'success' => true, 
                'message' => 'Upload successful',
                'newSrc' => '../client/profile_pics/' . $filename,
                'fullPath' => $targetPath
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
}

$conn->close();
?>