<?php
session_start();
require_once '../db/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die('Unauthorized access');
}

// Get filter and search parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

function fetchSystemLogs($conn, $filter = 'all', $search = '', $limit = 50) {
    try {
        // query base
        $query = "SELECT sl.log_id, sl.action, sl.description, sl.entity_type, 
                        sl.entity_id, sl.log_date, u.username, u.role,
                        CONCAT(u.firstname, ' ', u.lastname) as full_name
                 FROM system_logs sl
                 JOIN users u ON sl.user_id = u.user_id 
                 WHERE 1=1 ";

        // Add filter conditions
        if ($filter !== 'all') {
            $role = ucfirst($filter); // admin -> Admin, staff -> Staff, client -> Client
            $query .= " AND u.role = ? ";
        }

        // Add search condition if search term exists
        if (!empty($search)) {
            $query .= " AND (sl.action LIKE ? OR sl.description LIKE ? 
                      OR u.username LIKE ? OR u.role LIKE ?) ";
        }

        $query .= "ORDER BY sl.log_date DESC LIMIT ?";
        
        $stmt = $conn->prepare($query);

        $types = "";
        $params = [];

        if ($filter !== 'all') {
            $types .= "s";
            $params[] = $role;
        }

        if (!empty($search)) {
            $searchTerm = "%$search%";
            $types .= "ssss";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $types .= "i";
        $params[] = $limit;

        $stmt->bind_param($types, ...$params);

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo "<tr><td colspan='5' class='text-center'>No logs found</td></tr>";
            return;
        }

        while ($log = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars(date('Y-m-d H:i:s', strtotime($log['log_date']))) . "</td>";
            echo "<td>" . htmlspecialchars($log['action']) . "</td>";
            echo "<td>" . htmlspecialchars($log['description']) . "</td>";
            echo "<td>" . htmlspecialchars($log['full_name']) . 
                 " (" . htmlspecialchars($log['role']) . ")</td>";
            echo "<td>" . htmlspecialchars($log['entity_type']) . ": " . 
                 htmlspecialchars($log['entity_id']) . "</td>";
            echo "</tr>";
        }

    } catch (Exception $e) {
        error_log("Error fetching logs: " . $e->getMessage());
        echo "<tr><td colspan='5' class='text-center text-danger'>Error loading logs</td></tr>";
    }
}

// Execute the function when this file is called directly
fetchSystemLogs($conn, $filter, $search);