<?php
require '../db/database.php';
require_once 'log_actions.php';


// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Set current user ID for trigger functionality
$stmt = $conn->prepare("SET @current_user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

// Handle GET request to fetch locker data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $locker_id = $_GET['id'];
    
    $query = "SELECT l.*, ls.size_name, lst.status_name 
              FROM lockerunits l
              JOIN lockersizes ls ON l.size_id = ls.size_id
              JOIN lockerstatuses lst ON l.status_id = lst.status_id
              WHERE l.locker_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $locker_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Get all sizes and statuses for dropdowns
        $sizes = $conn->query("SELECT * FROM lockersizes");
        $statuses = $conn->query("SELECT * FROM lockerstatuses");
        
        echo "<form id='editLockerForm'>
                <input type='hidden' name='locker_id' value='{$row['locker_id']}'>
                
                <div class='form-group'>
                    <label>Locker ID</label>
                    <input type='text' class='form-control' value='{$row['locker_id']}' disabled>
                </div>
                
                <div class='form-group'>
                    <label>Size</label>
                    <select name='size_id' class='form-control' required>";
                    while ($size = $sizes->fetch_assoc()) {
                        $selected = ($size['size_id'] == $row['size_id']) ? 'selected' : '';
                        echo "<option value='{$size['size_id']}' {$selected}>{$size['size_name']}</option>";
                    }
        echo    "</select>
                </div>
                
                <div class='form-group'>
                    <label>Status</label>
                    <select name='status_id' class='form-control' required>";
                    while ($status = $statuses->fetch_assoc()) {
                        $selected = ($status['status_id'] == $row['status_id']) ? 'selected' : '';
                        echo "<option value='{$status['status_id']}' {$selected}>{$status['status_name']}</option>";
                    }
        echo    "</select>
                </div>
                
                <div class='form-group'>
                    <label>Price per Month</label>
                    <input type='number' step='0.01' name='price_per_month' class='form-control' value='{$row['price_per_month']}' required>
                </div>
            </form>";
    }
    exit;
}

// Handle POST request to update locker
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'error' => ''];
    
    try {
        $locker_id = $_POST['locker_id'];
        $size_id = $_POST['size_id'];
        $status_id = $_POST['status_id'];
        $price_per_month = $_POST['price_per_month'];
        
        $query = "UPDATE lockerunits 
                  SET size_id = ?, status_id = ?, price_per_month = ? 
                  WHERE locker_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iids", $size_id, $status_id, $price_per_month, $locker_id);
        
        if ($stmt->execute()) {
            // Fetch size name and status name
            $size_query = "SELECT size_name FROM lockersizes WHERE size_id = ?";
            $status_query = "SELECT status_name FROM lockerstatuses WHERE status_id = ?";
            
            $size_stmt = $conn->prepare($size_query);
            $size_stmt->bind_param("i", $size_id);
            $size_stmt->execute();
            $size_result = $size_stmt->get_result();
            $size_name = $size_result->fetch_assoc()['size_name'];
            
            $status_stmt = $conn->prepare($status_query);
            $status_stmt->bind_param("i", $status_id);
            $status_stmt->execute();
            $status_result = $status_stmt->get_result();
            $status_name = $status_result->fetch_assoc()['status_name'];
            
            $logger = new SystemLogger($conn);
            $logger->logAction(
                'Edit Locker',
                "Updated locker {$locker_id} - Size: {$size_name}, Status: {$status_name}, Price: ₱{$price_per_month}",
                'locker',
                $locker_id
            );
            
            $response['success'] = true;
            $response['message'] = "Locker updated successfully!";
        } else {
            throw new Exception("Error updating locker");
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
?>