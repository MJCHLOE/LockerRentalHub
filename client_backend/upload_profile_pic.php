<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['profile_pic'];
$userId = $_SESSION['user_id'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$fileType = $file['type'];

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
    exit();
}

// Validate file size (5MB max)
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.']);
    exit();
}

// Define upload directory and ensure it exists
$uploadDir = realpath("../") . "/profile_pics";

if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit();
    }
    chmod($uploadDir, 0777);
}

// Define file path - always save as .jpg for consistency
$fileName = "user_{$userId}.jpg";
$filePath = $uploadDir . "/" . $fileName;

// Process and resize image
try {
    // Get image info
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid image file']);
        exit();
    }

    // Create image resource based on type
    switch ($imageInfo[2]) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($file['tmp_name']);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($file['tmp_name']);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unsupported image type']);
            exit();
    }

    if ($sourceImage === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to process image']);
        exit();
    }

    // Get original dimensions
    $originalWidth = imagesx($sourceImage);
    $originalHeight = imagesy($sourceImage);

    // Calculate new dimensions (square crop)
    $size = min($originalWidth, $originalHeight);
    $x = ($originalWidth - $size) / 2;
    $y = ($originalHeight - $size) / 2;

    // Create new image (300x300 for profile pics)
    $newSize = 300;
    $newImage = imagecreatetruecolor($newSize, $newSize);

    // Handle transparency for PNG/GIF
    if ($imageInfo[2] == IMAGETYPE_PNG || $imageInfo[2] == IMAGETYPE_GIF) {
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
        imagesavealpha($newImage, true);
    }

    // Crop and resize
    imagecopyresampled(
        $newImage, $sourceImage,
        0, 0, $x, $y,
        $newSize, $newSize, $size, $size
    );

    // Save as JPEG
    $success = imagejpeg($newImage, $filePath, 90);

    // Clean up memory
    imagedestroy($sourceImage);
    imagedestroy($newImage);

    if ($success) {
        // Set proper permissions
        chmod($filePath, 0644);
        
        // Update user profile_pic in database
        require_once '../db/database.php';
        $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
        $stmt->bind_param("si", $fileName, $userId);
        $stmt->execute();
        $stmt->close();

        // Update session
        $_SESSION['profile_pic'] = $fileName;
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Profile picture updated successfully',
            'newSrc' => "/profile_pics/" . $fileName,
            'fullPath' => $filePath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing image: ' . $e->getMessage()]);
}
?>