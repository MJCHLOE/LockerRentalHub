<?php
session_start(); // Start session to check user role
require '../db/database.php';

// Only allow admin or staff to update rental status
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['rental_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$rental_id = $data['rental_id'];
$new_status = $data['status'];
$user_id = $_SESSION['user_id']; // Get current user ID for logging

// Valid rental statuses
$valid_statuses = ['pending', 'approved', 'active', 'denied', 'cancelled', 'completed'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$response = [];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get current rental information and locker_id
    $stmt = $conn->prepare("SELECT locker_id, rental_status, payment_status 
                          FROM rental 
                          WHERE rental_id = ?");
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
    $stmt->close();
    
    // Don't process if status hasn't changed
    if ($current_status === $new_status) {
        echo json_encode(['success' => true, 'message' => 'No change in status']);
        exit;
    }
    
    // Determine if payment status should be updated
    $update_payment = false;
    $new_payment_status = $current_payment_status;
    
    // If rental is being approved, automatically set payment status to 'paid'
    if ($new_status === 'approved' && $current_status === 'pending') {
        $update_payment = true;
        $new_payment_status = 'paid';
    }
    
    // Check if we need to set the rent_ended_date
    $set_end_date = in_array($new_status, ['completed', 'denied', 'cancelled']);
    
    // Prepare SQL based on whether payment status and/or end date needs updating
    if ($update_payment && $set_end_date) {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ?, payment_status = ?, rent_ended_date = NOW() WHERE rental_id = ?");
        $stmt->bind_param("ssi", $new_status, $new_payment_status, $rental_id);
    } elseif ($update_payment) {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ?, payment_status = ? WHERE rental_id = ?");
        $stmt->bind_param("ssi", $new_status, $new_payment_status, $rental_id);
    } elseif ($set_end_date) {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ?, rent_ended_date = NOW() WHERE rental_id = ?");
        $stmt->bind_param("si", $new_status, $rental_id);
    } else {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ? WHERE rental_id = ?");
        $stmt->bind_param("si", $new_status, $rental_id);
    }
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
    $description = "Updated rental #$rental_id status from '$current_status' to '$new_status'";
    
    // Add payment status change to log description if applicable
    if ($update_payment) {
        $description .= " and payment status to '$new_payment_status'";
    }
    
    // Add rent ended date to log description if applicable
    if ($set_end_date) {
        $description .= " and marked rental as ended";
    }
    
    $entity_type = "rental";
    $entity_id = $rental_id;
    
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
    
    $response['success'] = true;
    $response['message'] = "Rental status updated successfully";
    if ($update_payment) {
        $response['message'] .= " and payment marked as paid";
    }
    if ($set_end_date) {
        $response['message'] .= " and rent ended date set";
    }
    $response['new_status'] = $new_status;
    if ($update_payment) {
        $response['payment_status'] = $new_payment_status;
    }
    $response['locker_status_id'] = $locker_status_id;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>