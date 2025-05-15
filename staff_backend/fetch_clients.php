<?php
session_start();

// Security check - only staff should access this
$staffSessionKey = md5('Staff_' . $_SESSION['user_id']);
if (!isset($_SESSION[$staffSessionKey]) || 
    !isset($_SESSION['role']) || 
    $_SESSION['role'] !== 'Staff') {
    echo "Unauthorized access";
    exit();
}

// Database connection
include_once '../backend/db_connect.php';

// Check if search parameter exists
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base SQL for fetching clients
$sql = "SELECT user_id, username, full_name, email, phone 
        FROM users 
        WHERE role = 'Client'";

// Add search condition if search parameter is provided
if (!empty($search)) {
    $searchQuery = "%$search%";
    $sql .= " AND (user_id LIKE ? OR 
                   username LIKE ? OR 
                   full_name LIKE ? OR 
                   email LIKE ? OR 
                   phone LIKE ?)";
    
    // Execute with search parameters
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $searchQuery, $searchQuery, $searchQuery, $searchQuery, $searchQuery);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Execute without search parameters
    $sql .= " ORDER BY user_id";
    $result = $conn->query($sql);
}

// Output results
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['user_id']}</td>
                <td>{$row['username']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['email']}</td>
                <td>{$row['phone']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>No clients found</td></tr>";
}

// Close connection
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>