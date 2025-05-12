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
        
        // First delete all logs associated with this user
        // Delete from admin_logs if admin
        if ($userData['role'] === 'Admin') {
            // Get admin_id
            $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                
                // Delete from admin_logs
                $stmt = $conn->prepare("DELETE FROM admin_logs WHERE admin_id = ?");
                $stmt->bind_param("i", $admin['admin_id']);
                $stmt->execute();
            }
        }
        // Delete from staff_logs if staff
        elseif ($userData['role'] === 'Staff') {
            // Get staff_id
            $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $staff = $result->fetch_assoc();
                
                // Delete from staff_logs
                $stmt = $conn->prepare("DELETE FROM staff_logs WHERE staff_id = ?");
                $stmt->bind_param("i", $staff['staff_id']);
                $stmt->execute();
            }
        }
        // Delete from client_logs if client
        else {
            // Get client_id
            $stmt = $conn->prepare("SELECT client_id FROM clients WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $client = $result->fetch_assoc();
                
                // Delete from client_logs
                $stmt = $conn->prepare("DELETE FROM client_logs WHERE client_id = ?");
                $stmt->bind_param("i", $client['client_id']);
                $stmt->execute();
            }
        }
        
        // Delete all rentals associated with this user
        $stmt = $conn->prepare("DELETE FROM rental WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete any system logs associated with this user
        $stmt = $conn->prepare("DELETE FROM system_logs WHERE user_id = ? OR (entity_type = 'user' AND entity_id = ?)");
        $stmt->bind_param("is", $user_id, $user_id);
        $stmt->execute();
        
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
            throw new Exception("Error deleting from role table: " . $conn->error);
        }
        
        // Delete from users table
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
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
        } else {
            throw new Exception("Error deleting user: " . $conn->error);
        }
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } finally {
        // Make sure to close the connection
        if (isset($stmt)) {
            $stmt->close();
        }
    }
    
    exit();
}

// If reached here, show invalid request message
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit();
?>