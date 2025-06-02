<?php
session_start();
require '../db/database.php';

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

$valid_statuses = ['pending', 'approved', 'active', 'denied', 'cancelled', 'completed'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$response = [];

try {
    $conn->begin_transaction();
    
    $stmt = $conn->prepare("SELECT r.locker_id, r.rental_status, ps.status_name as payment_status, r.payment_status_id 
                          FROM rental r
                          JOIN paymentstatus ps ON r.payment_status_id = ps.payment_status_id 
                          WHERE r.rental_id = ?");
    $stmt->bind_param("i", $rental_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Rental not found");
    }
    
    $rental_info = $result->fetch_assoc();
    $locker_id = $rental_info['locker_id'];
    $current_status = $rental_info['rental_status'];
    $current_payment_status = $rental_info['payment_status'];
    $current_payment_status_id = $rental_info['payment_status_id'];
    $stmt->close();
    
    if ($current_status === $new_status) {
        echo json_encode(['success' => true, 'message' => 'No change in status']);
        exit;
    }
    
    $update_payment = false;
    $new_payment_status_id = $current_payment_status_id;
    $set_approval_date = false;
    
    if ($new_status === 'approved' && $current_status === 'pending') {
        $update_payment = true;
        $new_payment_status_id = 2; // 'paid'
        $set_approval_date = true;
    }
    
    $set_end_date = in_array($new_status, ['completed', 'denied', 'cancelled']);
    
    if ($update_payment && $set_approval_date && $set_end_date) {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ?, payment_status_id = ?, date_approved = NOW(), rent_ended_date = NOW() WHERE rental_id = ?");
        $stmt->bind_param("sii", $new_status, $new_payment_status_id, $rental_id);
    } elseif ($update_payment && $set_approval_date) {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ?, payment_status_id = ?, date_approved = NOW() WHERE rental_id = ?");
        $stmt->bind_param("sii", $new_status, $new_payment_status_id, $rental_id);
    } elseif ($update_payment && $set_end_date) {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ?, payment_status_id = ?, rent_ended_date = NOW() WHERE rental_id = ?");
        $stmt->bind_param("sii", $new_status, $new_payment_status_id, $rental_id);
    } elseif ($set_approval_date && $set_end_date) {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ?, date_approved = NOW(), rent_ended_date = NOW() WHERE rental_id = ?");
        $stmt->bind_param("si", $new_status, $rental_id);
    } elseif ($update_payment) {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ?, payment_status_id = ? WHERE rental_id = ?");
        $stmt->bind_param("sii", $new_status, $new_payment_status_id, $rental_id);
    } elseif ($set_approval_date) {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ?, date_approved = NOW() WHERE rental_id = ?");
        $stmt->bind_param("si", $new_status, $rental_id);
    } elseif ($set_end_date) {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ?, rent_ended_date = NOW() WHERE rental_id = ?");
        $stmt->bind_param("si", $new_status, $rental_id);
    } else {
        $stmt = $conn->prepare("UPDATE rental SET rental_status = ? WHERE rental_id = ?");
        $stmt->bind_param("si", $new_status, $rental_id);
    }
    $stmt->execute();
    $stmt->close();
    
    // Auto-deny other pending reservations for the same locker when approving
    $denied_count = 0;
    if ($new_status === 'approved' && $current_status === 'pending') {
        // Find all other pending rentals for the same locker
        $other_rentals_stmt = $conn->prepare("SELECT rental_id FROM rental WHERE locker_id = ? AND rental_status = 'pending' AND rental_id != ?");
        $other_rentals_stmt->bind_param("si", $locker_id, $rental_id);
        $other_rentals_stmt->execute();
        $other_rentals_result = $other_rentals_stmt->get_result();
        
        $rental_ids_to_deny = [];
        while ($row = $other_rentals_result->fetch_assoc()) {
            $rental_ids_to_deny[] = $row['rental_id'];
        }
        $other_rentals_stmt->close();
        
        // Deny all other pending rentals for this locker
        if (!empty($rental_ids_to_deny)) {
            $placeholders = implode(',', array_fill(0, count($rental_ids_to_deny), '?'));
            $types = str_repeat('i', count($rental_ids_to_deny));
            
            $deny_stmt = $conn->prepare("UPDATE rental SET rental_status = 'denied', rent_ended_date = NOW() WHERE rental_id IN ($placeholders)");
            $deny_stmt->bind_param($types, ...$rental_ids_to_deny);
            $deny_stmt->execute();
            $denied_count = $deny_stmt->affected_rows;
            $deny_stmt->close();
            
            // Log the auto-denial for each rental
            foreach ($rental_ids_to_deny as $denied_rental_id) {
                $auto_deny_description = "Auto-denied rental #$denied_rental_id due to approval of rental #$rental_id for the same locker ($locker_id)";
                
                $auto_log_stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description, entity_type, entity_id, log_date) VALUES (?, ?, ?, ?, ?, NOW())");
                $auto_log_stmt->bind_param("issss", $user_id, $action, $auto_deny_description, $entity_type, $denied_rental_id);
                $auto_log_stmt->execute();
                $auto_log_id = $conn->insert_id;
                $auto_log_stmt->close();
                
                // Add role-specific logs for auto-denials
                if ($_SESSION['role'] === 'Admin') {
                    $admin_stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
                    $admin_stmt->bind_param("i", $user_id);
                    $admin_stmt->execute();
                    $admin_result = $admin_stmt->get_result();
                    $admin_row = $admin_result->fetch_assoc();
                    $admin_id = $admin_row['admin_id'];
                    $admin_stmt->close();
                    
                    $admin_auto_log_stmt = $conn->prepare("INSERT INTO admin_logs (log_id, admin_id) VALUES (?, ?)");
                    $admin_auto_log_stmt->bind_param("ii", $auto_log_id, $admin_id);
                    $admin_auto_log_stmt->execute();
                    $admin_auto_log_stmt->close();
                } elseif ($_SESSION['role'] === 'Staff') {
                    $staff_stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
                    $staff_stmt->bind_param("i", $user_id);
                    $staff_stmt->execute();
                    $staff_result = $staff_stmt->get_result();
                    $staff_row = $staff_result->fetch_assoc();
                    $staff_id = $staff_row['staff_id'];
                    $staff_stmt->close();
                    
                    $staff_auto_log_stmt = $conn->prepare("INSERT INTO staff_logs (log_id, staff_id) VALUES (?, ?)");
                    $staff_auto_log_stmt->bind_param("ii", $auto_log_id, $staff_id);
                    $staff_auto_log_stmt->execute();
                    $staff_auto_log_stmt->close();
                }
            }
        }
    }
    
    $locker_status_id = 1; // Vacant
    switch ($new_status) {
        case 'pending':
            $locker_status_id = 4; // Reserved
            break;
        case 'approved':
            $locker_status_id = 4; // Reserved
            break;
        case 'active':
            $locker_status_id = 2; // Occupied
            break;
        case 'completed':
        case 'denied':
        case 'cancelled':
            $locker_status_id = 1; // Vacant
            break;
    }
    
    $stmt = $conn->prepare("UPDATE lockerunits SET status_id = ? WHERE locker_id = ?");
    $stmt->bind_param("is", $locker_status_id, $locker_id);
    $stmt->execute();
    $stmt->close();
    
    $action = "Update Rental";
    $description = "Updated rental #$rental_id status from '$current_status' to '$new_status'";
    if ($update_payment) {
        $description .= " and payment status to 'paid'";
    }
    if ($set_approval_date) {
        $description .= " and set approval date";
    }
    if ($set_end_date) {
        $description .= " and marked rental as ended";
    }
    if ($denied_count > 0) {
        $description .= " and auto-denied $denied_count conflicting rental(s)";
    }
    
    $entity_type = "rental";
    $entity_id = $rental_id;
    
    $log_stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description, entity_type, entity_id, log_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $log_stmt->bind_param("issss", $user_id, $action, $description, $entity_type, $entity_id);
    $log_stmt->execute();
    $log_id = $conn->insert_id;
    $log_stmt->close();
    
    if ($_SESSION['role'] === 'Admin') {
        $admin_stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
        $admin_stmt->bind_param("i", $user_id);
        $admin_stmt->execute();
        $admin_result = $admin_stmt->get_result();
        $admin_row = $admin_result->fetch_assoc();
        $admin_id = $admin_row['admin_id'];
        $admin_stmt->close();
        
        $admin_log_stmt = $conn->prepare("INSERT INTO admin_logs (log_id, admin_id) VALUES (?, ?)");
        $admin_log_stmt->bind_param("ii", $log_id, $admin_id);
        $admin_log_stmt->execute();
        $admin_log_stmt->close();
    } elseif ($_SESSION['role'] === 'Staff') {
        $staff_stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
        $staff_stmt->bind_param("i", $user_id);
        $staff_stmt->execute();
        $staff_result = $staff_stmt->get_result();
        $staff_row = $staff_result->fetch_assoc();
        $staff_id = $staff_row['staff_id'];
        $staff_stmt->close();
        
        $staff_log_stmt = $conn->prepare("INSERT INTO staff_logs (log_id, staff_id) VALUES (?, ?)");
        $staff_log_stmt->bind_param("ii", $log_id, $staff_id);
        $staff_log_stmt->execute();
        $staff_log_stmt->close();
    }
    
    $payment_stmt = $conn->prepare("SELECT status_name FROM paymentstatus WHERE payment_status_id = ?");
    $payment_stmt->bind_param("i", $new_payment_status_id);
    $payment_stmt->execute();
    $payment_result = $payment_stmt->get_result();
    $payment_row = $payment_result->fetch_assoc();
    $payment_status_name = $payment_row['status_name'];
    $payment_stmt->close();
    
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = "Rental status updated successfully";
    if ($update_payment) {
        $response['message'] .= " and payment marked as paid";
    }
    if ($set_approval_date) {
        $response['message'] .= " and approval date set";
    }
    if ($set_end_date) {
        $response['message'] .= " and rent ended date set";
    }
    if ($denied_count > 0) {
        $response['message'] .= ". Automatically denied $denied_count conflicting reservation(s)";
    }
    $response['new_status'] = $new_status;
    if ($update_payment) {
        $response['payment_status'] = $payment_status_name;
    }
    $response['locker_status_id'] = $locker_status_id;
    if ($denied_count > 0) {
        $response['auto_denied_count'] = $denied_count;
    }

} catch (Exception $e) {
    $conn->rollback();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>