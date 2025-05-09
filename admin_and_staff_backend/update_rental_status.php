<?php
session_start();
require '../db/database.php';

header('Content-Type: application/json');

// Check if user is authorized
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = $_POST['rental_id'];
    $new_status = $_POST['status'];
    $staff_id = $_SESSION['user_id'];

    try {
        $conn->begin_transaction();

        // Update rental status - removed the extra comma before WHERE
        $updateRental = "UPDATE rental 
                        SET rental_status = ?,
                            processed_by = ?
                        WHERE rental_id = ?";
        $stmt = $conn->prepare($updateRental);
        $stmt->bind_param("sis", $new_status, $staff_id, $rental_id);
        $stmt->execute();

        // Update locker status based on rental status
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
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>