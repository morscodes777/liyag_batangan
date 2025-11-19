<?php
require_once __DIR__ . '/../config/liyag_batangan_db.php';

class ProductModel {
    private $table_name = "products";
    private $vendor_table_name = "vendor_account";
    private $category_table_name = "product_categories";
    protected $conn;

    public function __construct() {
        $database = new Database(); 
        $this->conn = $database->connect(); 
    }

    public function getPendingProducts() {
        $query = "SELECT 
                      p.product_id, 
                      p.name, 
                      p.price,
                      p.vendor_id,
                      v.business_name AS vendor_name
                  FROM 
                      " . $this->table_name . " p
                  JOIN 
                      " . $this->vendor_table_name . " v ON p.vendor_id = v.vendor_id
                  WHERE 
                      p.status = 'Pending'
                  ORDER BY 
                      p.created_at ASC";

        $stmt = $this->conn->prepare($query); 

        if (!$stmt) {
            error_log("ProductModel: Failed to prepare getPendingProducts statement: " . $this->conn->error);
            return [];
        }

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            
            $stmt->close();
            return $products;
        } else {
            error_log("ProductModel: Failed to execute getPendingProducts statement: " . $stmt->error);
            $stmt->close();
            return [];
        }
    }

    /**
     * Fetches detailed product information for the modal.
     */
    public function getProductDetails(int $product_id): ?array {
        $query = "SELECT 
                      p.*, 
                      c.name AS category_name,
                      v.business_name AS vendor_name
                  FROM 
                      " . $this->table_name . " p
                  LEFT JOIN 
                      " . $this->category_table_name . " c ON p.category_id = c.category_id
                  LEFT JOIN 
                      " . $this->vendor_table_name . " v ON p.vendor_id = v.vendor_id
                  WHERE 
                      p.product_id = ?";
        
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            error_log("ProductModel: Failed to prepare getProductDetails statement: " . $this->conn->error);
            return null;
        }

        $stmt->bind_param("i", $product_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            return $product;
        } else {
            error_log("ProductModel: Failed to execute getProductDetails statement: " . $stmt->error);
            $stmt->close();
            return null;
        }
    }

    /**
     * Fetches products by vendor, with optional status filter.
     */
    public function getProductsByVendor(int $vendor_id, ?string $status_filter = null): array {
        $query = "SELECT product_id, name, price, status 
                  FROM " . $this->table_name . " 
                  WHERE vendor_id = ?";
        
        if ($status_filter && $status_filter !== '') {
            $query .= " AND status = ?";
        }

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            error_log("ProductModel: Failed to prepare getProductsByVendor statement: " . $this->conn->error);
            return [];
        }
        
        if ($status_filter && $status_filter !== '') {
            $stmt->bind_param("is", $vendor_id, $status_filter);
        } else {
            $stmt->bind_param("i", $vendor_id);
        }
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $products;
        } else {
            error_log("ProductModel: Failed to execute getProductsByVendor statement: " . $stmt->error);
            $stmt->close();
            return [];
        }
    }

    /**
     * Counts products by status for a specific vendor.
     */
    public function countProductsByVendorStatus(int $vendor_id): array {
        $counts = [];
        $statuses = ['Pending', 'Active', 'OutOfStock'];
        
        foreach ($statuses as $status) {
            $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE vendor_id = ? AND status = ?";
            
            $stmt = $this->conn->prepare($query);

            if (!$stmt) {
                error_log("ProductModel: Failed to prepare count statement for " . $status . ": " . $this->conn->error);
                $counts[strtolower($status) . '_count'] = 0;
                continue;
            }

            $stmt->bind_param("is", $vendor_id, $status);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $count = $result->fetch_row()[0];
                $counts[strtolower($status) . '_count'] = (int)$count;
                $stmt->close();
            } else {
                error_log("ProductModel: Failed to execute count statement for " . $status . ": " . $stmt->error);
                $counts[strtolower($status) . '_count'] = 0;
                $stmt->close();
            }
        }
        
        return $counts;
    }

    public function updateProductStatus($product_id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE product_id = ?";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            error_log("ProductModel: Failed to prepare updateProductStatus statement: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("si", $status, $product_id);

        if ($stmt->execute()) {
            $success = $stmt->affected_rows > 0;
            $stmt->close();
            return $success;
        } else {
            error_log("ProductModel: Failed to execute updateProductStatus statement: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }
}