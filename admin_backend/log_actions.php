<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
            // Insert into system_logs
            $query = "INSERT INTO system_logs (user_id, action, description, entity_type, entity_id) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("issss", $this->user_id, $action, $description, $entity_type, $entity_id);
            $stmt->execute();
            
            return true;

        } catch (Exception $e) {
            // Log error silently or return false
            return false;
        }
    }
}