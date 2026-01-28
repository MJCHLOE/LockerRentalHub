<?php
require '../db/database.php';
header('Content-Type: application/json');

// Query to get analytics counts matching the keys expected by JS
$query = "SELECT 
    (SELECT COUNT(*) FROM users) as totalUsers,
    (SELECT COUNT(*) FROM rentals WHERE status = 'active') as activeRentals,
    (SELECT COUNT(*) FROM lockers WHERE status = 'Maintenance') as lockersInMaintenance";

$result = $conn->query($query);

if ($result) {
    $data = $result->fetch_assoc();
    // Return keys at the root level alongside 'success'
    echo json_encode(array_merge(['success' => true], $data));
} else {
    echo json_encode(['success' => false, 'message' => 'Error fetching stats']);
}
?>
