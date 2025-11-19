<?php
require_once __DIR__ . '/../config/liyag_batangan_db.php';

class NotificationModel {
    private $conn;
    private $table = 'notifications';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function sendNotification(int $user_id, string $title, string $message): bool {
        $query = "INSERT INTO {$this->table} (user_id, title, message) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("iss", $user_id, $title, $message);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}
?>
