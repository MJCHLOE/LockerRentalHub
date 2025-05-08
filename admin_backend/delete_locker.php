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

// Process delete request - Accept both GET and POST for flexibility
// Handle both parameter naming formats (locker_id and id) for compatibility
$locker_id = isset($_POST['locker_id']) ? $_POST['locker_id'] : 
             (isset($_GET['locker_id']) ? $_GET['locker_id'] : 
             (isset($_POST['id']) ? $_POST['id'] : 
             (isset($_GET['id']) ? $_GET['id'] : null)));

if ($locker_id) {
    // Check if locker is in use (has active rentals)
    $check_query = "SELECT COUNT(*) AS rental_count FROM rental WHERE locker_id = ? AND rental_status IN ('pending', 'approved')";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $locker_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();
    
    $response = array();
    
    if ($row['rental_count'] > 0) {
        // Locker has active rentals
        $response['success'] = false;
        $response['message'] = "Cannot delete locker with active rentals!";
    } else {
        // Delete the locker
        $query = "DELETE FROM lockerunits WHERE locker_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $locker_id);
        
        if ($stmt->execute()) {
            $logger = new SystemLogger($conn);
            $logger->logAction(
                'Delete Locker',
                "Deleted locker with ID: {$locker_id}",
                'locker',
                $locker_id
            );
            $response['success'] = true;
            $response['message'] = "Locker deleted successfully!";
        } else {
            $response['success'] = false;
            $response['message'] = "Error deleting locker: " . $conn->error;
        }
    }
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    // No locker ID provided
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No locker ID provided'
    ]);
    exit();
}
?>