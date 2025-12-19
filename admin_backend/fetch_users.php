<?php
require '../db/database.php';

// Modified query to include role-specific IDs
$sql = "SELECT u.user_id, u.username, u.firstname, u.lastname, u.email, u.phone_number, u.role, 
        CONCAT(u.role, ' #', u.user_id) as role_specific_id,
        CONCAT(u.firstname, ' ', u.lastname) as full_name
        FROM users u";

// Check if a filter was requested
if (isset($_GET['filter']) && in_array($_GET['filter'], ['Admin', 'Staff', 'Client'])) {
    $filter = $_GET['filter'];
    $sql .= " WHERE u.role = '$filter'";
}

$sql .= " ORDER BY u.user_id ASC";

$result = $conn->query($sql);

// Check if there are users
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Add data-id attribute to the row for easier targeting
        echo "<tr data-id=\"{$row['user_id']}\">";
        // Display User ID and role-specific ID with parentheses and reduced opacity
        echo "<td>" . htmlspecialchars("User #{$row['user_id']}") . 
             ($row['role_specific_id'] ? "<br><span style='opacity: 0.7'>(" . htmlspecialchars($row['role_specific_id']) . ")</span>" : "") . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>
                <button class='btn btn-sm btn-primary' onclick='editUser({$row['user_id']})'>Edit</button>
                <button class='btn btn-sm btn-danger' onclick='deleteUser({$row['user_id']})'>Delete</button>
              </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7' class='text-center'>No users found.</td></tr>";
}

$conn->close();
?>