<?php
session_start(); // Start session to check user role
require '../db/database.php';

// Only allow admin or staff to update rental status
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Check for required parameters based on the operation type
if (!isset($data['rental_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing rental ID']);
    exit;
}

$rental_id = $data['rental_id'];
$user_id = $_SESSION['user_id']; // Get current user ID for logging
$operation_type = isset($data['operation_type']) ? $data['operation_type'] : 'status_update';
$response = [];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get current rental information
    $stmt = $conn->prepare("SELECT r.locker_id, r.rental_status, r.payment_status, 
                          CONCAT(u.firstname, ' ', u.lastname) as client_name 
                          FROM rental r 
                          JOIN users u ON r.user_id = u.user_id 
                          WHERE r.rental_id = ?");
    $stmt->bind_param("i", $rental_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Rental not found");
    }
    
    $rental_info = $result->fetch_assoc();
    $locker_id = $rental_info['locker_id'];
    $current_status = $rental_info['rental_status'];
    $current_payment_status = $rental_info['payment_status'];
    $client_name = $rental_info['client_name'];
    $stmt->close();
    
    // Handle different operations
    if ($operation_type === 'status_update' && isset($data['status'])) {
        $new_status = $data['status'];
        
        // Valid rental statuses
        $valid_statuses = ['pending', 'approved', 'active', 'denied', 'cancelled', 'completed'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception('Invalid status');
        }
        
        // Don't process if status hasn't changed
        if ($current_status === $new_status) {
            echo json_encode(['success' => true, 'message' => 'No change in status']);
            exit;
        }
        
        // Update rental status
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ? WHERE rental_id = ?");
        $stmt->bind_param("si", $new_status, $rental_id);
        $stmt->execute();
        $stmt->close();
        
        // Determine the appropriate locker status_id based on rental status
        $locker_status_id = 1; // Default to Vacant (1)
        
        switch ($new_status) {
            case 'pending':
                $locker_status_id = 4; // Reserved
                break;
            case 'approved':
                $locker_status_id = 4; // Reserved
                break;
            case 'active':
                $locker_status_id = 2; // Occupied
                break;
            case 'completed':
            case 'denied':
            case 'cancelled':
                $locker_status_id = 1; // Vacant
                break;
        }
        
        // Update locker status
        $stmt = $conn->prepare("UPDATE lockerunits SET status_id = ? WHERE locker_id = ?");
        $stmt->bind_param("is", $locker_status_id, $locker_id);
        $stmt->execute();
        $stmt->close();
        
        // Log the action in system_logs
        $action = "Update Rental";
        $description = "Updated rental #$rental_id status from '$current_status' to '$new_status' for client $client_name";
        $entity_type = "rental";
        $entity_id = $rental_id;
        
        $response['success'] = true;
        $response['message'] = "Rental status updated successfully";
        $response['new_status'] = $new_status;
        $response['locker_status_id'] = $locker_status_id;
    }
    // Handle payment status update
    elseif ($operation_type === 'payment_update' && isset($data['payment_status'])) {
        $new_payment_status = $data['payment_status'];
        
        // Valid payment statuses
        $valid_payment_statuses = ['paid', 'unpaid'];
        if (!in_array($new_payment_status, $valid_payment_statuses)) {
            throw new Exception('Invalid payment status');
        }
        
        // Don't process if payment status hasn't changed
        if ($current_payment_status === $new_payment_status) {
            echo json_encode(['success' => true, 'message' => 'No change in payment status']);
            exit;
        }
        
        // Update payment status
        $stmt = $conn->prepare("UPDATE rental SET payment_status = ? WHERE rental_id = ?");
        $stmt->bind_param("si", $new_payment_status, $rental_id);
        $stmt->execute();
        $stmt->close();
        
        // Log the action in system_logs
        $action = "Update Payment";
        $description = "Updated rental #$rental_id payment status from '$current_payment_status' to '$new_payment_status' for client $client_name";
        $entity_type = "rental";
        $entity_id = $rental_id;
        
        $response['success'] = true;
        $response['message'] = "Payment status updated successfully";
        $response['new_payment_status'] = $new_payment_status;
    }
    else {
        throw new Exception('Invalid operation or missing parameters');
    }
    
    // Common logging code for both operations
    $log_stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description, entity_type, entity_id, log_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $log_stmt->bind_param("issss", $user_id, $action, $description, $entity_type, $entity_id);
    $log_stmt->execute();
    $log_id = $conn->insert_id;
    $log_stmt->close();
    
    // Add to appropriate role-specific log table
    if ($_SESSION['role'] === 'Admin') {
        // Get admin_id from the admin table
        $admin_stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
        $admin_stmt->bind_param("i", $user_id);
        $admin_stmt->execute();
        $admin_result = $admin_stmt->get_result();
        $admin_row = $admin_result->fetch_assoc();
        $admin_id = $admin_row['admin_id'];
        $admin_stmt->close();
        
        // Insert into admin_logs
        $admin_log_stmt = $conn->prepare("INSERT INTO admin_logs (log_id, admin_id) VALUES (?, ?)");
        $admin_log_stmt->bind_param("ii", $log_id, $admin_id);
        $admin_log_stmt->execute();
        $admin_log_stmt->close();
    } elseif ($_SESSION['role'] === 'Staff') {
        // Get staff_id from the staff table
        $staff_stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
        $staff_stmt->bind_param("i", $user_id);
        $staff_stmt->execute();
        $staff_result = $staff_stmt->get_result();
        $staff_row = $staff_result->fetch_assoc();
        $staff_id = $staff_row['staff_id'];
        $staff_stmt->close();
        
        // Insert into staff_logs
        $staff_log_stmt = $conn->prepare("INSERT INTO staff_logs (log_id, staff_id) VALUES (?, ?)");
        $staff_log_stmt->bind_param("ii", $log_id, $staff_id);
        $staff_log_stmt->execute();
        $staff_log_stmt->close();
    }
    
    // Commit the transaction
    $conn->commit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>