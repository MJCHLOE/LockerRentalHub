<?php
session_start();
require_once '../db/database.php';
require_once '../admin_backend/log_actions.php'; // Ensure Logger is available
require_once '../backend/Notification.php'; // Notification System


function writeLog($msg) {
    file_put_contents(__DIR__ . '/debug_log.txt', date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

writeLog("--- New Request ---");

header('Content-Type: application/json');

// DEBUG: Disable error reporting after fix
error_reporting(0);
ini_set('display_errors', 0);

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    writeLog("Unauthorized access attempt. Role: " . ($_SESSION['role'] ?? 'None'));
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = file_get_contents("php://input");
writeLog("Raw Input: " . $input);
$data = json_decode($input, true);

if (!isset($data['rental_id']) || !isset($data['status'])) {
    writeLog("Missing parameters");
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$rental_id = $data['rental_id'];
$new_status = $data['status'];
$user_id = $_SESSION['user_id'];

writeLog("Processing Rental ID: $rental_id, New Status: $new_status, Admin ID: $user_id");

$valid_transition_statuses = ['approved', 'active', 'completed', 'cancelled', 'denied'];
if (!in_array($new_status, $valid_transition_statuses)) {
    writeLog("Invalid status: $new_status");
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $conn->begin_transaction();
    writeLog("Transaction started");

    // 1. Fetch current rental info
    $stmt = $conn->prepare("SELECT r.*, l.locker_id 
                          FROM rentals r -- New table name
                          JOIN lockers l ON r.locker_id = l.locker_id 
                          WHERE r.rental_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
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

    // --- NOTIFICATION LOGIC ---
    try {
        // Moved here to ensure it runs for ALL status changes (including archived ones)
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
    } catch (Exception $e) {
        // Non-critical error, just log it internally if possible or ignore
        writeLog("Notification Error: " . $e->getMessage());
    }

    // Determine if archiving is needed
    $is_archiving = in_array($new_status, ['completed', 'cancelled', 'denied']);

    if ($is_archiving) {
        // --- ARCHIVE LOGIC ---

        // 2. Insert into rental_archives
        $final_payment_status = $rental['payment_status'];
        if ($new_status === 'completed') {
             $final_payment_status = 'paid';
        }

        $archive_query = "INSERT INTO rental_archives 
                         (original_rental_id, user_id, locker_id, start_date, end_date, final_status, payment_status_at_archive, archived_at)
                         VALUES (?, ?, ?, ?, NOW(), ?, ?, NOW())";
        $stmt = $conn->prepare($archive_query);
        // Use rental_date as start_date for archive if column exists mapping
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
        // Use existing date_approved if available, else null
        $date_approved = isset($rental['date_approved']) ? $rental['date_approved'] : null;

        if ($new_status === 'approved') {
            // Set Approved Date to NOW
            $date_approved = date('Y-m-d H:i:s');
        } elseif ($new_status === 'active') {
             // Auto-set to Paid when Active
             $payment_status = 'paid';
        }

        $update_query = "UPDATE rentals SET status = ?, payment_status = ?, date_approved = ? WHERE rental_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $new_status, $payment_status, $date_approved, $rental_id);
        $stmt->execute();
        $stmt->close();

        // Update Locker Status
        // CHANGED: User requested locker becomes 'Occupied' upon 'Approved' (or 'Reserved' might be invalid ENUM)
        $locker_status = 'Occupied'; 
        if ($new_status === 'pending') {
             $locker_status = 'Vacant'; // Should not happen usually in this flow
        }
        
        $locker_stmt = $conn->prepare("UPDATE lockers SET status = ? WHERE locker_id = ?");
        // FIX: Two variables meant "ss", not "s"
        $locker_stmt->bind_param("ss", $locker_status, $locker_id);
        $locker_stmt->execute();
        $locker_stmt->close();

        // Calculate Start/End Date if becoming Active
        if ($new_status === 'active') {
             // Check if 'start_date' column exists inside rentals is tricky blindly.
             // We'll wrap this in try-catch to avoid crashing if schema mismatch
             try {
                $start_date = date('Y-m-d H:i:s');
                $end_date = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $date_stmt = $conn->prepare("UPDATE rentals SET start_date = ?, end_date = ? WHERE rental_id = ?");
                $date_stmt->bind_param("ssi", $start_date, $end_date, $rental_id);
                $date_stmt->execute();
                $date_stmt->close();
             } catch (Exception $e) {
                 writeLog("Date Update Error: " . $e->getMessage());
                 // Proceed anyway
             }
        }

        // Handle Auto-Deny for Approved
        $denied_count = 0;
        if ($new_status === 'approved') {
            try {
                // Find conflicts
                $conflict_query = "SELECT rental_id, user_id, rental_date, payment_status 
                                 FROM rentals 
                                 WHERE locker_id = ? AND rental_id != ? AND status = 'pending'";
                $c_stmt = $conn->prepare($conflict_query);
                $c_stmt->bind_param("si", $locker_id, $rental_id);
                $c_stmt->execute();
                $conflicts = $c_stmt->get_result();
                
                while ($conflict = $conflicts->fetch_assoc()) {
                    // Notifying conflicted users
                    try {
                        $notify->create($conflict['user_id'], "Rental Denied", "Your request for Locker $locker_id was denied because another request was approved.", "denied");
                    } catch(Exception $ex) {}
    
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
            } catch (Exception $e) {
                writeLog("Auto-Deny Error: " . $e->getMessage());
            }
        }

        $response_message = "Rental updated to $new_status";
        if ($denied_count > 0) {
            $response_message .= ". Auto-denied $denied_count other requests.";
        }
    }

    // 5. Log Action
    try {
        if (class_exists('SystemLogger')) {
            $logger = new SystemLogger($conn);
            $logger->logAction(
                'Update Rental',
                "Rental #$rental_id updated from '$current_status' to '$new_status'",
                'rental',
                (string)$rental_id
            );
        }
    } catch (Exception $e) {
        writeLog("Logging Error: " . $e->getMessage());
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $response_message]);

} catch (Exception $e) {
    if ($conn) $conn->rollback();
    $errInfo = $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
    writeLog("Exception: " . $errInfo);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $errInfo]);
}
?>