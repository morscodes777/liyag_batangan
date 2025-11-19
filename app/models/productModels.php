<?php

class ProductModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // New common SELECT and JOIN block for readability
    private function getProductBaseQuery() {
        return "
            SELECT 
                p.*, 
                v.business_name, 
                v.business_address,
                CAST(AVG(pr.rating) AS DECIMAL(2, 1)) AS average_rating,
                COUNT(pr.review_id) AS total_reviews
            FROM products p
            JOIN vendor_account v ON p.vendor_id = v.vendor_id
            LEFT JOIN product_reviews pr ON p.product_id = pr.product_id
        ";
    }

    public function getReviewsByProductId($productId) {
        $sql = "
            SELECT 
                pr.review_id,
                pr.rating,
                pr.comment AS review_text, 
                pr.review_date,
                u.username,
                u.user_id 
            FROM
                product_reviews pr
            JOIN
                users u ON pr.user_id = u.user_id
            WHERE
                pr.product_id = ?
                AND pr.status = 'Approved' 
            ORDER BY
                pr.review_date DESC;
        ";

        $stmt = $this->conn->prepare($sql);
        
        // Ensure $productId is an integer for secure binding
        $stmt->execute([$productId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveProductsInStock() {
        $sql = $this->getProductBaseQuery() . "
                WHERE p.stock_quantity > 0 AND p.status = 'Active'
                GROUP BY p.product_id
                ORDER BY p.product_id DESC";
                
        $stmt = $this->conn->query($sql);

        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    
    public function getProductsByCategoryId($categoryId) {
        $sql = $this->getProductBaseQuery() . "
                WHERE p.category_id = ? AND p.stock_quantity > 0 AND p.status = 'Active'
                GROUP BY p.product_id
                ORDER BY p.product_id DESC";
                
        $stmt = $this->conn->prepare($sql);
        
        $stmt->execute([$categoryId]); 
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readSingle($id) {
        $sql = $this->getProductBaseQuery() . "
                WHERE p.product_id = ? 
                GROUP BY p.product_id
                LIMIT 1";
                
        $stmt = $this->conn->prepare($sql);
        
        $stmt->execute([$id]); 
        
        return $stmt->fetch(PDO::FETCH_ASSOC); 
    }

    public function readRandom($limit) {
        $sql = $this->getProductBaseQuery() . "
                WHERE p.stock_quantity > 0 AND p.status = 'Active' 
                GROUP BY p.product_id
                ORDER BY RAND() 
                LIMIT ?";
                
        $stmt = $this->conn->prepare($sql);
        
        $stmt->execute([$limit]); 
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // The methods createProduct, updateProduct, and deleteProduct do not require changes
    public function createProduct($data) {
        $sql = "INSERT INTO products (name, description, price, stock_quantity, status, image_url, vendor_id)
                VALUES (:name, :description, :price, :stock_quantity, :status, :image_url, :vendor_id)";
                
        $stmt = $this->conn->prepare($sql);
        
        $success = $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock_quantity' => $data['stock_quantity'],
            'status' => $data['status'],
            'image_url' => $data['image_url'],
            'vendor_id' => $data['vendor_id']
        ]);
        
        return $success;
    }
    
    public function updateProduct($id, $data) {
        $sql = "UPDATE products SET 
                name=:name, description=:description, price=:price, 
                stock_quantity=:stock_quantity, status=:status, 
                image_url=:image_url, vendor_id=:vendor_id 
                WHERE product_id=:id";
                
        $stmt = $this->conn->prepare($sql);

        $params = [
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock_quantity' => $data['stock_quantity'],
            'status' => $data['status'],
            'image_url' => $data['image_url'],
            'vendor_id' => $data['vendor_id'],
            'id' => $id
        ];
        
        return $stmt->execute($params);
    }
    
    public function deleteProduct($id) {
        $sql = "DELETE FROM products WHERE product_id = :id";
        $stmt = $this->conn->prepare($sql);
        
        return $stmt->execute(['id' => $id]);
    }
}