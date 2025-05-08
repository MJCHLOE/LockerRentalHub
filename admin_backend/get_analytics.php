<?php
require '../db/database.php';
    
    // Query to get analytics counts
    $query = "SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        COUNT(*) as total_lockers,
        COUNT(CASE WHEN lst.status_name = 'Occupied' THEN 1 END) as active_rentals,
        COUNT(CASE WHEN lst.status_name = 'Maintenance' THEN 1 END) as maintenance_count
        FROM lockerunits l
        JOIN lockerstatuses lst ON l.status_id = lst.status_id";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data found']);
    }
    
?>