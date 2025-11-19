<?php

class VendorAccount {
    private $conn;
    private $table_name = "vendor_account";

    public $user_id;
    public $business_name;
    public $business_address;
    public $business_description;
    public $logo_url; // Will now store 'business_logo/filename.ext'
    public $latitude;
    public $longitude;
    public $verification_document; // Will now store 'business_documents/filename.ext'
    public $status = 'Pending';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        if ($this->exists()) {
            return false;
        }

        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    user_id=:user_id,
                    business_name=:business_name,
                    business_address=:business_address,
                    business_description=:business_description,
                    logo_url=:logo_url,
                    latitude=:latitude,
                    longitude=:longitude,
                    verification_document=:verification_document,
                    status=:status";

        $stmt = $this->conn->prepare($query);

        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->business_name = htmlspecialchars(strip_tags($this->business_name));
        $this->business_address = htmlspecialchars(strip_tags($this->business_address));
        $this->business_description = htmlspecialchars(strip_tags($this->business_description));
        $this->logo_url = htmlspecialchars(strip_tags($this->logo_url));
        $this->latitude = htmlspecialchars(strip_tags($this->latitude));
        $this->longitude = htmlspecialchars(strip_tags($this->longitude));
        $this->verification_document = htmlspecialchars(strip_tags($this->verification_document));

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":business_name", $this->business_name);
        $stmt->bindParam(":business_address", $this->business_address);
        $stmt->bindParam(":business_description", $this->business_description);
        $stmt->bindParam(":logo_url", $this->logo_url);
        $stmt->bindParam(":latitude", $this->latitude);
        $stmt->bindParam(":longitude", $this->longitude);
        $stmt->bindParam(":verification_document", $this->verification_document);
        $stmt->bindParam(":status", $this->status);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Vendor Account creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function exists() {
        $query = "SELECT vendor_id FROM " . $this->table_name . " WHERE user_id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
    public function readByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id LIMIT 1";

        try {
            $stmt = $this->conn->prepare($query);
            // Ensure $user_id is treated as an integer
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Return the associative array of vendor data or false
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO Error in readByUserId: " . $e->getMessage());
            return false;
        }
    }
}