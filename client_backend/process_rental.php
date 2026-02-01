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

        // Check if user has ANY active, approved, or pending rental
        $checkActiveRental = "SELECT COUNT(*) as count FROM rentals WHERE user_id = ? AND status IN ('pending', 'approved', 'active')";
        $stmt = $conn->prepare($checkActiveRental);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            throw new Exception('You already have an active or pending rental. You cannot rent another locker until your current rental is completed or cancelled.');
        }

        // Check if locker is available for rent
        // Check if locker is available for rent
        $checkQuery = "SELECT status, price FROM lockers WHERE locker_id = ? FOR UPDATE";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $locker_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $locker = $result->fetch_assoc();
        if ($locker === null) {
            throw new Exception('Locker not found.');
        }
        if (!in_array($locker['status'], ['Vacant'])) {
            throw new Exception('This locker is not available for rent.');
        }

        if (!isset($_POST['start_date']) || !isset($_POST['end_date'])) {
            throw new Exception("Start and End dates are required.");
        }
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
            throw new Exception("Start date cannot be in the past.");
        }
        if (strtotime($end_date) <= strtotime($start_date)) {
            throw new Exception("End date must be after start date.");
        }

        // Calculate Total Price
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $days = $interval->days;
        
        // Logic: minimal 1 month pricing, prorated or block? 
        // User asked "depending how many months". Usually means blocks of months.
        // Let's assume standard logic: ceil(days / 30) * price.
        $months = ceil($days / 30);
        if ($months < 1) $months = 1;
        
        $total_price = $locker['price'] * $months;

        // Insert into rentals table
        $insertQuery = "INSERT INTO rentals (user_id, locker_id, rental_date, end_date, status, payment_status, total_price) 
                       VALUES (?, ?, ?, ?, 'pending', 'unpaid', ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("isssd", $user_id, $locker_id, $start_date, $end_date, $total_price);
        $stmt->execute();

        // Update locker status to 'Reserved'
        $updateQuery = "UPDATE lockers SET status = 'Reserved' WHERE locker_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("s", $locker_id);
        $stmt->execute();

        // Log the rental request
        $logger = new ClientLogger($conn);
        $logger->logLockerRental($locker_id, 'REQUEST');

        // Notify Admins
        require_once '../backend/Notification.php';
        $notify = new Notification($conn);
        $notify->notifyAdmins(
            "New Rental Request", 
            "User ID $user_id has requested Locker $locker_id.", 
            "request"
        );
        $notify->notifyStaff(
            "New Rental Request", 
            "User ID $user_id has requested Locker $locker_id.", 
            "request"
        );


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