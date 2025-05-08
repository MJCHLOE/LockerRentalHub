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
        $query = "SELECT sl.log_id, sl.action, sl.description, sl.entity_type, 
                        sl.entity_id, sl.log_date, u.username, u.role,
                        CONCAT(u.firstname, ' ', u.lastname) as full_name
                 FROM system_logs sl
                 JOIN users u ON sl.user_id = u.user_id ";

        // Add filter conditions
        switch($filter) {
            case 'admin':
                $query .= "JOIN admin_logs al ON sl.log_id = al.log_id ";
                break;
            case 'staff':
                $query .= "JOIN staff_logs stl ON sl.log_id = stl.log_id ";
                break;
            case 'client':
                $query .= "JOIN client_logs cl ON sl.log_id = cl.log_id ";
                break;
        }

        // Add search condition if search term exists
        if (!empty($search)) {
            $query .= "WHERE (sl.action LIKE ? OR sl.description LIKE ? 
                      OR u.username LIKE ? OR u.role LIKE ?) ";
        }

        $query .= "ORDER BY sl.log_date DESC LIMIT ?";
        
        $stmt = $conn->prepare($query);

        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bind_param("ssssi", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit);
        } else {
            $stmt->bind_param("i", $limit);
        }

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