<?php
require '../db/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

$user_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Base queries
// Active rentals (rentals table)
// Active rentals (rentals table)
$active_query = "SELECT rental_id as id, locker_id, rental_date, start_date, end_date, status, payment_status, 'current' as source
                 FROM rentals 
                 WHERE user_id = ?";

// Archived rentals (rental_archives table)
// Archived rentals (rental_archives table)
$archive_query = "SELECT archive_id as id, locker_id, start_date as rental_date, start_date, end_date, final_status as status, payment_status_at_archive as payment_status, 'archive' as source 
                  FROM rental_archives 
                  WHERE user_id = ?";

// Build final query based on filter
if ($status_filter === 'active' || $status_filter === 'pending') {
    $final_query = "SELECT r.*, l.size, l.price FROM ({$active_query}) r JOIN lockers l ON r.locker_id = l.locker_id WHERE 1=1";
    if ($status_filter === 'active') {
        $final_query .= " AND r.status IN ('active', 'approved')";
    } else { // pending
        $final_query .= " AND r.status = 'pending'";
    }
} elseif ($status_filter === 'history' || $status_filter === 'completed' || $status_filter === 'denied') {
    $final_query = "SELECT r.*, l.size, l.price FROM ({$archive_query}) r JOIN lockers l ON r.locker_id = l.locker_id WHERE 1=1";
    if ($status_filter === 'completed') {
        $final_query .= " AND r.status = 'completed'";
    } elseif ($status_filter === 'denied') {
        $final_query .= " AND r.status = 'denied'";
    }
} else {
    // All - UNION both
    $final_query = "SELECT r.*, l.size, l.price FROM (
                        ({$active_query}) 
                        UNION ALL 
                        ({$archive_query})
                    ) r 
                    JOIN lockers l ON r.locker_id = l.locker_id 
                    WHERE 1=1";
}

$final_query .= " ORDER BY r.rental_date DESC";

try {
    // Prepare logic is tricky with UNIONs and varying params if we use prepared statements strictly with variable filters.
    // For simplicity with this specific structure where user_id is the only param in subqueries:
    // We'll manually insert the user_id integer since it's from session (safeish) or better, bind it.
    // Given the complexity of dynamic UNION binding, we'll execute the subqueries separately if needed or construct the SQL carefully.
    
    // Actually, simpler approach:
    // We already put `user_id = ?` in the strings.
    // If it's UNION, we have two `?`. If it's single, one `?`.
    
    $params = [];
    $types = "";
    
    if ($status_filter === 'all') {
        $params[] = $user_id;
        $params[] = $user_id;
        $types = "ii";
    } else {
        $params[] = $user_id;
        $types = "i";
    }
    
    $stmt = $conn->prepare($final_query);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $statusClass = '';
            switch(strtolower($row['status'])) {
                case 'active': $statusClass = 'text-success font-weight-bold'; break;
                case 'approved': $statusClass = 'text-success'; break;
                case 'pending': $statusClass = 'text-warning'; break;
                case 'completed': $statusClass = 'text-info'; break;
                case 'denied': $statusClass = 'text-danger'; break;
                case 'cancelled': $statusClass = 'text-muted'; break;
            }
            
            $source = isset($row['source']) ? $row['source'] : 'unknown';
            $rental_id = $row['id'];
            
            echo "<tr>";
            echo "<td>{$row['locker_id']}</td>";
            echo "<td>{$row['size']}</td>";
            echo "<td>" . date('Y-m-d', strtotime($row['rental_date'])) . "</td>";

            echo "<td>" . date('Y-m-d', strtotime($row['end_date'])) . "</td>";

            // Time Remaining Logic
            $timeRemainingHtml = "<span class='text-muted'>-</span>";
            if (in_array(strtolower($row['status']), ['active', 'approved', 'pending']) && $row['end_date']) {
                $now = new DateTime();
                $end = new DateTime($row['end_date']);
                
                if ($now > $end) {
                    $timeRemainingHtml = "<span class='text-danger font-weight-bold'>Expired</span>";
                } else {
                    $timeRemainingHtml = "<span class='time-remaining' data-end-date='{$row['end_date']}'>Calculating...</span>";
                }
            }
            echo "<td>{$timeRemainingHtml}</td>";

            echo "<td class='{$statusClass}'>" . ucfirst($row['status']) . "</td>";
            echo "<td>₱" . number_format($row['price'], 2) . "</td>";
            
            // Actions
            echo "<td>";
            if ($row['status'] === 'pending') {
                echo "<button class='btn btn-sm btn-danger' onclick='cancelRental({$rental_id})'>Cancel</button>";
            } elseif ($row['status'] === 'active') {
                 echo "<button class='btn btn-sm btn-warning' onclick='terminateRental({$rental_id})'>Terminate</button>";
            } else {
                echo "<span class='text-muted'>-</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center'>No rentals found for this status.</td></tr>";
    }

} catch (Exception $e) {
    echo "<tr><td colspan='7' class='text-center text-danger'>Error: " . $e->getMessage() . "</td></tr>";
}
?>
