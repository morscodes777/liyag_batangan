<?php
// app/models/AddressModel.php - CORRECTED FOR PDO

class AddressModel {
    private $db;

    public function __construct($db) {
        // Assume $db is a PDO object
        $this->db = $db;
    }

    /**
     * Fetches all addresses for a given user.
     * @param int $userId
     * @return array
     */
    public function getUserAddresses($userId) {
        $sql = "SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetches a single address by its ID and ensures it belongs to the user.
     * @param int $addressId
     * @param int $userId
     * @return array|null
     */
    public function getAddressById($addressId, $userId) {
        $sql = "SELECT * FROM user_addresses WHERE address_id = :address_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Inserts a new address for a user.
     * @param int $userId
     * @param string $label
     * @param string $fullAddress
     * @param float|null $latitude
     * @param float|null $longitude
     * @param string $contactNumber
     * @return int|false The new address_id or false on failure.
     */
    public function saveAddress($userId, $label, $fullAddress, $latitude = null, $longitude = null, $contactNumber = null) {
        $isDefault = 0; 

        $sql = "
            INSERT INTO user_addresses (user_id, label, full_address, latitude, longitude, contact_number, is_default) 
            VALUES (:user_id, :label, :full_address, :latitude, :longitude, :contact_number, :is_default)
        ";
        
        $stmt = $this->db->prepare($sql);
        
        try {
            // Use bindParam for explicit type binding
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':label', $label, PDO::PARAM_STR);
            $stmt->bindParam(':full_address', $fullAddress, PDO::PARAM_STR);
            $stmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
            
            // Handle null values for decimals (latitude/longitude)
            if (is_null($latitude)) {
                $stmt->bindValue(':latitude', null, PDO::PARAM_NULL);
            } else {
                // Binding float as string is generally safer in PDO for decimal types
                $stmt->bindValue(':latitude', (float)$latitude, PDO::PARAM_STR);
            }
            
            if (is_null($longitude)) {
                $stmt->bindValue(':longitude', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':longitude', (float)$longitude, PDO::PARAM_STR);
            }
            
            $stmt->bindParam(':is_default', $isDefault, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            } else {
                // Log detailed error information here if needed for debugging
                return false;
            }
        } catch (PDOException $e) {
            error_log("Database Error saving address: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Wrapper method to match the call in CheckoutController.php (saveNewAddress).
     * Delegates the saving to the main saveAddress method.
     */
    public function saveNewAddress($userId, $label, $fullAddress, $latitude, $longitude, $contactNumber) {
        return $this->saveAddress($userId, $label, $fullAddress, $latitude, $longitude, $contactNumber);
    }
    /**
     * Sets a specific address as the default for the user and clears others.
     * @param int $addressId
     * @param int $userId
     * @return bool
     */
    public function setDefaultAddress($addressId, $userId) {
        try {
            $this->db->beginTransaction();

            // 1. Clear default status for all other addresses
            $sqlClear = "UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id";
            $stmtClear = $this->db->prepare($sqlClear);
            $stmtClear->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtClear->execute();

            // 2. Set the new address as default
            $sqlSet = "UPDATE user_addresses SET is_default = 1 WHERE address_id = :address_id AND user_id = :user_id";
            $stmtSet = $this->db->prepare($sqlSet);
            $stmtSet->bindParam(':address_id', $addressId, PDO::PARAM_INT);
            $stmtSet->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtSet->execute();

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            // In a real application, you would log $e->getMessage() here
            return false;
        }
    }
    
}