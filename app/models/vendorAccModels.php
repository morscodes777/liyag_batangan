<?php

// app/models/VendorAccountModel.php (or similar)

class VendorAccountModel {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Fetches the vendor account details using the general user ID.
     * * @param int $userId The user_id associated with the vendor account.
     * @return array|false The vendor details (vendor_id, status, etc.) or false on failure.
     */
    public function getStoreDetailsByUserId($userId) {
        $sql = "
            SELECT 
                vendor_id, 
                business_name,
                status
            FROM vendor_account
            WHERE user_id = :user_id
            LIMIT 1
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getStoreDetailsByUserId: " . $e->getMessage());
            return false;
        }
    }
}