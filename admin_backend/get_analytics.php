<?php
require '../db/database.php';
    
    //Query to get analytics counts
    $query = "SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM lockers) as total_lockers,
        (SELECT COUNT(*) FROM rental WHERE rental_status = 'active') as active_rentals,
        (SELECT COUNT(*) FROM lockers WHERE status = 'Maintenance') as maintenance_count";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data found']);
    }
    
?>