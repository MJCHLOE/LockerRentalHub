<?php
session_start();
require_once '../db/database.php';
require_once 'log_actions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['locker_id'])) {
    $locker_id = $_POST['locker_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Start transaction
        $conn->begin_transaction();

        // Check if user has previously rented this locker
        $checkPrevRental = "SELECT COUNT(*) as count FROM rental WHERE user_id = ? AND locker_id = ? AND rental_status IN ('approved', 'active', 'completed')";
        $stmt = $conn->prepare($checkPrevRental);
        $stmt->bind_param("is", $user_id, $locker_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            throw new Exception('You have previously rented this locker and cannot rent it again.');
        }

        // Check if locker is available for rent
        $checkQuery = "SELECT lst.status_name FROM lockerunits l JOIN lockerstatuses lst ON l.status_id = lst.status_id WHERE l.locker_id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $locker_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $locker = $result->fetch_assoc();
        if ($locker === null) {
            throw new Exception('Locker not found.');
        }
        if (!in_array($locker['status_name'], ['Vacant', 'Reserved'])) {
            throw new Exception('This locker is not available for rent.');
        }

        // Insert into rental table
        $insertQuery = "INSERT INTO rental (user_id, locker_id, rental_date, rental_status, payment_status_id) 
                       VALUES (?, ?, NOW(), 'pending', '1')";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("is", $user_id, $locker_id);
        $stmt->execute();

        // Update locker status to 'Reserved'
        $updateQuery = "UPDATE lockerunits SET status_id = 4 WHERE locker_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("s", $locker_id);
        $stmt->execute();

        // Log the rental request
        $logger = new ClientLogger($conn);
        $logger->logLockerRental($locker_id, 'REQUEST');

        // Commit transaction
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Rental request submitted successfully']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>