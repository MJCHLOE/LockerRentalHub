<?php
// Include database connection
require '../db/database.php';

// Set current user ID for trigger functionality
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SET @current_user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
}

// Query to get all locker information with status and size names
$query = "SELECT l.locker_id, l.size as size_name, l.status as status_name, l.price_per_month
          FROM lockerunits l
          ORDER BY l.locker_id";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Set row color based on status
        $status_class = "";
        switch($row['status_name']) {
            case 'Vacant':
                $status_class = "bg-success";
                break;
            case 'Occupied':
                $status_class = "bg-info";
                break;
            case 'Maintenance':
                $status_class = "bg-warning";
                break;
        }

        echo "<tr class='{$status_class} locker-row' data-status='{$row['status_name']}'>";
        echo "<td>{$row['locker_id']}</td>";
        echo "<td>{$row['size_name']}</td>";
        echo "<td>{$row['status_name']}</td>";
        echo "<td>₱" . number_format($row['price_per_month'], 2) . "</td>";
        echo "<td class='text-center'>
                <button class='btn btn-primary btn-sm' onclick='editLocker(\"{$row['locker_id']}\")'>Edit</button>
                <button class='btn btn-danger btn-sm' onclick='confirmDeleteLocker(\"{$row['locker_id']}\")'>Delete</button>
              </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>No lockers found</td></tr>";
}
?>