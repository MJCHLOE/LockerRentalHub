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
    
    $query = "SELECT l.*, l.size as size_name, l.status as status_name 
              FROM lockerunits l
              WHERE l.locker_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $locker_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "<form id='editLockerForm'>
                <input type='hidden' name='locker_id' value='{$row['locker_id']}'>
                
                <div class='form-group'>
                    <label>Locker ID</label>
                    <input type='text' class='form-control' value='{$row['locker_id']}' disabled>
                </div>
                
                <div class='form-group'>
                    <label>Size</label>
                    <select name='size' class='form-control' required>
                        <option value='Small' " . ($row['size_name'] == 'Small' ? 'selected' : '') . ">Small</option>
                        <option value='Medium' " . ($row['size_name'] == 'Medium' ? 'selected' : '') . ">Medium</option>
                        <option value='Large' " . ($row['size_name'] == 'Large' ? 'selected' : '') . ">Large</option>
                    </select>
                </div>
                
                <div class='form-group'>
                    <label>Status</label>
                    <select name='status' class='form-control' required>
                        <option value='Vacant' " . ($row['status_name'] == 'Vacant' ? 'selected' : '') . ">Vacant</option>
                        <option value='Occupied' " . ($row['status_name'] == 'Occupied' ? 'selected' : '') . ">Occupied</option>
                        <option value='Maintenance' " . ($row['status_name'] == 'Maintenance' ? 'selected' : '') . ">Maintenance</option>
                        <option value='Reserved' " . ($row['status_name'] == 'Reserved' ? 'selected' : '') . ">Reserved</option>
                    </select>
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
        $size = $_POST['size'];
        $status = $_POST['status'];
        $price_per_month = $_POST['price_per_month'];
        
        $query = "UPDATE lockerunits 
                  SET size = ?, status = ?, price_per_month = ? 
                  WHERE locker_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssds", $size, $status, $price_per_month, $locker_id);
        
        if ($stmt->execute()) {
            $logger = new SystemLogger($conn);
            $logger->logAction(
                'Edit Locker',
                "Updated locker {$locker_id} - Size: {$size}, Status: {$status}, Price: ₱{$price_per_month}",
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