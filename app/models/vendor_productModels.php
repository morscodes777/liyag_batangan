<?php
// models/ProductModel.php

// Note: Ensure liyab_batangan_db_pdo.php returns a valid PDO connection object ($db).
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';

class ProductModel {
    private $conn;
    private $table_name = "products";

    // $db is expected to be a PDO object
    public function __construct($db) {
        $this->conn = $db;
    }

    public function getProductById($productId) {
        $query = "SELECT product_id, vendor_id, category_id, name, description, price, stock_quantity, image_url, status
                  FROM " . $this->table_name . "
                  WHERE product_id = :product_id
                  LIMIT 1";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Use PDO's fetch() for a single row
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO Error in getProductById: " . $e->getMessage());
            return false;
        }
    }

    public function getVendorIdByUserId($userId) {
        $query = "SELECT vendor_id FROM vendor_account WHERE user_id = :user_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $row['vendor_id'] : null;
        } catch (PDOException $e) {
            error_log("PDO Error in getVendorIdByUserId: " . $e->getMessage());
            return null;
        }
    }

    public function getProductsByStoreAndCategory($vendorId, $categoryId = null) {
        $query = "SELECT product_id, name, description, price, stock_quantity, image_url, status
                  FROM " . $this->table_name . "
                  WHERE vendor_id = :vendor_id";

        if ($categoryId !== null && $categoryId !== 'all') {
            $query .= " AND category_id = :category_id";
        }

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':vendor_id', $vendorId, PDO::PARAM_INT);

            if ($categoryId !== null && $categoryId !== 'all') {
                $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            }

            $stmt->execute();
            // Use PDO's fetchAll() for multiple rows
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PDO Error in getProductsByStoreAndCategory: " . $e->getMessage());
            return [];
        }
    }
    
    public function createProduct($data) {
        $status = 'Pending';
        $query = "INSERT INTO " . $this->table_name . "
                  (vendor_id, category_id, name, description, price, stock_quantity, image_url, status)
                  VALUES (:vendor_id, :category_id, :name, :description, :price, :stock_quantity, :image_url, :status)"; 

        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':vendor_id', $data['vendor_id'], PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':price', $data['price']); // PDO handles float/double automatically
            $stmt->bindParam(':stock_quantity', $data['stock_quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':image_url', $data['image_url'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("PDO Error in createProduct: " . $e->getMessage());
            return false;
        }
    }

    public function updateProduct($productId, $data) {
        // NOTE: If you are using 'category_id' in your form, ensure $data['category_id'] is set.
        $query = "UPDATE " . $this->table_name . "
                  SET name = :name, description = :description, price = :price, stock_quantity = :stock_quantity, category_id = :category_id
                  WHERE product_id = :product_id";

        try {
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':price', $data['price']); // PDO handles float/double automatically
            $stmt->bindParam(':stock_quantity', $data['stock_quantity'], PDO::PARAM_INT);
            
            // IMPORTANT: Ensure category_id is included in the data array if you want to update it.
            $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT); 
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("PDO Error in updateProduct: " . $e->getMessage());
            return false;
        }
    }

    public function deleteProduct($productId) {
        $query = "DELETE FROM " . $this->table_name . " WHERE product_id = :product_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("PDO Error in deleteProduct: " . $e->getMessage());
            return false;
        }
    }
}