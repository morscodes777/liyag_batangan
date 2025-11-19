<?php
class ChatModel {
    private $db;

    public function __construct($db_connection) {
        if (!($db_connection instanceof PDO)) {
            throw new InvalidArgumentException('Expected PDO instance for ChatModel');
        }
        $this->db = $db_connection;
    }

    public function findOrCreateThread($customer_user_id, $vendor_user_id) {
        $sql = "SELECT `thread_id` FROM `chat_threads`
                WHERE (`customer_user_id` = ? AND `vendor_user_id` = ?)
                   OR (`customer_user_id` = ? AND `vendor_user_id` = ?)
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$customer_user_id, (int)$vendor_user_id, (int)$vendor_user_id, (int)$customer_user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['thread_id'];
        }

        $insertSql = "INSERT INTO `chat_threads` (`customer_user_id`, `vendor_user_id`, `created_at`, `last_message_at`)
                      VALUES (?, ?, NOW(), NOW())";
        $ins = $this->db->prepare($insertSql);
        $ins->execute([(int)$customer_user_id, (int)$vendor_user_id]);

        return $this->db->lastInsertId();
    }

    public function getMessages($thread_id, $limit = 50) {
        $sql = "SELECT `message_id`, `thread_id`, `sender_user_id`, `message_content`, `is_read`, `sent_at`
                FROM `chat_messages`
                WHERE `thread_id` = ?
                ORDER BY `sent_at` ASC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, (int)$thread_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNewMessages($thread_id, $last_timestamp) {
        $sql = "SELECT `message_id`, `thread_id`, `sender_user_id`, `message_content`, `is_read`, `sent_at`
                FROM `chat_messages`
                WHERE `thread_id` = ?
                  AND `sent_at` > ?
                ORDER BY `sent_at` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$thread_id, $last_timestamp]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sendMessage($thread_id, $sender_user_id, $message_content) {
        $sql = "INSERT INTO `chat_messages` (`thread_id`, `sender_user_id`, `message_content`, `sent_at`)
                 VALUES (?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$thread_id, (int)$sender_user_id, $message_content]);

        // Explicitly update the thread timestamp
        try {
            $this->updateThreadTimestamp($thread_id);
        } catch (Exception $e) {
        }

        return $this->db->lastInsertId();
    }

    public function updateThreadTimestamp($thread_id) {
        // Uses 'last_message_at' from your chat_threads schema
        $sql = "UPDATE `chat_threads` SET `last_message_at` = NOW() WHERE `thread_id` = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([(int)$thread_id]);
    }

    public function getThreadParticipants($thread_id) {
        // Uses customer_user_id and vendor_user_id from your chat_threads schema
        $sql = "SELECT `customer_user_id`, `vendor_user_id`
                FROM `chat_threads`
                WHERE `thread_id` = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$thread_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getVendorThreads($vendor_user_id) {
        // Using 'name' and 'profile_picture' columns from your 'users' table schema
        $sql = "SELECT t.`thread_id`, 
                        t.`customer_user_id`,
                        t.`last_message_at`,
                        u.name AS customer_name,
                        u.profile_picture AS customer_profile_picture
                FROM `chat_threads` t
                INNER JOIN `users` u ON u.user_id = t.`customer_user_id`
                WHERE t.`vendor_user_id` = ?
                ORDER BY t.`last_message_at` DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$vendor_user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}