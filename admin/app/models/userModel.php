<?php

class UserModel {
    private $conn;
    private $table = 'users';

    public function __construct() {
        // --- UPDATED PATH AND FILENAME: liyag_batangan_db.php ---
        require_once __DIR__ . '/../config/liyag_batangan_db.php'; 
        
        $database = new Database();
        $this->conn = $database->connect(); // Use the connect() method
    }

    /**
     * Updates the user_type column for a specific user ID.
     *
     * @param int $user_id The ID of the user to update.
     * @param string $new_type The new type ('Vendor').
     * @return bool True on successful update, false otherwise.
     */
    public function updateUserType(int $user_id, string $new_type): bool {
        if ($user_id <= 0) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET user_type = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);

        if ($stmt) {
            // 'si' stands for string (new_type) and integer (user_id)
            $stmt->bind_param("si", $new_type, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }

        return false;
    }
}