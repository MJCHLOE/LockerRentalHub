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

        // Update rental status
        if ($payment_status !== null && $_SESSION['role'] === 'Admin') {
            // Admin is updating both rental status and payment status
            $updateRental = "UPDATE rental 
                            SET rental_status = ?,
                                processed_by = ?,
                                payment_status = ?
                            WHERE rental_id = ?";
            $stmt = $conn->prepare($updateRental);
            $stmt->bind_param("sisi", $new_status, $staff_id, $payment_status, $rental_id);
        } else {
            // Standard update without payment status change
            $updateRental = "UPDATE rental 
                            SET rental_status = ?,
                                processed_by = ?
                            WHERE rental_id = ?";
            $stmt = $conn->prepare($updateRental);
            $stmt->bind_param("sis", $new_status, $staff_id, $rental_id);
        }
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

        // If admin is updating payment status for an approved rental, add a payment record
        if ($payment_status !== null && $_SESSION['role'] === 'Admin' && $payment_status == 'paid' && $new_status == 'approved') {
            // Get rental details to calculate payment amount
            $getRental = "SELECT rental_fee, start_date, end_date FROM rental WHERE rental_id = ?";
            $stmt = $conn->prepare($getRental);
            $stmt->bind_param("i", $rental_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($rentalData = $result->fetch_assoc()) {
                $rental_fee = $rentalData['rental_fee'];
                
                // Insert payment record
                $insertPayment = "INSERT INTO payments (rental_id, amount, payment_date, payment_method, processed_by) 
                                VALUES (?, ?, NOW(), 'Admin processed', ?)";
                $stmt = $conn->prepare($insertPayment);
                $stmt->bind_param("idi", $rental_id, $rental_fee, $staff_id);
                $stmt->execute();
            }
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