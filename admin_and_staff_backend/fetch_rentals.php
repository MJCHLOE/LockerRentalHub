<?php
session_start();
require '../db/database.php';

$isAdminOrStaff = isset($_SESSION['role']) && ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Staff');

try {
    $query = "SELECT r.rental_id, 
                     r.user_id,
                     CONCAT(u.firstname, ' ', u.lastname) as client_name,
                     r.locker_id,
                     r.rental_date,
                     r.date_approved,
                     r.rent_ended_date,
                     r.rental_status,
                     r.payment_status_id,
                     ps.status_name as payment_status
              FROM rental r
              JOIN users u ON r.user_id = u.user_id
              JOIN paymentstatus ps ON r.payment_status_id = ps.payment_status_id
              ORDER BY FIELD(r.rental_status, 'pending', 'approved', 'active', 'denied', 'completed', 'cancelled'), 
                       r.rental_date DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<tr><td colspan='10'>No rental records found.</td></tr>";
    } else {
        while ($row = $result->fetch_assoc()) {
            echo "<tr data-rental-id='{$row['rental_id']}' data-user-id='{$row['user_id']}' data-status='{$row['rental_status']}' data-payment='{$row['payment_status']}'>";
            echo "<td>{$row['rental_id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>{$row['client_name']}</td>";
            echo "<td>{$row['locker_id']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['rental_date'])) . "</td>";
            echo "<td>" . (!is_null($row['date_approved']) ? date('Y-m-d H:i', strtotime($row['date_approved'])) : 'None') . "</td>";
            echo "<td>" . (!is_null($row['rent_ended_date']) ? date('Y-m-d H:i', strtotime($row['rent_ended_date'])) : "None") . "</td>";
            
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
            
            $paymentClass = $row['payment_status'] === 'paid' ? 'text-success' : 'text-danger';
            echo "<td class='{$paymentClass}'>{$row['payment_status']}</td>";
            
            echo "<td>";
            if ($isAdminOrStaff) {
                switch($row['rental_status']) {
                    case 'pending':
                        echo "<button class='btn btn-sm btn-success mr-1' onclick='updateRentalStatus({$row['rental_id']}, \"approved\")'>Approve</button>";
                        echo "<button class='btn btn-sm btn-danger' onclick='updateRentalStatus({$row['rental_id']}, \"denied\")'>Deny</button>";
                        break;
                    case 'approved':
                        echo "<button class='btn btn-sm btn-primary mr-1' onclick='updateRentalStatus({$row['rental_id']}, \"active\")'>Activate</button>";
                        echo "<button class='btn btn-sm btn-secondary' onclick='updateRentalStatus({$row['rental_id']}, \"cancelled\")'>Cancel</button>";
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
    }

    $result->free();
    $stmt->close();
    
} catch (Exception $e) {
    echo "<tr><td colspan='10'>Error fetching rentals: " . $e->getMessage() . "</td></tr>";
}
?>