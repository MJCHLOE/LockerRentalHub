<?php
session_start();
require '../db/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $field = $_POST['field']; // 'username', 'fullname' (split into first/last), 'email', 'phone'
    $value = trim($_POST['value']);
    
    if (empty($value)) {
        echo json_encode(['success' => false, 'message' => 'Value cannot be empty']);
        exit;
    }
    
    $updateQuery = "";
    $types = "";
    $params = [];
    
    switch ($field) {
        case 'username':
            $updateQuery = "UPDATE users SET username = ? WHERE user_id = ?";
            $types = "si";
            $params = [$value, $userId];
            break;
        case 'email':
             if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit;
            }
            $updateQuery = "UPDATE users SET email = ? WHERE user_id = ?";
            $types = "si";
            $params = [$value, $userId];
            break;
        case 'phone':
             if (!preg_match("/^[0-9]{11}$/", $value)) {
                echo json_encode(['success' => false, 'message' => 'Invalid phone number format (11 digits)']);
                exit;
            }
            $updateQuery = "UPDATE users SET phone_number = ? WHERE user_id = ?";
            $types = "si";
            $params = [$value, $userId];
            break;
        case 'fullname':
            // Assume format "First Last"
            $parts = explode(' ', $value, 2);
            if (count($parts) < 2) {
                 echo json_encode(['success' => false, 'message' => 'Please enter both first and last name']);
                 exit;
            }
            $updateQuery = "UPDATE users SET firstname = ?, lastname = ? WHERE user_id = ?";
            $types = "ssi";
            $params = [$parts[0], $parts[1], $userId];
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid field']);
            exit;
    }
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // specific session update if needed
        if ($field === 'fullname') {
             // Update session firstname if meaningful
             if (isset($_SESSION[md5('Client_' . $userId)])) {
                  $_SESSION[md5('Client_' . $userId)]['firstname'] = $params[0];
             }
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
}
$conn->close();
?>