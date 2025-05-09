<?php
session_start();
require '../db/database.php';

// 1. Verify admin/staff access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}

// 2. Validate required inputs
if (!isset($_POST['rental_id']) || !isset($_POST['status'])) {
    die(json_encode(['success' => false, 'message' => 'Missing parameters']));
}

$rental_id = (int)$_POST['rental_id'];
$new_status = $_POST['status'];
$user_id = (int)$_SESSION['user_id'];

// 3. Validate status transition
$current_status = $conn->query("SELECT rental_status FROM rental WHERE rental_id = $rental_id")->fetch_row()[0] ?? null;

if (!$current_status) {
    die(json_encode(['success' => false, 'message' => 'Rental not found']));
}

$valid_transitions = [
    'pending' => ['approved', 'denied'],
    'approved' => ['active', 'cancelled'],
    'active' => ['completed', 'cancelled']
];

if (!isset($valid_transitions[$current_status]) || !in_array($new_status, $valid_transitions[$current_status])) {
    die(json_encode(['success' => false, 'message' => 'Invalid status change']));
}

// 4. Update database
$conn->begin_transaction();

try {
    // Update rental status
    $conn->query("UPDATE rental SET rental_status = '$new_status', processed_by = $user_id WHERE rental_id = $rental_id");
    
    // Auto-set payment status to paid when activating
    if ($new_status === 'active') {
        $conn->query("UPDATE rental SET payment_status = 'paid' WHERE rental_id = $rental_id");
    }
    
    // Update locker status
    $locker_id = $conn->query("SELECT locker_id FROM rental WHERE rental_id = $rental_id")->fetch_row()[0];
    
    if (in_array($new_status, ['approved', 'active'])) {
        $status_id = $conn->query("SELECT status_id FROM lockerstatuses WHERE status_name = 'Occupied'")->fetch_row()[0];
    } 
    else if (in_array($new_status, ['completed', 'denied', 'cancelled'])) {
        $status_id = $conn->query("SELECT status_id FROM lockerstatuses WHERE status_name = 'Vacant'")->fetch_row()[0];
    }
    
    if (isset($status_id)) {
        $conn->query("UPDATE lockers SET status_id = $status_id WHERE locker_id = '$locker_id'");
    }
    
    // Log the action
    $conn->query("INSERT INTO activity_logs (action, description, user_id, entity) 
                 VALUES ('Status Update', 'Changed rental #$rental_id to $new_status', $user_id, 'rental')");
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    die(json_encode(['success' => false, 'message' => 'Database error']));
}
?>