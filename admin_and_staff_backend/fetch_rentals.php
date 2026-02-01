<?php
session_start();
require '../db/database.php';

$isAdminOrStaff = isset($_SESSION['role']) && ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Staff');

try {
    // Check for status filter
    // Filter can be 'all' or specific status
    $filter = isset($_GET['filter']) && $_GET['filter'] !== 'all' ? $_GET['filter'] : null;

    // Use UNION ALL to combine active rentals and archives
    // We normalize columns to match:
    // r.rental_id
    // r.user_id
    // client_name (joined)
    // r.locker_id
    // r.rental_date
    // r.date_approved
    // r.end_date (or rent_ended_date)
    // r.status (or final_status)
    // r.payment_status (or payment_status_at_archive)
    // r.total_price
    // source ('active' or 'archive') - useful for logic if needed

    $query = "
        SELECT 
            r.rental_id, 
            r.user_id,
            CONCAT(u.firstname, ' ', u.lastname) as client_name,
            r.locker_id,
            r.rental_date,
            r.date_approved, 
            r.end_date,
            r.status as rental_status,
            r.payment_status,
            r.total_price,
            'active' as source
        FROM rentals r
        JOIN users u ON r.user_id = u.user_id
        
        UNION ALL
        
        SELECT 
            r.archive_id as rental_id, 
            r.user_id,
            CONCAT(u.firstname, ' ', u.lastname) as client_name,
            r.locker_id,
            r.start_date as rental_date,
            NULL as date_approved, 
            r.end_date,
            r.final_status as rental_status,
            r.payment_status_at_archive as payment_status,
            0.00 as total_price, -- Assuming price not always in archive or we don't care for history
            'archive' as source
        FROM rental_archives r
        JOIN users u ON r.user_id = u.user_id
    ";

    // If filtering, we wrap the UNION in a subquery or apply WHERE to both.
    // Applying to both is cleaner but harder to string build dynamically 2x.
    // Wrapping is easier.
    
    $finalQuery = "SELECT * FROM ($query) AS combined_rentals WHERE 1=1";
    
    if ($filter) {
        $finalQuery .= " AND rental_status = ?";
    }
    
    // Order by date desc
    $finalQuery .= " ORDER BY rental_date DESC";
              
    $stmt = $conn->prepare($finalQuery);
    
    if ($filter) {
        $stmt->bind_param("s", $filter);
    }
    
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<tr><td colspan='11' class='text-center'>No records found.</td></tr>";
    } else {
        while ($row = $result->fetch_assoc()) {
            
            // Re-map archive price if needed? 
            // For now, Archives have 0.00 in query above. If archive has price, update schema or query.
            // Assuming active rentals dictate price importance.
            
            $isArchive = ($row['source'] === 'archive');

            echo "<tr data-rental-id='{$row['rental_id']}' data-status='{$row['rental_status']}'>";
            echo "<td>{$row['rental_id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
            echo "<td>{$row['locker_id']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['rental_date'])) . "</td>";
            echo "<td>" . ($row['date_approved'] ? date('Y-m-d H:i', strtotime($row['date_approved'])) : '-') . "</td>";
            echo "<td>" . ($row['end_date'] ? date('Y-m-d H:i', strtotime($row['end_date'])) : '-') . "</td>";
            
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
                 }
            } elseif ($row['rental_status'] === 'active') {
                $timeRemaining = "Indefinite";
            } elseif ($isArchive) {
                 // For history, maybe show 'Ended' or just '-'
                 $timeRemaining = "Ended";
            }
            
            echo "<td class='$color'>$timeRemaining</td>";
            
            $statusClass = '';
            switch(strtolower($row['rental_status'])) {
                case 'pending': $statusClass = 'badge badge-warning text-dark'; break;
                case 'approved': $statusClass = 'badge badge-success'; break;
                case 'active': $statusClass = 'badge badge-success'; break;
                case 'denied': $statusClass = 'badge badge-danger'; break;
                case 'cancelled': $statusClass = 'badge badge-secondary'; break;
                case 'completed': $statusClass = 'badge badge-info'; break;
            }
            
            echo "<td><span class='{$statusClass}'>" . ucfirst($row['rental_status']) . "</span></td>";
            
            $paymentClass = $row['payment_status'] === 'paid' ? 'text-success' : 'text-danger';
            echo "<td class='{$paymentClass} font-weight-bold'>{$row['payment_status']}</td>";
            
            // Price column - If archive, maybe we don't show it? Or show 0?
            // If active, show regular price
            $priceText = $isArchive ? "-" : "₱" . number_format($row['total_price'], 2);
            echo "<td>{$priceText}</td>";
            
            // Actions
            echo "<td>";
            // Buttons logic - ONLY for non-archived items usually
            if ($isAdminOrStaff && !$isArchive) {
                echo "<div class='btn-group' role='group'>";
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
            } elseif ($isArchive) {
                echo "<div class='btn-group' role='group'>";
                echo "<button class='btn btn-sm btn-info' onclick='viewReceipt({$row['rental_id']})' title='View Receipt'><iconify-icon icon='mdi:eye'></iconify-icon></button>";
                echo "</div>";
            }
            echo "</td>";
            echo "</tr>";
        }
    }

    $result->free();
    $stmt->close();
    
} catch (Exception $e) {
    echo "<tr><td colspan='11'>Error fetching rentals: " . $e->getMessage() . "</td></tr>";
}
?>