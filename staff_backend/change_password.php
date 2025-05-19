<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    echo "<script>alert('Unauthorized access.'); window.location.href='../../staffdashboard.php';</script>";
    exit();
}

require_once '../db/database.php';
require_once 'log_actions.php'; 

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo "<script>alert('All fields are required.'); window.location.href='../staff/dashboard.php';</script>";
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        echo "<script>alert('New passwords do not match.'); window.location.href='../staff/dashboard.php';</script>";
        exit();
    }

    if (strlen($newPassword) < 6) {
        echo "<script>alert('New password must be at least 6 characters.'); window.location.href='../staff/dashboard.php';</script>";
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
        echo "<script>alert('Current password is incorrect.'); window.location.href='../staff/dashboard.php';</script>";
        exit();
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);

    if ($stmt->execute()) {
        // Add after successful password update, before the success alert:
        $logger = new SystemLogger($conn);
        $logger->logAction(
            'Change Password',
            "User changed their password",
            'user',
            $userId
        );
        echo "<script>alert('Password changed successfully!'); window.location.href='../staff/dashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to update password. Please try again later.'); window.location.href='../staff/dashboard.php';</script>";
    }
    $stmt->close();
}
$conn->close();
?>