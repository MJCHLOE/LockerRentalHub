<?php
session_start();
require '../db/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Get active rentals count (approved rentals)
    $activeQuery = "SELECT COUNT(*) as active 
                   FROM rentals 
                   WHERE user_id = ? 
                   AND status = 'active'";
    $stmt = $conn->prepare($activeQuery);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $activeRentals = $result->fetch_assoc()['active'];
    $stmt->close();

    // Get pending requests count
    $pendingQuery = "SELECT COUNT(*) as pending 
                    FROM rentals 
                    WHERE user_id = ? 
                    AND status = 'pending'";
    $stmt = $conn->prepare($pendingQuery);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pendingRequests = $result->fetch_assoc()['pending'];
    $stmt->close();

    // Get available lockers count (status = 'Vacant' in lockers table)
    $availableQuery = "SELECT COUNT(*) as available 
                      FROM lockers 
                      WHERE status = 'Vacant'";
    $stmt = $conn->prepare($availableQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    $availableLockers = $result->fetch_assoc()['available'];
    $stmt->close();

    // Send the response
    echo json_encode([
        'success' => true,
        'stats' => [
            'activeRentals' => (int)$activeRentals,
            'pendingRequests' => (int)$pendingRequests,
            'availableLockers' => (int)$availableLockers
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in fetch_stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error fetching stats: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>