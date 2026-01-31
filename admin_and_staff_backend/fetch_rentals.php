<?php
session_start();
require '../db/database.php';

$isAdminOrStaff = isset($_SESSION['role']) && ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Staff');

try {
    // Check if we want archives
    $show_archives = isset($_GET['type']) && $_GET['type'] === 'archive';
    
    // Check for status filter
    // Filter can be 'all' or specific status
    $filter = isset($_GET['filter']) && $_GET['filter'] !== 'all' ? $_GET['filter'] : null;

    if ($show_archives) {
        $query = "SELECT r.archive_id as rental_id, 
                         r.user_id,
                         CONCAT(u.firstname, ' ', u.lastname) as client_name,
                         r.locker_id,
                         r.start_date as rental_date,
                         NULL as date_approved, 
                         r.end_date as rent_ended_date,
                         r.final_status as rental_status,
                         r.payment_status_at_archive as payment_status
                  FROM rental_archives r
                  JOIN users u ON r.user_id = u.user_id
                  WHERE 1=1";
        
        if ($filter) {
            $query .= " AND r.final_status = ?";
        }
        
        $query .= " ORDER BY r.archived_at DESC";
    } else {
        // Update query to include locker price
        $query = "SELECT r.rental_id, 
                         r.user_id,
                         CONCAT(u.firstname, ' ', u.lastname) as client_name,
                         r.locker_id,
                         r.rental_date,
                         r.date_approved, 
                         r.status as rental_status,
                         r.end_date,
                         r.payment_status as payment_status,
                         r.total_price
                  FROM rentals r
                  JOIN users u ON r.user_id = u.user_id
                  JOIN lockers l ON r.locker_id = l.locker_id
                  WHERE 1=1";
        
        if ($filter) {
            $query .= " AND r.status = ?";
        }
        
        $query .= " ORDER BY FIELD(r.status, 'pending', 'approved', 'active') DESC, r.rental_date DESC";
    }
              
    $stmt = $conn->prepare($query);
    
    if ($filter) {
        $stmt->bind_param("s", $filter);
    }
    
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<tr><td colspan='11' class='text-center'>No records found.</td></tr>";
    } else {
        while ($row = $result->fetch_assoc()) {
            // Calculate Price - REMOVED, using DB value
            $totalPrice = $row['total_price'];

            echo "<tr data-rental-id='{$row['rental_id']}' data-status='{$row['rental_status']}'>";
            echo "<td>{$row['rental_id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>{$row['client_name']}</td>";
            echo "<td>{$row['locker_id']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['rental_date'])) . "</td>";
            echo "<td>" . ($row['date_approved'] ? date('Y-m-d H:i', strtotime($row['date_approved'])) : '-') . "</td>";
            echo "<td>" . ($row['end_date'] ? date('Y-m-d H:i', strtotime($row['end_date'])) : '-') . "</td>";
            
            // Removed extra End Date column to align with dashboard headers
            
            // Time Remaining Calculation
            $timeRemaining = "-";
            $color = "";
            if ($row['rental_status'] === 'active' && $row['end_date']) {
                 $now = new DateTime();
                 $end = new DateTime($row['end_date']);
                 
                 if ($now > $end) {
                     $timeRemaining = "Expired";
                     $color = "text-danger font-weight-bold";
                 } else {
                     // Output for JS Timer
                     $timeRemaining = "<span class='time-remaining' data-end-date='{$row['end_date']}'>Calculating...</span>";
                     // Color will be handled by JS based on time left
                 }
            } elseif ($row['rental_status'] === 'active') {
                $timeRemaining = "Indefinite";
            } else if (isset($row['rent_ended_date'])) {
                $timeRemaining = "Ended: " . date('Y-m-d', strtotime($row['rent_ended_date']));
            }
            
            echo "<td class='$color'>$timeRemaining</td>";
            
            $statusClass = '';
            switch($row['rental_status']) {
                case 'pending': $statusClass = 'badge badge-warning text-dark'; break;
                case 'approved': $statusClass = 'badge badge-success'; break;
                case 'active': $statusClass = 'badge badge-success'; break;
                case 'denied': $statusClass = 'badge badge-danger'; break;
                case 'cancelled': $statusClass = 'badge badge-secondary'; break;
                case 'completed': $statusClass = 'badge badge-info'; break;
            }
            
            echo "<td><span class='{$statusClass}'>{$row['rental_status']}</span></td>";
            
            $paymentClass = $row['payment_status'] === 'paid' ? 'text-success' : 'text-danger';
            echo "<td class='{$paymentClass} font-weight-bold'>{$row['payment_status']}</td>";
            
            echo "<td>₱" . number_format($totalPrice, 2) . "</td>";
            
            // Actions
            echo "<td>";
            if ($isAdminOrStaff && !$show_archives) {
                echo "<div class='btn-group' role='group'>";
                // Calculate Price only if valid status for price
                switch($row['rental_status']) {
                    case 'pending':
                        echo "<button class='btn btn-sm btn-info' onclick='viewReceipt({$row['rental_id']})' title='View Receipt'><iconify-icon icon='mdi:eye'></iconify-icon></button>";
                        echo "<button class='btn btn-sm btn-success' onclick='updateRentalStatus({$row['rental_id']}, \"approved\")' title='Approve'><iconify-icon icon='mdi:check'></iconify-icon></button>";
                        echo "<button class='btn btn-sm btn-danger' onclick='updateRentalStatus({$row['rental_id']}, \"denied\")' title='Deny'><iconify-icon icon='mdi:close'></iconify-icon></button>";
                        break;
                    case 'approved':
                        echo "<button class='btn btn-sm btn-info' onclick='viewReceipt({$row['rental_id']})' title='View Receipt'><iconify-icon icon='mdi:eye'></iconify-icon></button>";
                        echo "<button class='btn btn-sm btn-primary' onclick='updateRentalStatus({$row['rental_id']}, \"active\")' title='Activate'><iconify-icon icon='mdi:play'></iconify-icon></button>";
                        echo "<button class='btn btn-sm btn-secondary' onclick='updateRentalStatus({$row['rental_id']}, \"cancelled\")' title='Cancel'><iconify-icon icon='mdi:cancel'></iconify-icon></button>";
                        break;
                    case 'active':
                        echo "<button class='btn btn-sm btn-info' onclick='viewReceipt({$row['rental_id']})' title='View Receipt'><iconify-icon icon='mdi:eye'></iconify-icon></button>";
                        echo "<button class='btn btn-sm btn-success' onclick='updateRentalStatus({$row['rental_id']}, \"completed\")' title='Complete'><iconify-icon icon='mdi:check-all'></iconify-icon></button>";
                        if ($_SESSION['role'] === 'Admin') {
                            echo "<button class='btn btn-sm btn-secondary' onclick='updateRentalStatus({$row['rental_id']}, \"cancelled\")' title='Cancel'><iconify-icon icon='mdi:cancel'></iconify-icon></button>";
                        }
                        break;
                    case 'completed': 
                         echo "<button class='btn btn-sm btn-info' onclick='viewReceipt({$row['rental_id']})' title='View Receipt'><iconify-icon icon='mdi:eye'></iconify-icon></button>";
                        break;
                }
                echo "</div>";
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