<?php
session_start(); // Make sure session is started
require '../db/database.php';

// Check if user is admin or staff
$isAdminOrStaff = isset($_SESSION['role']) && ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Staff');

try {
    $query = "SELECT r.rental_id, 
                     CONCAT(u.firstname, ' ', u.lastname) as client_name,
                     r.locker_id,
                     r.rental_date,
                     r.rental_status,
                     r.payment_status,
                     r.processed_by,
                     CONCAT(p.firstname, ' ', p.lastname) as processor_name
              FROM rental r
              JOIN users u ON r.user_id = u.user_id
              LEFT JOIN users p ON r.processed_by = p.user_id
              ORDER BY r.rental_date DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Fetch data using mysqli_fetch_assoc
    while ($row = $result->fetch_assoc()) {
        echo "<tr data-rental-id='{$row['rental_id']}' data-status='{$row['rental_status']}' data-payment='{$row['payment_status']}'>";
        echo "<td>{$row['rental_id']}</td>";
        echo "<td>{$row['client_name']}</td>";
        echo "<td>{$row['locker_id']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($row['rental_date'])) . "</td>";
        
        // Add specific classes for different statuses
        $statusClass = '';
        switch($row['rental_status']) {
            case 'pending': $statusClass = 'text-warning'; break;
            case 'approved': $statusClass = 'text-success'; break;
            case 'active': $statusClass = 'text-success'; break;
            case 'denied': $statusClass = 'text-danger'; break;
            case 'cancelled': $statusClass = 'text-secondary'; break;
            case 'completed': $statusClass = 'text-info'; break;
        }
        
        echo "<td data-status='{$row['rental_status']}' class='{$statusClass}'>{$row['rental_status']}</td>";
        
        // Payment status with class
        $paymentClass = $row['payment_status'] === 'paid' ? 'text-success' : 'text-danger';
        echo "<td class='{$paymentClass}'>{$row['payment_status']}</td>";
        
        // Display processor if available
        echo "<td>" . ($row['processor_name'] ?? 'N/A') . "</td>";
        
        echo "<td>";
        
        // Add buttons based on rental status and user role
        if ($isAdminOrStaff) {
            switch($row['rental_status']) {
                case 'pending':
                    echo "<button class='btn btn-sm btn-success mr-1' onclick='updateRentalStatus({$row['rental_id']}, \"approved\")'>Approve</button>";
                    echo "<button class='btn btn-sm btn-danger' onclick='updateRentalStatus({$row['rental_id']}, \"denied\")'>Deny</button>";
                    break;
                    
                case 'active':
                    echo "<button class='btn btn-sm btn-info mr-1' onclick='updateRentalStatus({$row['rental_id']}, \"completed\")'>Complete</button>";
                    
                    if ($_SESSION['role'] === 'Admin') {
                        echo "<button class='btn btn-sm btn-secondary ml-1' onclick='updateRentalStatus({$row['rental_id']}, \"cancelled\")'>Cancel</button>";
                    }
                    break;
            }
        }
        
        echo "</td>";
        echo "</tr>";
    }

    // Close the statement
    $stmt->close();
    
} catch (Exception $e) {
    echo "<tr><td colspan='8'>Error fetching rentals: " . $e->getMessage() . "</td></tr>";
}
?>