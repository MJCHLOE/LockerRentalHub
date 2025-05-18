<?php
session_start();

// Generate a unique session identifier for client
$clientSessionKey = md5('Client_' . $_SESSION['user_id']);

// Check if user is logged in and is client
if (!isset($_SESSION[$clientSessionKey]) || 
    !isset($_SESSION['role']) || 
    $_SESSION['role'] !== 'Client') {
    $_SESSION['error_message'] = 'Unauthorized access.';
    header('Location: ../backend/logout.php');
    exit();
}

require_once '../db/database.php';

if ($conn->connect_error) {
    $_SESSION['error_message'] = 'Database connection failed.';
    header('Location: ../index.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error_message'] = 'All fields are required.';
        header('Location: password_error.php');
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['error_message'] = 'New passwords do not match.';
        header('Location: password_error.php');
        exit();
    }

    if (strlen($newPassword) < 6) {
        $_SESSION['error_message'] = 'New password must be at least 6 characters.';
        header('Location: password_error.php');
        exit();
    }

    // Fetch current hashed password from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($storedHash);
    $stmt->fetch();
    $stmt->close();

    if (!$storedHash || !password_verify($currentPassword, $storedHash)) {
        $_SESSION['error_message'] = 'Current password is incorrect.';
        header('Location: password_error.php');
        exit();
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);

    if ($stmt->execute()) {
        // Log the password change if you have a logging system
        if (file_exists('../backend/log_actions.php')) {
            require_once '../backend/log_actions.php';
            $logger = new SystemLogger($conn);
            $logger->logAction(
                'Change Password',
                "Client changed their password",
                'client',
                $userId
            );
        }
        
        // Set success message and redirect to password success page
        $_SESSION['success_message'] = 'Password changed successfully! Please login with your new password.';
        $stmt->close();
        $conn->close();
        header('Location: password_success.php');
        exit();
    } else {
        $_SESSION['error_message'] = 'Failed to update password. Please try again later.';
        header('Location: password_error.php');
    }
    $stmt->close();
}
$conn->close();
header('Location: change_password.php');
exit();
?>