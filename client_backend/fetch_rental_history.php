<?php
require '../db/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

try {
    $query = "SELECT r.rental_id, 
                     r.locker_id,
                     r.rental_date,
                     r.rent_ended_date,
                     r.rental_status,
                     r.payment_status_id,
                     lu.price_per_month,
                     ls.size_name
              FROM rental r
              JOIN lockerunits lu ON r.locker_id = lu.locker_id
              JOIN lockersizes ls ON lu.size_id = ls.size_id
              WHERE r.user_id = ? 
              AND r.rental_status IN ('completed', 'denied', 'cancelled')
              ORDER BY r.rental_date DESC";
              
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
            
            echo "<tr>";
            echo "<td>{$row['locker_id']}</td>";
            echo "<td>{$row['size_name']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['rental_date'])) . "</td>";
            
            // Display rent ended date or "None" if null
            echo "<td>";
            if (!is_null($row['rent_ended_date'])) {
                echo date('Y-m-d H:i', strtotime($row['rent_ended_date']));
            } else {
                echo "None";
            }
            echo "</td>";
            
            echo "<td class='{$statusClass}'>{$row['rental_status']}</td>";
            echo "<td>{$row['payment_status']}</td>";
            echo "<td>₱{$row['price_per_month']}</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center'>No rental history found</td></tr>";
    }

    $stmt->close();
    
} catch (Exception $e) {
    echo "<tr><td colspan='7' class='text-center'>Error: " . $e->getMessage() . "</td></tr>";
}
?>