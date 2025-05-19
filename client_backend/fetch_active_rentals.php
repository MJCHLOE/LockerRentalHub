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
                     r.date_approved,
                     r.rental_status,
                     ps.status_name AS payment_status,
                     lu.price_per_month,
                     ls.size_name
              FROM rental r
              JOIN lockerunits lu ON r.locker_id = lu.locker_id
              JOIN lockersizes ls ON lu.size_id = ls.size_id
              JOIN paymentstatus ps ON r.payment_status_id = ps.payment_status_id
              WHERE r.user_id = ? 
              AND r.rental_status = 'active'  
              ORDER BY r.rental_date DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            error_log("Payment Status for rental_id {$row['rental_id']}: " . var_export($row['payment_status'], true));

            $paymentStatusDisplay = ucfirst(strtolower($row['payment_status'] ?? 'unknown'));
            $paymentClass = strtolower($row['payment_status'] ?? 'unknown') === 'paid' ? 'success' : 'warning';

            echo "<tr>";
            echo "<td>{$row['locker_id']}</td>";
            echo "<td>{$row['size_name']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['rental_date'])) . "</td>";
            echo "<td>" . (!is_null($row['date_approved']) ? date('Y-m-d H:i', strtotime($row['date_approved'])) : 'None') . "</td>";
            echo "<td><span class='badge badge-success'>Active</span></td>";
            echo "<td><span class='badge badge-{$paymentClass}'>{$paymentStatusDisplay}</span></td>";
            echo "<td>₱" . number_format($row['price_per_month'], 2) . "</td>";
            echo "<td>
                    <button class='btn btn-danger btn-sm' onclick='terminateRental({$row['rental_id']})'>
                        Terminate
                    </button>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='8' class='text-center'>No active rentals found</td></tr>";
    }

    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error in fetch_active_rentals.php: " . $e->getMessage());
    echo "<tr><td colspan='8' class='text-center'>Error: " . $e->getMessage() . "</td></tr>";
}
?>