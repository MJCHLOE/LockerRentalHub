<?php
session_start();
require_once '../db/database.php';
header('Content-Type: application/json');

// Check if user is Admin or Staff
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required fields
if (!isset($_POST['rental_id'], $_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$rental_id = intval($_POST['rental_id']);
$new_status = $_POST['status'];
$staff_id = $_SESSION['user_id'];

// Validate status
$valid_statuses = ['pending', 'approved', 'active', 'denied', 'cancelled', 'completed'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1. Update rental status and processed_by
    $updateRental = "UPDATE rental 
                     SET rental_status = ?, processed_by = ?
                     WHERE rental_id = ?";
    $stmt = $conn->prepare($updateRental);
    $stmt->bind_param("sii", $new_status, $staff_id, $rental_id);
    $stmt->execute();

    // 2. Update locker status based on rental status
    $updateLocker = "UPDATE lockerunits lu 
                     JOIN rental r ON lu.locker_id = r.locker_id 
                     SET lu.status_id = CASE 
                         WHEN ? = 'approved' THEN 3 -- Occupied
                         WHEN ? IN ('denied', 'cancelled', 'completed') THEN 1 -- Vacant
                         ELSE lu.status_id
                     END
                     WHERE r.rental_id = ?";
    $stmt = $conn->prepare($updateLocker);
    $stmt->bind_param("ssi", $new_status, $new_status, $rental_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}

$conn->close();
?>
