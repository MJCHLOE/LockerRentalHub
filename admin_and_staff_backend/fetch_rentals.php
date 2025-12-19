<?php
session_start();
require '../db/database.php';

$isAdminOrStaff = isset($_SESSION['role']) && ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Staff');

try {
    // Check if we want archives
    $show_archives = isset($_GET['type']) && $_GET['type'] === 'archive';

    if ($show_archives) {
        $query = "SELECT r.archive_id as rental_id, 
                         r.user_id,
                         CONCAT(u.firstname, ' ', u.lastname) as client_name,
                         r.locker_id,
                         r.start_date as rental_date,
                         NULL as date_approved, -- Archives structure simplification
                         r.end_date as rent_ended_date,
                         r.final_status as rental_status,
                         r.payment_status_at_archive as payment_status
                  FROM rental_archives r
                  JOIN users u ON r.user_id = u.user_id
                  ORDER BY r.archived_at DESC";
    } else {
        $query = "SELECT r.rental_id, 
                         r.user_id,
                         CONCAT(u.firstname, ' ', u.lastname) as client_name,
                         r.locker_id,
                         r.rental_date,
                         NULL as date_approved, 
                         NULL as rent_ended_date,
                         r.status as rental_status,
                         r.payment_status
                  FROM rentals r
                  JOIN users u ON r.user_id = u.user_id
                  ORDER BY FIELD(r.status, 'pending', 'approved', 'active') DESC, r.rental_date DESC";
    }
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<tr><td colspan='9'>No records found.</td></tr>";
    } else {
        while ($row = $result->fetch_assoc()) {
            echo "<tr data-rental-id='{$row['rental_id']}' data-status='{$row['rental_status']}'>";
            echo "<td>{$row['rental_id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>{$row['client_name']}</td>";
            echo "<td>{$row['locker_id']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['rental_date'])) . "</td>";
            echo "<td>" . ($row['date_approved'] ? date('Y-m-d H:i', strtotime($row['date_approved'])) : '-') . "</td>";
            echo "<td>" . ($row['rent_ended_date'] ? date('Y-m-d H:i', strtotime($row['rent_ended_date'])) : '-') . "</td>";
            
            $statusClass = '';
            switch($row['rental_status']) {
                case 'pending': $statusClass = 'text-warning'; break;
                case 'approved': $statusClass = 'text-success'; break;
                case 'active': $statusClass = 'text-success'; break;
                case 'denied': $statusClass = 'text-danger'; break;
                case 'cancelled': $statusClass = 'text-secondary'; break;
                case 'completed': $statusClass = 'text-info'; break;
            }
            
            echo "<td class='{$statusClass}'>{$row['rental_status']}</td>";
            
            $paymentClass = $row['payment_status'] === 'paid' ? 'text-success' : 'text-danger';
            echo "<td class='{$paymentClass}'>{$row['payment_status']}</td>";
            
            // Actions only for active rentals in default view
            echo "<td>";
            if ($isAdminOrStaff && !$show_archives) {
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
    echo "<tr><td colspan='9'>Error fetching rentals: " . $e->getMessage() . "</td></tr>";
}
?>