<?php
class ClientLogger {
    private $conn;
    private $user_id;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    public function logProfileUpdate($field_type, $old_value, $new_value) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Create description message
            $description = "Updated profile: Changed $field_type from '$old_value' to '$new_value'";

            // Insert into system_logs
            $query = "INSERT INTO system_logs (user_id, action, description, entity_type, entity_id) 
                     VALUES (?, 'PROFILE_UPDATE', ?, 'client', ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iss", $this->user_id, $description, $this->user_id);
            $stmt->execute();

            // Commit transaction
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            // Rollback on error
            $this->conn->rollback();
            return false;
        }
    }

    public function logLockerRental($locker_id, $action_type) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Get locker details
            $query = "SELECT size as size_name, price 
                     FROM lockers 
                     WHERE locker_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $locker_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $lockerDetails = $result->fetch_assoc();

            // Create description message
            $description = "Rental Request: Client requested to rent Locker #$locker_id " .
                         "(" . $lockerDetails['size_name'] . " - ₱" . 
                         number_format($lockerDetails['price'], 2) . "/month)";

            // Insert into system_logs
            $query = "INSERT INTO system_logs (user_id, action, description, entity_type, entity_id) 
                     VALUES (?, 'RENTAL_REQUEST', ?, 'locker', ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iss", $this->user_id, $description, $locker_id);
            $stmt->execute();

            // Commit transaction
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            // Rollback on error
            $this->conn->rollback();
            return false;
        }
    }
}