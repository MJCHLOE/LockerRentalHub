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
                     r.rental_status,
                     r.payment_status_id,
                     lu.price_per_month,
                     ls.size_name
              FROM rental r
              JOIN lockerunits lu ON r.locker_id = lu.locker_id
              JOIN lockersizes ls ON lu.size_id = ls.size_id
              WHERE r.user_id = ? 
              AND r.rental_status = 'active'  -- Only show approved rentals
              ORDER BY r.rental_date DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['locker_id']}</td>";
            echo "<td>{$row['size_name']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['rental_date'])) . "</td>";
            echo "<td><span class='badge badge-success'>Active</span></td>";
            echo "<td><span class='badge badge-" . ($row['payment_status_id'] == 'paid' ? 'success' : 'warning') . "'>" 
                 . ucfirst($row['payment_status_id']) . "</span></td>";
            echo "<td>₱" . number_format($row['price_per_month'], 2) . "</td>";
            echo "<td>
                    <button class='btn btn-danger btn-sm' onclick='terminateRental({$row['rental_id']})'>
                        Terminate
                    </button>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center'>No active rentals found</td></tr>";
    }

    $stmt->close();
    
} catch (Exception $e) {
    echo "<tr><td colspan='7' class='text-center'>Error: " . $e->getMessage() . "</td></tr>";
}
?>