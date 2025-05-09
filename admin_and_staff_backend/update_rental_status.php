<?php
session_start();
require '../db/database.php';

// Check if user is logged in and is admin or staff
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if required parameters are set
if (!isset($_POST['rental_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$rental_id = intval($_POST['rental_id']);
$new_status = $_POST['status'];
$processed_by = $_SESSION['user_id']; // Current user ID

// Validate status
$valid_statuses = ['pending', 'approved', 'active', 'denied', 'cancelled', 'completed'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Get current rental status first to check if transition is valid
    $check_query = "SELECT rental_status, locker_id FROM rental WHERE rental_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $rental_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Rental not found");
    }
    
    $rental = $result->fetch_assoc();
    $current_status = $rental['rental_status'];
    $locker_id = $rental['locker_id'];
    
    // Validate status transition
    $valid_transition = false;
    
    switch ($current_status) {
        case 'pending':
            $valid_transition = in_array($new_status, ['approved', 'denied']);
            break;
        case 'approved':
            $valid_transition = in_array($new_status, ['active', 'cancelled']);
            break;
        case 'active':
            $valid_transition = in_array($new_status, ['completed', 'cancelled']);
            break;
        case 'denied':
        case 'cancelled':
        case 'completed':
            // These are final states, no transitions allowed
            $valid_transition = false;
            break;
    }
    
    if (!$valid_transition && $_SESSION['role'] !== 'Admin') {
        // Only admin can override status transition rules
        throw new Exception("Invalid status transition from {$current_status} to {$new_status}");
    }
    
    // Update rental status
    $update_query = "UPDATE rental SET rental_status = ?, processed_by = ? WHERE rental_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sii", $new_status, $processed_by, $rental_id);
    $update_stmt->execute();
    
    // Update locker status based on rental status
    $locker_status = '';
    if ($new_status === 'approved' || $new_status === 'active') {
        $locker_status = 'Occupied';
    } elseif ($new_status === 'completed' || $new_status === 'denied' || $new_status === 'cancelled') {
        $locker_status = 'Vacant';
    }
    
    if (!empty($locker_status)) {
        $status_id_query = "SELECT status_id FROM lockerstatuses WHERE status_name = ?";
        $status_stmt = $conn->prepare($status_id_query);
        $status_stmt->bind_param("s", $locker_status);
        $status_stmt->execute();
        $status_result = $status_stmt->get_result();
        $status_row = $status_result->fetch_assoc();
        $status_id = $status_row['status_id'];
        
        $locker_update = "UPDATE lockers SET status_id = ? WHERE locker_id = ?";
        $locker_stmt = $conn->prepare($locker_update);
        $locker_stmt->bind_param("is", $status_id, $locker_id);
        $locker_stmt->execute();
    }
    
    // Log the action
    $action = "Update Rental Status";
    $description = "Updated rental #{$rental_id} status from {$current_status} to {$new_status}";
    $user_id = $_SESSION['user_id'];
    $entity = "rental";
    
    $log_query = "INSERT INTO activity_logs (action, description, user_id, entity) VALUES (?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("ssis", $action, $description, $user_id, $entity);
    $log_stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response
    echo json_encode(['success' => true, 'message' => "Rental #{$rental_id} status updated to {$new_status}"]);
    
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>