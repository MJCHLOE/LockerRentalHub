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
    $size_id = $_POST['size_id'];
    $status_id = $_POST['status_id'];
    $price_per_month = $_POST['price_per_month'];
    
    // Validate locker ID is unique
    $check_query = "SELECT locker_id FROM lockerunits WHERE locker_id = ?";
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
    $query = "INSERT INTO lockerunits (locker_id, size_id, status_id, price_per_month) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siid", $locker_id, $size_id, $status_id, $price_per_month);
    
    if ($stmt->execute()) {
        // Fetch size name and status name
        $size_query = "SELECT size_name FROM lockersizes WHERE size_id = ?";
        $status_query = "SELECT status_name FROM lockerstatuses WHERE status_id = ?";
        
        $size_stmt = $conn->prepare($size_query);
        $size_stmt->bind_param("i", $size_id);
        $size_stmt->execute();
        $size_result = $size_stmt->get_result();
        $size_name = $size_result->fetch_assoc()['size_name'];
        
        $status_stmt = $conn->prepare($status_query);
        $status_stmt->bind_param("i", $status_id);
        $status_stmt->execute();
        $status_result = $status_stmt->get_result();
        $status_name = $status_result->fetch_assoc()['status_name'];
        
        $logger = new SystemLogger($conn);
        $logger->logAction(
            'Add Locker',
            "Added new locker {$locker_id} - Size: {$size_name}, Status: {$status_name}, Price: ₱{$price_per_month}",
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