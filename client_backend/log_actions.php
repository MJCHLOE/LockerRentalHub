<?php
class ClientLogger {
    private $conn;
    private $user_id;
    private $client_id;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        // Get client_id from clients table
        if ($this->user_id) {
            $query = "SELECT client_id FROM clients WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $this->client_id = $row['client_id'];
            }
            $stmt->close();
        }
    }

    public function logProfileUpdate($field_type, $old_value, $new_value) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Create description message
            $description = "Updated profile: Changed $field_type from '$old_value' to '$new_value'";

            // Insert into system_logs
            $query = "INSERT INTO system_logs (user_id, action, description, entity_type, entity_id) 
                     VALUES (?, 'PROFILE_UPDATE', ?, 'CLIENT', ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iss", $this->user_id, $description, $this->client_id);
            $stmt->execute();

            // Get the last inserted log_id
            $log_id = $this->conn->insert_id;

            // Insert into client_logs
            $query = "INSERT INTO client_logs (log_id, client_id) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $log_id, $this->client_id);
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
            $query = "SELECT ls.size_name, l.price_per_month 
                     FROM lockerunits l 
                     JOIN lockersizes ls ON l.size_id = ls.size_id 
                     WHERE l.locker_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $locker_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $lockerDetails = $result->fetch_assoc();

            // Create description message
            $description = "Rental Request: Client requested to rent Locker #$locker_id " .
                         "(" . $lockerDetails['size_name'] . " - ₱" . 
                         number_format($lockerDetails['price_per_month'], 2) . "/month)";

            // Insert into system_logs
            $query = "INSERT INTO system_logs (user_id, action, description, entity_type, entity_id) 
                     VALUES (?, 'RENTAL_REQUEST', ?, 'LOCKER', ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iss", $this->user_id, $description, $locker_id);
            $stmt->execute();

            // Get the last inserted log_id
            $log_id = $this->conn->insert_id;

            // Insert into client_logs
            $query = "INSERT INTO client_logs (log_id, client_id) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $log_id, $this->client_id);
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