<?php
session_start();
require_once '../db/database.php';
require_once '../admin_scripts/log_actions.php'; // Assuming this has logging classes

header('Content-Type: application/json');

// Check if user is authorized (Admin or Staff)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_POST['rental_id'], $_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$rental_id = intval($_POST['rental_id']);
$new_status = strtolower($_POST['status']);
$admin_id = $_SESSION['user_id'];
$admin_role = $_SESSION['role'];

try {
    $conn->begin_transaction();

    // Fetch current rental data
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

    // Validate allowed transitions
    $allowedTransitions = [
        'pending' => ['approved', 'denied', 'cancelled'],
        'approved' => ['active', 'cancelled'],
        'active' => ['completed', 'cancelled'],
        'cancelled' => [],
        'completed' => []
    ];

    if (!in_array($new_status, $allowedTransitions[$current_status] ?? [])) {
        throw new Exception("Invalid status transition from '$current_status' to '$new_status'");
    }

    // Update rental status and set processor info
    $stmt = $conn->prepare("UPDATE rental SET 
                                rental_status = ?, 
                                processed_by = ?, 
                                processed_at = NOW()
                            WHERE rental_id = ?");
    $stmt->bind_param("sii", $new_status, $admin_id, $rental_id);
    $stmt->execute();

    // Optional: Update locker status based on new rental status
    $locker_id = $rental['locker_id'];

    switch ($new_status) {
        case 'approved':
            // Reserved → Occupied
            $lockerStatus = 3; // Occupied
            break;
        case 'active':
            $lockerStatus = 3; // Still occupied
            break;
        case 'cancelled':
        case 'denied':
            $lockerStatus = 1; // Vacant again
            break;
        case 'completed':
            $lockerStatus = 1; // Free up locker
            break;
        default:
            $lockerStatus = null;
    }

    if ($lockerStatus !== null) {
        $stmt = $conn->prepare("UPDATE lockerunits SET status_id = ? WHERE locker_id = ?");
        $stmt->bind_param("is", $lockerStatus, $locker_id);
        $stmt->execute();
    }

    // Log the action
    $logger = new AdminLogger($conn); // Or StaffLogger depending on your setup
    $logger->logRentalStatusChange($rental_id, $current_status, $new_status, $admin_role);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Rental #{$rental_id} status updated to '{$new_status}'",
        'new_status' => $new_status
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>