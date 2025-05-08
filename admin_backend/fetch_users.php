<?php

require '../db/database.php';

// Modified query to include role-specific IDs
$sql = "SELECT u.user_id, u.username, u.firstname, u.lastname, u.email, u.phone_number, u.role, 
        CASE 
            WHEN u.role = 'Admin' THEN CONCAT('Admin #', a.admin_id)
            WHEN u.role = 'Staff' THEN CONCAT('Staff #', s.staff_id)
            WHEN u.role = 'Client' THEN CONCAT('Client #', c.client_id)
            ELSE NULL
        END as role_specific_id,
        CASE 
            WHEN u.role = 'Admin' THEN a.full_name
            WHEN u.role = 'Staff' THEN s.full_name
            WHEN u.role = 'Client' THEN c.full_name
            ELSE NULL
        END as full_name
        FROM users u
        LEFT JOIN admins a ON u.user_id = a.user_id AND u.role = 'Admin'
        LEFT JOIN staff s ON u.user_id = s.user_id AND u.role = 'Staff'
        LEFT JOIN clients c ON u.user_id = c.user_id AND u.role = 'Client'";

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
        echo "<tr>";
        // Display both User ID and role-specific ID
        echo "<td>" . htmlspecialchars("User #{$row['user_id']}") . 
             ($row['role_specific_id'] ? "<br>" . htmlspecialchars($row['role_specific_id']) : "") . "</td>";
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