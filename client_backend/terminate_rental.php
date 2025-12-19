<?php
session_start();
require_once '../db/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rental_id'])) {
    try {
        $conn->begin_transaction();

        $rental_id = $_POST['rental_id'];
        $user_id = $_SESSION['user_id'];

        // Get rental details before terminating
        $query = "SELECT locker_id, rental_date, status, payment_status FROM rentals WHERE rental_id = ? AND user_id = ? AND status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $rental_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rental = $result->fetch_assoc();

        if (!$rental) {
            throw new Exception('Active rental not found or unauthorized');
        }

        // 1. Insert into archives (status 'completed' as it was terminated/checked out by user)
        // If the user manually terminates, we can consider it 'completed' usage.
        $archiveQuery = "INSERT INTO rental_archives (original_rental_id, user_id, locker_id, start_date, end_date, final_status, payment_status_at_archive) 
                         VALUES (?, ?, ?, ?, NOW(), 'completed', ?)";
        $stmt = $conn->prepare($archiveQuery);
        $stmt->bind_param("iiiss", $rental_id, $user_id, $rental['locker_id'], $rental['rental_date'], $rental['payment_status']);
        $stmt->execute();

        // 2. Delete from rentals
        $deleteQuery = "DELETE FROM rentals WHERE rental_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $rental_id);
        $stmt->execute();

        // 3. Update locker status back to vacant
        $updateLocker = "UPDATE lockers SET status = 'Vacant' WHERE locker_id = ?";
        $stmt = $conn->prepare($updateLocker);
        $stmt->bind_param("s", $rental['locker_id']);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
