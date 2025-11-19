<?php
class NotificationModel {
    private $db;

    public function __construct(PDO $db_connection) {
        $this->db = $db_connection;
    }

    public function getNotificationsByUserId($user_id) {
        if (!($this->db instanceof PDO)) {
            error_log("NotificationModel Error: Database connection is missing in getNotificationsByUserId.");
            throw new Exception("Database connection is invalid.");
        }
        $sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead($notification_id) {
        if (!($this->db instanceof PDO)) {
            error_log("NotificationModel Error: Database connection is missing in markAsRead.");
            return false;
        }
        $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = :id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function deleteNotification($notification_id) {
        if (!($this->db instanceof PDO)) {
            error_log("NotificationModel Error: Database connection is missing in deleteNotification.");
            return false;
        }
        $sql = "DELETE FROM notifications WHERE notification_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function createNotification($user_id, $title, $message, $link) {
        // CRITICAL: Prevent the fatal crash if the connection is null
        if (!($this->db instanceof PDO)) {
            error_log("FATAL CONNECTION FAILURE: \$this->db is not a PDO instance inside NotificationModel.");
            // Throw an Exception to stop execution and be caught by the OrderController
            throw new Exception("Database connection is invalid for notification creation.");
        }
        
        $sql = "INSERT INTO notifications (user_id, title, message, link, is_read) 
                VALUES (:user_id, :title, :message, :link, 0)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':link', $link);
        return $stmt->execute();
    }
}