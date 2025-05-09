<?php
    session_start();
    require '../db/database.php';
    require_once 'log_actions.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // Get form data
    $newUsername = $_POST['username'];
    $newPassword = $_POST['password'];
    $newFirstname = $_POST['firstname'];
    $newLastname = $_POST['lastname'];
    $newEmail = $_POST['email'];
    $newPhone = $_POST['phone_number'];
    $newRole = $_POST['role'];

    // Basic validation
    if (!empty($newUsername) && !empty($newPassword) && !empty($newFirstname) && 
        !empty($newLastname) && !empty($newEmail) && !empty($newPhone) && !empty($newRole)) {
        
        // Hash the password (for security)
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            // Start transaction
            $conn->begin_transaction();

            // Insert user with new fields
            $sql = "INSERT INTO users (username, password, firstname, lastname, email, phone_number, role) 
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
                    $_SESSION['user_id'] // Add the current user's ID
                );

                // Commit transaction
                $conn->commit();
                
                echo json_encode(['success' => true, 'message' => 'User added successfully']);
            } else {
                throw new Exception($conn->error);
            }

            $stmt->close();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    }
    // Redirect back to dashboard
    header("Location: ../admin/dashboard.php#lockers");
    exit();
    $conn->close();
?>
