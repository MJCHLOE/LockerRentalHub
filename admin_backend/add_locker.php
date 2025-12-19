<?php
// Include database connection
require '../db/database.php';
require_once 'log_actions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Set current user ID for trigger functionality
$stmt = $conn->prepare("SET @current_user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $locker_id = $_POST['locker_id'];
    $size = $_POST['size'];
    $status = $_POST['status'];
    $price = $_POST['price_per_month']; // Keeping POST key same for frontend compat, logic changes below
    
    // Validate locker ID is unique
    $check_query = "SELECT locker_id FROM lockers WHERE locker_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $locker_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Locker ID already exists
        $_SESSION['error'] = "Locker ID already exists!";
        header("Location: ../admin/dashboard.php#lockers");
        exit();
    }
    
    // Insert new locker into database
    $query = "INSERT INTO lockers (locker_id, size, status, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssd", $locker_id, $size, $status, $price);
    
    if ($stmt->execute()) {
        $logger = new SystemLogger($conn);
        $logger->logAction(
            'Add Locker',
            "Added new locker {$locker_id} - Size: {$size}, Status: {$status}, Price: ₱{$price}",
            'locker',
            $locker_id
        );
        
        $_SESSION['success'] = "Locker added successfully!";
    } else {
        $_SESSION['error'] = "Error adding locker: " . $conn->error;
    }
    
    // Redirect back to dashboard
    header("Location: ../admin/dashboard.php#lockers");
    exit();
}
?>