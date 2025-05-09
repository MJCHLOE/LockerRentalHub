<?php
session_start();
require '../db/database.php';
require_once 'log_actions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newUsername = mysqli_real_escape_string($conn, strtolower(trim($_POST['username'])));
    $newPassword = trim($_POST['password']);
    $newFirstname = mysqli_real_escape_string($conn, trim($_POST['firstname']));
    $newLastname = mysqli_real_escape_string($conn, trim($_POST['lastname']));
    $newEmail = mysqli_real_escape_string($conn, strtolower(trim($_POST['email'])));
    $newPhone = mysqli_real_escape_string($conn, trim($_POST['phone_number']));
    $newRole = mysqli_real_escape_string($conn, trim($_POST['role']));

    if (!empty($newUsername) && !empty($newPassword) && !empty($newFirstname) && 
        !empty($newLastname) && !empty($newEmail) && !empty($newPhone) && !empty($newRole)) {
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $conn->begin_transaction();

            // Insert into Users table
            $sql = "INSERT INTO Users (username, password, firstname, lastname, email, phone_number, role) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $newUsername, $hashedPassword, $newFirstname, 
                            $newLastname, $newEmail, $newPhone, $newRole);

            if ($stmt->execute()) {
                $newUserId = $conn->insert_id;
                
                $logger = new SystemLogger($conn);
                $logger->logAction(
                    'Add User',
                    "Added new {$newRole}: {$newFirstname} {$newLastname} (Username: {$newUsername})",
                    'user',
                    $newUserId,
                    $_SESSION['user_id']
                );

                $conn->commit();
                echo "<script>alert('User added successfully!'); window.location.href='../admin/dashboard.php';</script>";
            } else {
                throw new Exception($conn->error);
            }

        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='../admin/dashboard.php';</script>";
        }
        
        $stmt->close();
    } else {
        echo "<script>alert('All fields are required.'); window.location.href='../admin/dashboard.php';</script>";
    }
}

$conn->close();
?>
