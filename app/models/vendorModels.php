<?php
// models/Vendor.php

class Vendor {
    private $conn;
    private $table_name = "vendor_account";

    public function __construct($db) {
        // $db is a PDO connection instance
        $this->conn = $db;
    }

    // Read all approved vendors - CORRECTED FOR PDO
    public function readApproved() {
        $query = "SELECT vendor_id, business_name, business_address, logo_url, latitude, longitude
                  FROM " . $this->table_name . "
                  WHERE status = 'Approved'
                  ORDER BY registration_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        // PDO method to fetch all results as an associative array
        $stores = $stmt->fetchAll(PDO::FETCH_ASSOC); 
        
        return $stores;
    }

    // Get a single store's details - CORRECTED FOR PDO
    public function getStoreDetails($vendorId) {
        $query = "SELECT vendor_id, business_name, business_address, business_picture
                  FROM " . $this->table_name . "
                  WHERE vendor_id = :vendor_id AND status = 'Approved'
                  LIMIT 1"; // LIMIT 0,1 is unnecessary in PDO fetch(1) scenario

        $stmt = $this->conn->prepare($query);
        
        // PDO uses named or positional placeholders (using named here)
        $stmt->bindParam(':vendor_id', $vendorId, PDO::PARAM_INT);
        $stmt->execute();

        // PDO method to fetch a single result as an associative array
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStoreAverageRating($vendorId) {
    $query = "
        SELECT 
            COALESCE(AVG(pr.rating), 0.0) AS average_store_rating,
            COUNT(pr.review_id) AS total_reviews_count
        FROM
            product_reviews pr
        JOIN
            products p ON pr.product_id = p.product_id
        WHERE
            p.vendor_id = ?
    ";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([$vendorId]); 
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
    
}
?>