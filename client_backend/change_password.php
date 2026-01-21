<?php
session_start();
require '../db/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../LoginPage.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_POST['user_id'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword !== $confirmPassword) {
         echo "<script>alert('New passwords do not match'); window.location.href='../client/profile_details.php';</script>";
         exit();
    }
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($currentPassword, $user['password'])) {
            // Update to new password
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $updateStmt->bind_param("si", $newHash, $userId);
            
            if ($updateStmt->execute()) {
                 echo "<script>alert('Password updated successfully'); window.location.href='../client/profile_details.php';</script>";
            } else {
                 echo "<script>alert('Error updating password'); window.location.href='../client/profile_details.php';</script>";
            }
            $updateStmt->close();
        } else {
            echo "<script>alert('Incorrect current password'); window.location.href='../client/profile_details.php';</script>";
        }
    } else {
        echo "<script>alert('User not found'); window.location.href='../client/profile_details.php';</script>";
    }
    $stmt->close();
}
$conn->close();
?>