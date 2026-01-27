<?php
class Notification {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // Create a new notification
    public function create($user_id, $title, $message, $type = 'info') {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $message, $type);
        return $stmt->execute();
    }

    // Notify all admins (useful for new requests)
    // Notify all admins (useful for new requests)
    public function notifyAdmins($title, $message, $type = 'info') {
        // Check for common admin role names
        $sql = "SELECT user_id FROM users WHERE role IN ('Admin', 'admin', 'Administrator')";
        $result = $this->conn->query($sql);
        
        $count = 0;
        if ($result && $result->num_rows > 0) {
            $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            while($row = $result->fetch_assoc()) {
                $stmt->bind_param("isss", $row['user_id'], $title, $message, $type);
                $stmt->execute();
                $count++;
            }
            $stmt->close();
            return $count > 0;
        }
        return false;
    }

    // Get unread notifications for a user
    public function getUnread($user_id, $limit = 10) {
        $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get all notifications for a user
    public function getAll($user_id, $limit = 20) {
        $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Mark as read
    public function markAsRead($notification_id, $user_id) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        return $stmt->execute();
    }
    
    // Mark all as read for a user
    public function markAllAsRead($user_id) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
    
    // Count unread
    public function countUnread($user_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as info FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['info'];
    }
}
?>
