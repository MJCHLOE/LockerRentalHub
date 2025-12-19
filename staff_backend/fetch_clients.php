<?php

require '../db/database.php';

// Modified query to include client_id from clients table
$sql = "SELECT u.user_id, u.username, u.firstname, u.lastname, u.email, u.phone_number,
        CONCAT('Client #', u.user_id) as client_number
        FROM users u
        WHERE u.role = 'Client'
        ORDER BY u.user_id ASC";

$result = $conn->query($sql);

// Check if there are clients
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        // Display both User ID and Client ID with styling
        echo "<td>" . htmlspecialchars("User #{$row['user_id']}") . 
             "<br><span style='opacity: 0.7'>(" . htmlspecialchars($row['client_number']) . ")</span></td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>No clients found.</td></tr>";
}

$conn->close();
?>