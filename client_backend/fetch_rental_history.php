<?php
require '../db/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

try {
    $query = "SELECT r.archive_id as rental_id, 
                     r.locker_id,
                     r.start_date as rental_date,
                     NULL as date_approved, -- Archives don't have this column, or it's implicitly part of history
                     r.end_date as rent_ended_date,
                     r.final_status as rental_status,
                     r.payment_status_at_archive as payment_status,
                     l.price,
                     l.size as size_name
              FROM rental_archives r
              JOIN lockers l ON r.locker_id = l.locker_id
              WHERE r.user_id = ? 
              ORDER BY r.archived_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $statusClass = '';
            switch($row['rental_status']) {
                case 'completed': $statusClass = 'text-success'; break;
                case 'denied': $statusClass = 'text-danger'; break;
                case 'cancelled': $statusClass = 'text-warning'; break;
            }
            
            $paymentStatus = ucfirst(strtolower($row['payment_status']));
            
            echo "<tr>";
            echo "<td>{$row['locker_id']}</td>";
            echo "<td>{$row['size_name']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['rental_date'])) . "</td>";
            echo "<td>-</td>"; // No date_approved in archives display
            echo "<td>" . (!is_null($row['rent_ended_date']) ? date('Y-m-d H:i', strtotime($row['rent_ended_date'])) : "None") . "</td>";
            echo "<td class='{$statusClass}'>{$row['rental_status']}</td>";
            echo "<td>{$paymentStatus}</td>";
            echo "<td>₱" . number_format($row['price'], 2) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='8' class='text-center'>No rental history found</td></tr>";
    }

    $stmt->close();
    
} catch (Exception $e) {
    echo "<tr><td colspan='8' class='text-center'>Error: " . $e->getMessage() . "</td></tr>";
}
?>