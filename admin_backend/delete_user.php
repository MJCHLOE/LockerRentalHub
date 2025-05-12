<?php
session_start();
require_once '../db/database.php'; 
require_once 'log_actions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT firstname, lastname, role FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $userData = $stmt->get_result()->fetch_assoc();
        if (!$userData) throw new Exception("User not found");

        $conn->begin_transaction();

        $stmt = $conn->prepare("SELECT COUNT(*) as rental_count FROM rental WHERE user_id = ? AND rental_status NOT IN ('completed', 'cancelled', 'denied')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['rental_count'] > 0) throw new Exception("Cannot delete user with active rentals");

        $role_id = null;
        if ($userData['role'] === 'Admin') {
            $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                $role_id = $row['admin_id'];
                $stmt = $conn->prepare("DELETE al FROM admin_logs al JOIN system_logs sl ON al.log_id = sl.log_id WHERE al.admin_id = ?");
                $stmt->bind_param("i", $role_id);
                if (!$stmt->execute()) throw new Exception("Failed to delete admin logs");
            }
        } elseif ($userData['role'] === 'Staff') {
            $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                $role_id = $row['staff_id'];
                $stmt = $conn->prepare("DELETE sl FROM staff_logs sl JOIN system_logs sl2 ON sl.log_id = sl2.log_id WHERE sl.staff_id = ?");
                $stmt->bind_param("i", $role_id);
                if (!$stmt->execute()) throw new Exception("Failed to delete staff logs");
            }
        } else {
            $stmt = $conn->prepare("SELECT client_id FROM clients WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                $role_id = $row['client_id'];
                $stmt = $conn->prepare("DELETE cl FROM client_logs cl JOIN system_logs sl ON cl.log_id = sl.log_id WHERE cl.client_id = ?");
                $stmt->bind_param("i", $role_id);
                if (!$stmt->execute()) throw new Exception("Failed to delete client logs");
            }
        }

        $stmt = $conn->prepare("DELETE FROM rental WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) throw new Exception("Failed to delete rentals");

        $stmt = $conn->prepare("DELETE FROM system_logs WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) throw new Exception("Failed to delete system logs");

        if ($userData['role'] === 'Admin') {
            $stmt = $conn->prepare("DELETE FROM admins WHERE user_id = ?");
        } elseif ($userData['role'] === 'Staff') {
            $stmt = $conn->prepare("DELETE FROM staff WHERE user_id = ?");
        } else {
            $stmt = $conn->prepare("DELETE FROM clients WHERE user_id = ?");
        }
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) throw new Exception("Failed to delete from role table");

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) throw new Exception("Failed to delete user");

        try {
            $logger = new SystemLogger($conn);
            $logger->logAction(
                'Delete User',
                "Deleted {$userData['role']}: {$userData['firstname']} {$userData['lastname']}",
                'user',
                $user_id
            );
        } catch (Exception $logEx) {
            error_log("Logging failed: " . $logEx->getMessage());
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

header("Location: ../admin/dashboard.php");
exit();

?>