<?php
// Include database connection
require '../db/database.php';

// Set current user ID for trigger functionality
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SET @current_user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
}

// Query base
$query = "SELECT l.locker_id, l.size as size_name, l.status as status_name, l.price
          FROM lockers l";

// Filter handling
if (isset($_GET['filter']) && $_GET['filter'] !== 'All' && in_array($_GET['filter'], ['Vacant', 'Occupied', 'Maintenance', 'Reserved'])) {
    $filter = $conn->real_escape_string($_GET['filter']);
    $query .= " WHERE l.status = '$filter'";
}

$query .= " ORDER BY l.locker_id";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Set row color or badge color based on status
        $badgeClass = "bg-secondary";
        switch($row['status_name']) {
            case 'Vacant':
                $badgeClass = "bg-success";
                break;
            case 'Occupied':
                $badgeClass = "bg-info";
                break;
            case 'Maintenance':
                $badgeClass = "bg-warning text-dark";
                break;
            case 'Reserved':
                $badgeClass = "bg-primary";
                break;
        }

        echo "<tr class='locker-row' data-status='{$row['status_name']}'>";
        echo "<td>{$row['locker_id']}</td>";
        echo "<td>{$row['size_name']}</td>";
        echo "<td><span class='status-badge {$badgeClass} text-white'>{$row['status_name']}</span></td>";
        echo "<td>₱" . number_format($row['price'], 2) . "</td>";
        
        // Actions only if admin calling (simple check or always show edit for this view)
        // For simplicity, we output buttons which might be hidden by staff CSS/JS if needed.
        // Assuming admin usage primarily for this file.
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