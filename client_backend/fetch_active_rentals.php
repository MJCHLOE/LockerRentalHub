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
                     NULL as date_approved, -- Simplified in new schema or null if not tracking approved date separate from status
                     r.status as rental_status,
                     r.payment_status,
                     l.price,
                     l.size as size_name
              FROM rentals r
              JOIN lockers l ON r.locker_id = l.locker_id
              WHERE r.user_id = ? 
              AND r.status = 'active'
              ORDER BY r.rental_date DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $paymentStatusDisplay = ucfirst(strtolower($row['payment_status']));
            $paymentClass = strtolower($row['payment_status']) === 'paid' ? 'success' : 'warning';

            echo "<tr>";
            echo "<td>{$row['locker_id']}</td>";
            echo "<td>{$row['size_name']}</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($row['rental_date'])) . "</td>";
            echo "<td>-</td>"; // Date approved not explicitly in new schema active/pending rentals table unless we added it? Schema has rental_id, user_id, locker_id, rental_date, status, payment_status. rental_date is request date? Or start date? Assume rental_date.
            echo "<td><span class='badge badge-success'>Active</span></td>";
            echo "<td><span class='badge badge-{$paymentClass}'>{$paymentStatusDisplay}</span></td>";
            echo "<td>₱" . number_format($row['price'], 2) . "</td>";
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