<?php
session_start();
require '../db/database.php';

// 1. Check if admin/staff is logged in
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff') {
    die('Not authorized');
}

// 2. Get the input data
$rental_id = (int)$_POST['rental_id'];
$new_status = $_POST['status'];
$user_id = (int)$_SESSION['user_id'];

// 3. Update the rental status
$conn->query("UPDATE rental SET rental_status = '$new_status', processed_by = $user_id WHERE rental_id = $rental_id");

// 4. Update locker status if needed
if ($new_status === 'approved' || $new_status === 'active') {
    $locker_id = $conn->query("SELECT locker_id FROM rental WHERE rental_id = $rental_id")->fetch_row()[0];
    $status_id = $conn->query("SELECT status_id FROM lockerstatuses WHERE status_name = 'Occupied'")->fetch_row()[0];
    $conn->query("UPDATE lockers SET status_id = $status_id WHERE locker_id = '$locker_id'");
} 
else if ($new_status === 'completed' || $new_status === 'denied' || $new_status === 'cancelled') {
    $locker_id = $conn->query("SELECT locker_id FROM rental WHERE rental_id = $rental_id")->fetch_row()[0];
    $status_id = $conn->query("SELECT status_id FROM lockerstatuses WHERE status_name = 'Vacant'")->fetch_row()[0];
    $conn->query("UPDATE lockers SET status_id = $status_id WHERE locker_id = '$locker_id'");
}

// 5. Return success
echo json_encode(['success' => true]);
?>