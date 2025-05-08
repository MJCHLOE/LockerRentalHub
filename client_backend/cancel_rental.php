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

        // Get locker_id before cancelling
        $query = "SELECT locker_id FROM rental WHERE rental_id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $rental_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rental = $result->fetch_assoc();

        if (!$rental) {
            throw new Exception('Rental not found or unauthorized');
        }

        // Update rental status
        $updateRental = "UPDATE rental SET rental_status = 'cancelled' WHERE rental_id = ?";
        $stmt = $conn->prepare($updateRental);
        $stmt->bind_param("i", $rental_id);
        $stmt->execute();

        // Update locker status back to vacant
        $updateLocker = "UPDATE lockerunits SET status_id = 1 WHERE locker_id = ?";
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