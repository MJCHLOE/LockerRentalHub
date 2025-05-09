<?php
session_start();
require_once '../db/database.php';
require_once '../admin_backend/log_actions.php'; // Make sure this file exists and is working

header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session
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

    // Fetch rental data
    $stmt = $conn->prepare("SELECT r.rental_status, r.locker_id, u.firstname, u.lastname 
                            FROM rental r
                            JOIN users u ON r.user_id = u.user_id
                            WHERE r.rental_id = ?");
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

    // Validate status transition
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

    // Update rental status
    $stmt = $conn->prepare("UPDATE rental SET rental_status = ? WHERE rental_id = ?");
    $stmt->bind_param("si", $new_status, $rental_id);
    $stmt->execute();

    // Update locker status if applicable
    $lockerStatusMap = [
        'approved' => 2,
        'active' => 2,
        'cancelled' => 1,
        'denied' => 1,
        'completed' => 1
    ];

    if (!empty($locker_id) && isset($lockerStatusMap[$new_status])) {
        $stmt = $conn->prepare("UPDATE lockerunits SET status_id = ? WHERE locker_id = ?");
        $stmt->bind_param("is", $lockerStatusMap[$new_status], $locker_id);
        $stmt->execute();
    }

    // Log the change
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
        'message' => "Rental #{$rental_id} status updated to '{$new_status}'"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
}

$conn->close();
?>