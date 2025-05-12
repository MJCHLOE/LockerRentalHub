<?php
session_start();
require '../db/database.php';
require_once 'log_actions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $role = $_POST['role'];
    
    // Check if password should be updated
    $password_update = !empty($_POST['password']);
    
    try {
        // Fetch old user data first
        $stmt = $conn->prepare("SELECT firstname, lastname, email, phone_number, role FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $oldData = $stmt->get_result()->fetch_assoc();

        // Start transaction
        $conn->begin_transaction();
        
        // Update user basic info with new fields
        if ($password_update) {
            // Hash the new password
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, firstname = ?, lastname = ?, email = ?, phone_number = ?, role = ? WHERE user_id = ?");
            $stmt->bind_param("sssssssi", $username, $hashed_password, $firstname, $lastname, $email, $phone_number, $role, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, firstname = ?, lastname = ?, email = ?, phone_number = ?, role = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $username, $firstname, $lastname, $email, $phone_number, $role, $user_id);
        }
        
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Error updating user basic information");
        }
        
        // Get the current role from database before updating related tables
        $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_user = $result->fetch_assoc();
        $old_role = $current_user['role'];
        
        // If role has changed, update the role-specific tables
        if ($old_role !== $role) {
            $full_name = $firstname . ' ' . $lastname;
            
            // Remove from old role table
            if ($old_role === 'Admin') {
                $stmt = $conn->prepare("DELETE FROM admins WHERE user_id = ?");
            } elseif ($old_role === 'Staff') {
                $stmt = $conn->prepare("DELETE FROM staff WHERE user_id = ?");
            } else { // Client
                $stmt = $conn->prepare("DELETE FROM clients WHERE user_id = ?");
            }
            
            $stmt->bind_param("i", $user_id);
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("Error removing user from old role table");
            }
            
            // Add to new role table
            if ($role === 'Admin') {
                $stmt = $conn->prepare("INSERT INTO admins (user_id, full_name) VALUES (?, ?)");
            } elseif ($role === 'Staff') {
                $stmt = $conn->prepare("INSERT INTO staff (user_id, full_name) VALUES (?, ?)");
            } else { // Client
                $stmt = $conn->prepare("INSERT INTO clients (user_id, full_name) VALUES (?, ?)");
            }
            
            $stmt->bind_param("is", $user_id, $full_name);
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("Error adding user to new role table");
            }
        } else {
            // If role hasn't changed but name has, update the full_name in the respective table
            $full_name = $firstname . ' ' . $lastname;
            
            if ($role === 'Admin') {
                $stmt = $conn->prepare("UPDATE admins SET full_name = ? WHERE user_id = ?");
            } elseif ($role === 'Staff') {
                $stmt = $conn->prepare("UPDATE staff SET full_name = ? WHERE user_id = ?");
            } else { // Client
                $stmt = $conn->prepare("UPDATE clients SET full_name = ? WHERE user_id = ?");
            }
            
            $stmt->bind_param("si", $full_name, $user_id);
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("Error updating user's full name");
            }
        }
        
        // If all operations successful, commit transaction
        $conn->commit();

        $logger = new SystemLogger($conn);
        $logger->logAction(
            'Edit User',
            "Updated user {$oldData['firstname']} {$oldData['lastname']} to: {$firstname} {$lastname} (Role: {$role})",
            'user',
            $user_id
        );

        $success_message = "User updated successfully!";
        header("Location: ../admin/dashboard.php?success=" . urlencode($success_message));
        exit();
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
        header("Location: ../admin/dashboard.php?error=" . urlencode($error_message));
        exit();
    }
}

// If GET request, fetch and return user data for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Fetch user data
    $stmt = $conn->prepare("SELECT user_id, username, firstname, lastname,  email, phone_number, role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'user' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
    exit();
}

// If reached here, invalid request
header("Location: ../admin/dashboard.php");
exit();
?>