<?php
session_start();
require_once '../db/database.php';

header('Content-Type: application/json');

// Check if user is authorized
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST and SESSION data
    error_log("POST DATA: " . print_r($_POST, true));
    error_log("SESSION DATA: " . print_r($_SESSION, true));

    $rental_id = $_POST['rental_id'] ?? null;
    $new_status = $_POST['status'] ?? null;
    $staff_id = $_SESSION['user_id'] ?? null;

    if (!$rental_id || !$new_status || !$staff_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Update rental status and processed_by
        $updateRental = "UPDATE rental SET rental_status = ?, processed_by = ? WHERE rental_id = ?";
        $stmt = $conn->prepare($updateRental);
        $stmt->bind_param("sis", $new_status, $staff_id, $rental_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("Rental update failed or no rows were affected.");
        }

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

        if ($stmt->affected_rows === 0) {
            error_log("No locker status was updated. This might be due to the JOIN not matching.");
        }

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error updating rental: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
