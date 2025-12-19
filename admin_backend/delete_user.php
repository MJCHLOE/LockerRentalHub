<?php
session_start();
require_once '../db/database.php'; 
require_once 'log_actions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}

// Validate request method and parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

// Get and validate user_id
$user_id = (int)$_POST['user_id'];
if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
    exit();
}

// Don't allow admins to delete themselves
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
    exit();
}

// Set error reporting to catch all errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly

try {
    // Get user's details before deletion
    $stmt = $conn->prepare("SELECT firstname, lastname, role FROM users WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }
    
    $userData = $result->fetch_assoc();
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if user has any active rentals
    $stmt = $conn->prepare("SELECT COUNT(*) as rental_count FROM rental WHERE user_id = ? AND rental_status NOT IN ('completed', 'cancelled', 'denied')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['rental_count'] > 0) {
        throw new Exception("Cannot delete user with active rentals");
    }
    
    // Logs deletion logic simplified - system_logs handles everything via user_id
    // Previous role-specific log deletion removed as tables are dropped
    
    
    // Delete all rentals associated with this user
    $stmt = $conn->prepare("DELETE FROM rental WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Error deleting rentals: " . $stmt->error);
    }
    
    // Delete any system logs associated with this user
    $stmt = $conn->prepare("DELETE FROM system_logs WHERE user_id = ? OR (entity_type = 'user' AND entity_id = ?)");
    $stmt->bind_param("is", $user_id, $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Error deleting system logs: " . $stmt->error);
    }
    
    // Role specific table deletions removed
    
    
    // Delete from users table
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error deleting user: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("No user was deleted. User may not exist.");
    }
    
    // Log this action using the current admin's ID
    $logger = new SystemLogger($conn);
    $logger->logAction(
        'Delete User',
        "Deleted {$userData['role']}: {$userData['firstname']} {$userData['lastname']}",
        'user',
        $user_id
    );
    
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
    
} catch (Exception $e) {
    // Roll back transaction on error
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log the error to server error log
    error_log('Delete user error: ' . $e->getMessage() . ' for user_id: ' . $user_id);
    
    // Send error message to client
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    
} finally {
    // Make sure to close the connection
    if (isset($stmt)) {
        $stmt->close();
    }
}
?>