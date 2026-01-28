<?php
session_start();
require_once '../db/database.php';
require_once '../admin_backend/log_actions.php'; // Ensure Logger is available
require_once '../backend/Notification.php'; // Notification System

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['rental_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$rental_id = $data['rental_id'];
$new_status = $data['status'];
$user_id = $_SESSION['user_id'];

$valid_transition_statuses = ['approved', 'active', 'completed', 'cancelled', 'denied'];
if (!in_array($new_status, $valid_transition_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1. Fetch current rental info
    $stmt = $conn->prepare("SELECT r.*, l.locker_id 
                          FROM rentals r -- New table name
                          JOIN lockers l ON r.locker_id = l.locker_id 
                          WHERE r.rental_id = ?");
    $stmt->bind_param("i", $rental_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Rental not found (it might have been archived already)");
    }
    
    $rental = $result->fetch_assoc();
    $locker_id = $rental['locker_id'];
    $current_status = $rental['status']; // New column name
    $stmt->close();

    if ($current_status === $new_status) {
        echo json_encode(['success' => true, 'message' => 'No change in status']);
        exit;
    }

    // Determine if archiving is needed
    $is_archiving = in_array($new_status, ['completed', 'cancelled', 'denied']);

    if ($is_archiving) {
        // --- ARCHIVE LOGIC ---

        // 2. Insert into rental_archives
        // payment_status logic: if completing/approving, usually ensure paid.
        // For simplicity, we keep current payment status or force 'paid' if completing?
        // Let's assume manual payment toggle isn't here, we just use current or update if implied.
        // If 'completed', implies 'paid' usually? Let's treat 'active' -> 'completed' as final.
        
        $final_payment_status = $rental['payment_status'];
        if ($new_status === 'completed') {
             $final_payment_status = 'paid';
        }

        $archive_query = "INSERT INTO rental_archives 
                         (original_rental_id, user_id, locker_id, start_date, end_date, final_status, payment_status_at_archive, archived_at)
                         VALUES (?, ?, ?, ?, NOW(), ?, ?, NOW())";
        $stmt = $conn->prepare($archive_query);
        $stmt->bind_param("iissss", 
            $rental['rental_id'], 
            $rental['user_id'], 
            $rental['locker_id'], 
            $rental['rental_date'], 
            $new_status, 
            $final_payment_status
        );
        $stmt->execute();
        $stmt->close();

        // 3. Delete from rentals
        $del_stmt = $conn->prepare("DELETE FROM rentals WHERE rental_id = ?");
        $del_stmt->bind_param("i", $rental_id);
        $del_stmt->execute();
        $del_stmt->close();

        // 4. Update Locker Status to Vacant
        $locker_stmt = $conn->prepare("UPDATE lockers SET status = 'Vacant' WHERE locker_id = ?");
        $locker_stmt->bind_param("s", $locker_id);
        $locker_stmt->execute();
        $locker_stmt->close();

        $response_message = "Rental archived as $new_status";

    } else {
        // --- NORMAL UPDATE LOGIC (Pending -> Approved, Approved -> Active) ---
        
        $payment_status = $rental['payment_status'];
        // If moving to approved, might mark as paid? Or user does manually?
        // The old code assumed 'approved' -> 'paid'. Let's stick to that for simplicity if desired.
        // Or just keep it separate. Let's just update status.
        // Actually old code: "If approved, payment -> paid".
        if ($new_status === 'approved') {
            $payment_status = 'paid';
        }

        $update_query = "UPDATE rentals SET status = ?, payment_status = ? WHERE rental_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $new_status, $payment_status, $rental_id);
        $stmt->execute();
        $stmt->close();

        // Update Locker Status
        $locker_status = 'Reserved'; // Default for Pending/Approved
        if ($new_status === 'active') {
             $locker_status = 'Occupied';
        }
        
        $locker_stmt = $conn->prepare("UPDATE lockers SET status = ? WHERE locker_id = ?");
        $locker_stmt->bind_param("s", $locker_status, $locker_id);
        $locker_stmt->execute();
        $locker_stmt->close();

        // Calculate End Date if becoming Active
        if ($new_status === 'active') {
            // Default 30 days. You can make this dynamic later.
            $end_date = date('Y-m-d H:i:s', strtotime('+30 days'));
            $date_stmt = $conn->prepare("UPDATE rentals SET end_date = ? WHERE rental_id = ?");
            $date_stmt->bind_param("si", $end_date, $rental_id);
            $date_stmt->execute();
            $date_stmt->close();
        }

        // Notify User
        $notify = new Notification($conn);
        $msg = "";
        $title = "Rental Update";
        
        switch($new_status) {
            case 'approved': 
                $msg = "Good news! Your request for Locker {$rental['locker_id']} has been APPROVED. You can now pay to activate it."; 
                $title = "Rental Approved";
                break;
            case 'active': 
                $msg = "Success! Your Locker {$rental['locker_id']} is now ACTIVE. Access code sent separately."; 
                $title = "Rental Activated";
                break;
            case 'denied': 
                $msg = "Update: Your request for Locker {$rental['locker_id']} was denied."; 
                $title = "Rental Denied";
                break;
            case 'cancelled':
                $msg = "Your rental for Locker {$rental['locker_id']} has been cancelled.";
                $title = "Rental Cancelled";
                break;
             case 'completed':
                $msg = "Your rental for Locker {$rental['locker_id']} is now complete. Thank you!";
                $title = "Rental Complete";
                break;
        }
        
        if ($msg) {
            $notify->create($rental['user_id'], $title, $msg, $new_status);
        }

        // Handle Auto-Deny for Approved
        $denied_count = 0;
        if ($new_status === 'approved') {
            // Find conflicts
            $conflict_query = "SELECT rental_id, user_id, rental_date, payment_status 
                             FROM rentals 
                             WHERE locker_id = ? AND rental_id != ? AND status = 'pending'";
            $c_stmt = $conn->prepare($conflict_query);
            $c_stmt->bind_param("si", $locker_id, $rental_id);
            $c_stmt->execute();
            $conflicts = $c_stmt->get_result();
            
            while ($conflict = $conflicts->fetch_assoc()) {
                // Archive them as Denied
                $c_archive = "INSERT INTO rental_archives 
                             (original_rental_id, user_id, locker_id, start_date, end_date, final_status, payment_status_at_archive, archived_at)
                             VALUES (?, ?, ?, ?, NOW(), 'denied', ?, NOW())";
                $ca_stmt = $conn->prepare($c_archive);
                $ca_stmt->bind_param("iisss", 
                    $conflict['rental_id'], 
                    $conflict['user_id'], 
                    $locker_id, 
                    $conflict['rental_date'], 
                    $conflict['payment_status']
                );
                $ca_stmt->execute();
                $ca_stmt->close();

                // Delete
                $cd_stmt = $conn->prepare("DELETE FROM rentals WHERE rental_id = ?");
                $cd_stmt->bind_param("i", $conflict['rental_id']);
                $cd_stmt->execute();
                $cd_stmt->close();
                
                $denied_count++;
            }
            $c_stmt->close();
        }

        $response_message = "Rental updated to $new_status";
        if ($denied_count > 0) {
            $response_message .= ". Auto-denied $denied_count other requests.";
        }
    }

    // 5. Log Action
    $logger = new SystemLogger($conn);
    $logger->logAction(
        'Update Rental',
        "Rental #$rental_id updated from '$current_status' to '$new_status'",
        'rental',
        $rental_id
    );

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $response_message]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>