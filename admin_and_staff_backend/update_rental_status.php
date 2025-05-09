<?php
session_start();
require_once '../db/database.php';

header('Content-Type: application/json');

// Check if user is authorized
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = $data['rental_id'];
    $new_status = $data['status'];
    $staff_id = $_SESSION['user_id'];

    try {
        $conn->begin_transaction();

        // Set the current user ID for the trigger
        $conn->query("SET @current_user_id = " . $staff_id);

        // Update rental status
        $updateRental = "UPDATE rental 
                        SET rental_status = ?
                        WHERE rental_id = ?";
        $stmt = $conn->prepare($updateRental);
        $stmt->bind_param("si", $new_status, $rental_id);
        $stmt->execute();

        // Update locker status based on rental status
        $updateLocker = "UPDATE lockerunits lu 
                        JOIN rental r ON lu.locker_id = r.locker_id 
                        SET lu.status_id = CASE 
                            WHEN ? = 'approved' THEN 2 -- Occupied
                            WHEN ? IN ('denied', 'cancelled', 'completed') THEN 1 -- Vacant
                            ELSE lu.status_id 
                        END 
                        WHERE r.rental_id = ?";
        $stmt = $conn->prepare($updateLocker);
        $stmt->bind_param("ssi", $new_status, $new_status, $rental_id);
        $stmt->execute();

        // Insert into system_logs
        $logQuery = "INSERT INTO system_logs (user_id, action, description, entity_type, entity_id) 
                    VALUES (?, 'Update Rental', ?, 'rental', ?)";
        $description = "Updated rental status to: " . $new_status;
        $stmt = $conn->prepare($logQuery);
        $stmt->bind_param("iss", $staff_id, $description, $rental_id);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>