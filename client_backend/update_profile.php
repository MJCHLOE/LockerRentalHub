<?php
session_start();
require_once '../db/database.php';
require_once 'log_actions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $fieldType = $_POST['field_type'];
    $fieldValue = trim($_POST['field_value']);

    // Validate input
    if (empty($fieldValue)) {
        echo json_encode(['success' => false, 'message' => 'Field cannot be empty']);
        exit;
    }

    // Get current value before update
    $getCurrentValue = "SELECT username, firstname, lastname, email, phone_number 
                       FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($getCurrentValue);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentData = $result->fetch_assoc();
    $stmt->close();

    // Get old value based on field type
    switch($fieldType) {
        case 'username':
            $oldValue = $currentData['username'];
            $sql = "UPDATE users SET username = ? WHERE user_id = ?";
            break;
        case 'fullname':
            $oldValue = $currentData['firstname'] . ' ' . $currentData['lastname'];
            // Split full name into first and last name
            $names = explode(' ', $fieldValue);
            if (count($names) < 2) {
                echo json_encode(['success' => false, 'message' => 'Please enter both first and last name']);
                exit;
            }
            $firstname = $names[0];
            $lastname = isset($names[1]) ? $names[1] : '';
            $sql = "UPDATE users SET firstname = ?, lastname = ? WHERE user_id = ?";
            break;
        case 'email':
            $oldValue = $currentData['email'];
            if (!filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit;
            }
            $sql = "UPDATE users SET email = ? WHERE user_id = ?";
            break;
        case 'phone':
            $oldValue = $currentData['phone_number'];
            $sql = "UPDATE users SET phone_number = ? WHERE user_id = ?";
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid field type']);
            exit;
    }

    try {
        $stmt = $conn->prepare($sql);
        
        if ($fieldType === 'fullname') {
            $stmt->bind_param("ssi", $firstname, $lastname, $userId);
        } else {
            $stmt->bind_param("si", $fieldValue, $userId);
        }
        
        if ($stmt->execute()) {
            // Log the change
            $logger = new ClientLogger($conn);
            $logger->logProfileUpdate($fieldType, $oldValue, $fieldValue);
            
            // Update session variables if username was changed
            if ($fieldType === 'username') {
                $_SESSION['username'] = $fieldValue;
            }
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>