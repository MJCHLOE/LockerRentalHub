<?php
session_start();
require_once '../db/database.php'; 
require_once 'log_actions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

// Process the delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Don't allow admins to delete themselves
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
        exit();
    }
    
    try {
        // Get user's details before deletion
        $stmt = $conn->prepare("SELECT firstname, lastname, role FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $userData = $stmt->get_result()->fetch_assoc();
        
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
        
        // Delete from role-specific table
        if ($userData['role'] === 'Admin') {
            $stmt = $conn->prepare("DELETE FROM admins WHERE user_id = ?");
        } elseif ($userData['role'] === 'Staff') {
            $stmt = $conn->prepare("DELETE FROM staff WHERE user_id = ?");
        } else { // Client
            $stmt = $conn->prepare("DELETE FROM clients WHERE user_id = ?");
        }
        
        $stmt->bind_param("i", $user_id);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Error deleting from role table");
        }
        
        // Delete from users table
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $logger = new SystemLogger($conn);
            $logger->logAction(
                'Delete User',
                "Deleted {$userData['role']}: {$userData['firstname']} {$userData['lastname']}",
                'user',
                $user_id
            );
            
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
        }
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
    exit();
}

// If reached here, invalid request
header("Location: ../admin/dashboard.php");
exit();
?>