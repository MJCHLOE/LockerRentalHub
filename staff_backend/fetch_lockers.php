<?php
require '../db/database.php';


// Query to get all locker information with status and size names
$query = "SELECT l.locker_id, ls.size_name, lst.status_name, l.price_per_month
          FROM lockerunits l
          JOIN lockersizes ls ON l.size_id = ls.size_id
          JOIN lockerstatuses lst ON l.status_id = lst.status_id
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
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4' class='text-center'>No lockers found</td></tr>";
}

$conn->close();
?>