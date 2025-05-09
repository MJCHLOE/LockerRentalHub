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
    // Parse JSON input for JavaScript fetch API
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fallback to traditional form POST if JSON parsing fails
        $input = $_POST;
    }
    
    $rental_id = $input['rental_id'];
    $new_status = $input['status'];
    $staff_id = $_SESSION['user_id'];
    
    // Get payment status if provided (for admin payment updates)
    $payment_status = isset($input['payment_status']) ? $input['payment_status'] : null;
    
    try {
        $conn->begin_transaction();

        // When approving a rental, automatically set payment status to 'paid' and change status to 'active'
        if ($new_status === 'approved') {
            $new_status = 'active';
            $payment_status = 'paid';
        }

        // Set the current user ID for the trigger to use
        $setUserVar = "SET @current_user_id = ?";
        $stmt = $conn->prepare($setUserVar);
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();

        // Validate that the status exists in the rentalstatus table
        $validateStatus = "SELECT COUNT(*) as count FROM rentalstatus WHERE status_name = ?";
        $stmt = $conn->prepare($validateStatus);
        $stmt->bind_param("s", $new_status);
        $stmt->execute();
        $result = $stmt->get_result();
        $statusExists = $result->fetch_assoc()['count'] > 0;
        
        if (!$statusExists && $new_status !== 'active') {
            throw new Exception("Invalid rental status: $new_status");
        }

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
                            WHEN ? = 'active' THEN 2 -- Occupied (status_id = 2 from your DB schema)
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
            
            // Get log ID and map to appropriate role-based log tables
            $log_id = $conn->insert_id;
            
            if ($_SESSION['role'] === 'Admin') {
                // Get admin_id from the user_id
                $getAdminId = "SELECT admin_id FROM admins WHERE user_id = ?";
                $stmt = $conn->prepare($getAdminId);
                $stmt->bind_param("i", $staff_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $adminInfo = $result->fetch_assoc();
                
                if ($adminInfo) {
                    $admin_id = $adminInfo['admin_id'];
                    $insertAdminLog = "INSERT INTO admin_logs (log_id, admin_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($insertAdminLog);
                    $stmt->bind_param("ii", $log_id, $admin_id);
                    $stmt->execute();
                }
            } elseif ($_SESSION['role'] === 'Staff') {
                // Get staff_id from the user_id
                $getStaffId = "SELECT staff_id FROM staff WHERE user_id = ?";
                $stmt = $conn->prepare($getStaffId);
                $stmt->bind_param("i", $staff_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $staffInfo = $result->fetch_assoc();
                
                if ($staffInfo) {
                    $staff_id = $staffInfo['staff_id'];
                    $insertStaffLog = "INSERT INTO staff_logs (log_id, staff_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($insertStaffLog);
                    $stmt->bind_param("ii", $log_id, $staff_id);
                    $stmt->execute();
                }
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