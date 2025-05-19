<?php
session_start();
require_once '../db/database.php';

header('Content-Type: application/json');

// Enable error logging
error_log("Profile upload process started");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $userId = $_SESSION['user_id'];
    
    // Get the absolute path to ensure we're writing to the correct location
    $targetDir = realpath("..") . "/profile_pics/";
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            error_log("Failed to create directory: $targetDir");
            echo json_encode(['success' => false, 'message' => "Failed to create directory"]);
            exit;
        }
        chmod($targetDir, 0777); // Make sure the directory is writable
    }
    
    $targetFile = $targetDir . "user_{$userId}.jpg";
    error_log("Target file path: $targetFile");
    
    // Check if there was an upload error
    if ($_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = uploadErrorMessage($_FILES['profile_pic']['error']);
        error_log("Upload error: $errorMsg");
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }
    
    // Check if the file is an image
    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
    if ($check !== false) {
        // Try to move the uploaded file
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
            // Make the file readable by the web server
            chmod($targetFile, 0644);
            
            error_log("File uploaded successfully to: $targetFile");
            
            // Return the relative URL that the browser will use to display the image
            $relativeUrl = "/profile_pics/user_{$userId}.jpg";
            echo json_encode(['success' => true, 'newSrc' => $relativeUrl, 'fullPath' => $targetFile]);
        } else {
            $errorCode = $_FILES["profile_pic"]["error"];
            $tmpName = $_FILES["profile_pic"]["tmp_name"];
            error_log("Failed to move file from $tmpName to $targetFile");
            error_log("Current permissions on target directory: " . substr(sprintf('%o', fileperms($targetDir)), -4));
            echo json_encode(['success' => false, 'message' => "Error moving uploaded file. Code: $errorCode"]);
        }
    } else {
        error_log("File is not an image");
        echo json_encode(['success' => false, 'message' => 'File is not an image']);
    }
} else {
    error_log("No file uploaded or wrong request method");
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}

// Helper function to translate error codes to messages
function uploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form";
        case UPLOAD_ERR_PARTIAL:
            return "The uploaded file was only partially uploaded";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION:
            return "A PHP extension stopped the file upload";
        default:
            return "Unknown upload error";
    }
}
?>