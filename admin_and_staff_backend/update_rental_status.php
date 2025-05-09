<?php
session_start();
require_once '../db/database.php';
require_once 'log_actions.php'; // Adjust path if needed

header('Content-Type: application/json');

// Check if user is authorized (Admin or Staff)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get input
$rental_id = $_POST['rental_id'] ?? null;
$new_status = strtolower($_POST['status'] ?? '');

if (!$rental_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1. Fetch current rental data
    $stmt = $conn->prepare("
        SELECT r.rental_status, r.locker_id, u.firstname, u.lastname 
        FROM rental r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.rental_id = ?
    ");
    $stmt->bind_param("i", $rental_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Rental record not found");
    }

    $rental = $result->fetch_assoc();
    $current_status = $rental['rental_status'];
    $locker_id = $rental['locker_id'];
    $client_name = "{$rental['firstname']} {$rental['lastname']}";

    // 2. Validate allowed status transitions
    $allowedTransitions = [
        'pending' => ['approved', 'denied', 'cancelled'],
        'approved' => ['active', 'cancelled'],
        'active' => ['completed', 'cancelled'],
        'cancelled' => [],
        'completed' => []
    ];

    if (!in_array($new_status, $allowedTransitions[$current_status] ?? [])) {
        throw new Exception("Invalid status transition from '{$current_status}' to '{$new_status}'");
    }

    // 3. Update rental status (only this field)
    $stmt = $conn->prepare("UPDATE rental SET rental_status = ? WHERE rental_id = ?");
    $stmt->bind_param("si", $new_status, $rental_id);
    $stmt->execute();

    // 4. Update locker status based on new rental status
    $lockerStatusMap = [
        'approved' => 2, // Occupied
        'active' => 2,
        'cancelled' => 1, // Vacant
        'denied' => 1,
        'completed' => 1
    ];

    if (isset($lockerStatusMap[$new_status])) {
        $stmt = $conn->prepare("UPDATE lockerunits SET status_id = ? WHERE locker_id = ?");
        $stmt->bind_param("is", $lockerStatusMap[$new_status], $locker_id);
        $stmt->execute();
    }

    // 5. Log the action
    $logger = $_SESSION['role'] === 'Admin' ? new AdminLogger($conn) : new StaffLogger($conn);
    $logger->logRentalStatusChange(
        $rental_id,
        $current_status,
        $new_status,
        $_SESSION['role'],
        $locker_id,
        $client_name
    );

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Rental #{$rental_id} status updated from '{$current_status}' to '{$new_status}'",
        'new_status' => $new_status
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>