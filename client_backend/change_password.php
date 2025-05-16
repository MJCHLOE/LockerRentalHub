<?php
session_start();

// Generate a unique session identifier for client
$clientSessionKey = md5('Client_' . $_SESSION['user_id']);

// Check if user is logged in and is a client
if (!isset($_SESSION[$clientSessionKey]) || 
    !isset($_SESSION['role']) || 
    $_SESSION['role'] !== 'Client') {
    echo "<script>alert('Unauthorized access.'); window.location.href='../client/home.php';</script>";
    exit();
}

require_once '../db/database.php';
require_once 'log_actions.php'; 

// Check database connection
if ($conn->connect_error) {
    echo "<script>alert('Connection failed: " . addslashes($conn->connect_error) . "');</script>";
    exit();
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
    if ($userId === false) {
        echo "<script>alert('Invalid user ID.');</script>";
        exit();
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo "<script>alert('All fields are required.');</script>";
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        echo "<script>alert('New passwords do not match.');</script>";
        exit();
    }

    if (strlen($newPassword) < 6) {
        echo "<script>alert('New password must be at least 6 characters.');</script>";
        exit();
    }

    // Fetch current hashed password from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    if (!$stmt) {
        echo "<script>alert('Database error. Please try again later.');</script>";
        exit();
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($storedHash);
    $stmt->fetch();
    $stmt->close();

    if (!$storedHash || !password_verify($currentPassword, $storedHash)) {
        echo "<script>alert('Current password is incorrect.');</script>";
        exit();
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password in DB
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    if (!$stmt) {
        echo "<script>alert('Database error. Please try again later.');</script>";
        exit();
    }
    $stmt->bind_param("si", $hashedPassword, $userId);

    if ($stmt->execute()) {
        // Log the password change
        $logger = new SystemLogger($conn);
        $logger->logAction(
            'Change Password',
            "User changed their password",
            'user',
            $userId
        );

        // Log out the user
        session_destroy();

        // Show success pop-up and redirect to LoginPage.html
        echo "<script>alert('Password changed successfully! You have been logged out.');</script>";
        echo "<script>window.location.href='../LoginPage.html';</script>";
    } else {
        echo "<script>alert('Failed to update password. Please try again later.');</script>";
    }
    $stmt->close();
} else {
    // If not a POST request, show error
    echo "<script>alert('Invalid request method.'); window.location.href='../client/home.php';</script>";
}

$conn->close();
?>