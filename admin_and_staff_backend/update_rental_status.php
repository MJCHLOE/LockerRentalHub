<?php
session_start();
require_once '../db/database.php';
require_once '../admin_backend/log_actions.php';

header('Content-Type: application/json');

// Ensure only Admin or Staff can perform this action
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['rental_id'], $_POST['new_status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$rentalId = intval($_POST['rental_id']);
$newStatus = $_POST['new_status'];
$userId = $_SESSION['user_id'];\$userRole = $_SESSION['role'];

// Allowed status transitions
$validStatuses = ['approved', 'denied', 'active', 'cancelled', 'completed'];
if (!in_array($newStatus, $validStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Begin database transaction
    $conn->begin_transaction();

    // Fetch current rental info
    $stmt = $conn->prepare("SELECT locker_id, rental_status FROM rental WHERE rental_id = ? FOR UPDATE");
    $stmt->bind_param('i', $rentalId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Rental record not found');
    }
    $rental = $result->fetch_assoc();
    $currentStatus = $rental['rental_status'];
    $lockerId = $rental['locker_id'];

    // Update rental status
    $updateRental = $conn->prepare("UPDATE rental SET rental_status = ? WHERE rental_id = ?");
    $updateRental->bind_param('si', $newStatus, $rentalId);
    $updateRental->execute();

    // Update locker status based on action
    switch ($newStatus) {
        case 'approved':
            // keep Reserved (status_id = 4)
            $lockerStatusId = 4;
            break;
        case 'active':
            // mark Occupied
            $lockerStatusId = 2;
            break;
        case 'denied':
        case 'cancelled':
        case 'completed':
            // free up the locker
            $lockerStatusId = 1;
            break;
        default:
            $lockerStatusId = null;
    }
    if ($lockerStatusId !== null) {
        $updateLocker = $conn->prepare("UPDATE lockerunits SET status_id = ? WHERE locker_id = ?");
        $updateLocker->bind_param('is', $lockerStatusId, $lockerId);
        $updateLocker->execute();
    }

    // Log the action
    if ($userRole === 'Admin') {
        $logger = new AdminLogger($conn);
        $logger->logRentalAction($rentalId, strtoupper($newStatus));
    } else {
        $logger = new StaffLogger($conn);
        $logger->logRentalAction($rentalId, strtoupper($newStatus));
    }

    // Commit all changes
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Rental status updated successfully']);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
