<?php
class SystemLogger {
    private $conn;
    private $user_id;

    public function __construct($conn) {
        $this->conn = $conn;
        // Get user_id from session if available
        $this->user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    public function logAction($action, $description, $entity_type, $entity_id) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Insert into system_logs first
            $query = "INSERT INTO system_logs (user_id, action, description, entity_type, entity_id) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("issss", $this->user_id, $action, $description, $entity_type, $entity_id);
            $stmt->execute();

            // Get the last inserted log_id
            $log_id = $this->conn->insert_id;

            // Based on user role, insert into the appropriate sub-table
            if (isset($_SESSION['role'])) {
                switch ($_SESSION['role']) {
                    case 'Admin':
                        $this->insertAdminLog($log_id);
                        break;
                    case 'Staff':
                        $this->insertStaffLog($log_id);
                        break;
                    case 'Client':
                        $this->insertClientLog($log_id);
                        break;
                }
            }

            // Commit transaction
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            // Rollback on error
            $this->conn->rollback();
            return false;
        }
    }

    private function insertAdminLog($log_id) {
        $query = "INSERT INTO admin_logs (log_id, admin_id) 
                 SELECT ?, admin_id FROM admins WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $log_id, $this->user_id);
        $stmt->execute();
    }

    private function insertStaffLog($log_id) {
        $query = "INSERT INTO staff_logs (log_id, staff_id) 
                 SELECT ?, staff_id FROM staff WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $log_id, $this->user_id);
        $stmt->execute();
    }

    private function insertClientLog($log_id) {
        $query = "INSERT INTO client_logs (log_id, client_id) 
                 SELECT ?, client_id FROM clients WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $log_id, $this->user_id);
        $stmt->execute();
    }
}