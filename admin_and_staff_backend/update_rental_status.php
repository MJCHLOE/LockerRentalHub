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
    
    // Get payment status if provided (for admin payment updates)
    $payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : null;
    
    try {
        $conn->begin_transaction();

        // When approving a rental, automatically set payment status to 'paid'
        if ($new_status === 'approved') {
            $payment_status = 'paid';
        }

        // Set the current user ID for the trigger to use
        $setUserVar = "SET @current_user_id = ?";
        $stmt = $conn->prepare($setUserVar);
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();

        // Update rental status
        if ($payment_status !== null) {
            // Update both rental status and payment status
            $updateRental = "UPDATE rental 
                            SET rental_status = ?,
                                payment_status = ?
                            WHERE rental_id = ?";
            $stmt = $conn->prepare($updateRental);
            $stmt->bind_param("ssi", $new_status, $payment_status, $rental_id);
        } else {
            // Standard update without payment status change
            $updateRental = "UPDATE rental 
                            SET rental_status = ?
                            WHERE rental_id = ?";
            $stmt = $conn->prepare($updateRental);
            $stmt->bind_param("si", $new_status, $rental_id);
        }
        $stmt->execute();

        // Update locker status based on rental status
        $updateLocker = "UPDATE lockerunits lu 
                        JOIN rental r ON lu.locker_id = r.locker_id 
                        SET lu.status_id = CASE 
                            WHEN ? = 'approved' THEN 2 -- Occupied (status_id = 2 from your DB schema)
                            WHEN ? IN ('denied', 'cancelled', 'completed') THEN 1 -- Vacant (status_id = 1)
                            ELSE lu.status_id 
                        END 
                        WHERE r.rental_id = ?";
        $stmt = $conn->prepare($updateLocker);
        $stmt->bind_param("ssi", $new_status, $new_status, $rental_id);
        $stmt->execute();

        // Log the action in system_logs
        $getLockerInfo = "SELECT locker_id, user_id FROM rental WHERE rental_id = ?";
        $stmt = $conn->prepare($getLockerInfo);
        $stmt->bind_param("i", $rental_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rentalInfo = $result->fetch_assoc();
        
        if ($rentalInfo) {
            $locker_id = $rentalInfo['locker_id'];
            $user_id = $rentalInfo['user_id'];
            
            $action = "RENTAL_STATUS_UPDATE";
            $description = "Rental Status Update: Changed status of Locker #{$locker_id} rental to '{$new_status}'";
            if ($payment_status) {
                $description .= " with payment status '{$payment_status}'";
            }
            
            $insertLog = "INSERT INTO system_logs (user_id, action, description, entity_type, entity_id) 
                          VALUES (?, ?, ?, 'rental', ?)";
            $stmt = $conn->prepare($insertLog);
            $stmt->bind_param("issi", $staff_id, $action, $description, $rental_id);
            $stmt->execute();
        }

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