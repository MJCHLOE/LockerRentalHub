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
    $username = trim($_POST['username']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = isset($_POST['email']) && !empty($_POST['email']) ? trim($_POST['email']) : null;
    $phone_number = isset($_POST['phone_number']) && !empty($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
    $role = $_POST['role'];
    
    // Check if password should be updated
    $password_update = !empty($_POST['password']);
    
    try {
        // Fetch old user data first
        $stmt = $conn->prepare("SELECT username, firstname, lastname, email, phone_number, role FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $oldData = $stmt->get_result()->fetch_assoc();

        if (!$oldData) {
            throw new Exception("User not found");
        }

        // Validate input fields
        if (empty($username) || empty($firstname) || empty($lastname) || empty($email)) {
            throw new Exception("All required fields must be filled out");
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if the username is being changed and if so, if it's already taken
        if ($username !== $oldData['username']) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->bind_param("si", $username, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Username already exists. Please choose a different username.");
            }
        }

        // Check if the email is being changed and if so, if it's already taken
        if ($email !== $oldData['email']) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Email already in use by another account.");
            }
        }

        // Start transaction
        $conn->begin_transaction();
        
        // Update user basic info with new fields
        if ($password_update) {
            // Validate password strength if needed
            if (strlen($_POST['password']) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
            
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
            throw new Exception("Error updating user basic information: " . $conn->error);
        }
        
        // Role update handled within users table update above.
        // No additional role-specific table updates needed as tables are dropped.
        
        // Log the action

        
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
    
    // Fetch user data with all fields needed for editing
    $stmt = $conn->prepare("SELECT user_id, username, firstname, lastname, email, phone_number, role FROM users WHERE user_id = ?");
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