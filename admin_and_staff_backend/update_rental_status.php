<?php
require '../db/database.php';
$data = json_decode(file_get_contents("php://input"), true);

$rental_id = $data['rental_id'];
$new_status = $data['status'];

$response = [];

try {
    // Update rental status
    $stmt = $conn->prepare("UPDATE rental SET rental_status = ? WHERE rental_id = ?");
    $stmt->bind_param("si", $new_status, $rental_id);
    $stmt->execute();
    $stmt->close();

    // These statuses require locker status change
    $statusesThatAffectLocker = ['approved', 'cancelled', 'denied', 'completed'];

    if (in_array($new_status, $statusesThatAffectLocker)) {
        // Step 1: Get locker_id (VARCHAR)
        $stmt = $conn->prepare("SELECT locker_id FROM rental WHERE rental_id = ?");
        $stmt->bind_param("i", $rental_id);
        $stmt->execute();
        $stmt->bind_result($locker_id);
        $stmt->fetch();
        $stmt->close();

        // Step 2: Determine new locker status
        if ($new_status === 'approved') {
            $locker_status = 'reserved';
        } elseif (in_array($new_status, ['cancelled', 'denied', 'completed'])) {
            $locker_status = 'available';
        }

        // Step 3: Update locker table
        $stmt = $conn->prepare("UPDATE locker_units SET status = ? WHERE locker_id = ?");
        $stmt->bind_param("ss", $locker_status, $locker_id);
        $stmt->execute();
        $stmt->close();
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
