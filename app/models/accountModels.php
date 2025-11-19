<?php
require_once __DIR__ . '/../config/liyag_batangan_db.php';

class AccountModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get user profile details by ID
     */
    public function getUserById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // -------------------------------------------------------------------
    //                       NEW ANALYTICS METHODS
    // -------------------------------------------------------------------

    /**
     * Get the total count of orders for a specific user.
     */
    public function getTotalOrdersByUser($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(order_id) FROM orders WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_row()[0] ?? 0;
    }

    /**
     * Get the total amount spent by a specific user on delivered/completed orders.
     */
    public function getTotalSpentByUser($userId) {
        $stmt = $this->conn->prepare("
            SELECT SUM(order_total) 
            FROM orders 
            WHERE user_id = ? AND order_status IN ('Delivered', 'Completed', 'Approved')
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_row()[0] ?? 0.00; 
    }
    
    /**
     * Get spending grouped by product category for the pie chart.
     */
    public function getSpendingByCategory($userId) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.name, 
                SUM(oi.line_total) AS spent
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN product_categories c ON p.category_id = c.category_id
            WHERE o.user_id = ? AND o.order_status IN ('Delivered', 'Completed', 'Approved')
            GROUP BY c.category_id, c.name
            ORDER BY spent DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = $result->fetch_all(MYSQLI_ASSOC);
        
        if (empty($data)) {
            return [
                ['name' => 'Uncategorized', 'spent' => 0],
            ];
        }
        
        return $data;
    }
    

    /**
     * Get monthly spending data for the bar chart.
     */
    public function getMonthlySpending($userId) {
        $stmt = $this->conn->prepare("
            SELECT 
                DATE_FORMAT(order_date, '%b') AS month, 
                SUM(order_total) AS spent
            FROM orders
            WHERE user_id = ? AND order_status IN ('Delivered', 'Completed', 'Approved')
            GROUP BY month
            ORDER BY MIN(order_date) DESC 
            LIMIT 6
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // -------------------------------------------------------------------
    //                       NEW "TO REVIEW" METHOD
    // -------------------------------------------------------------------

    /**
     * Fetches all unique products from 'Delivered' orders for a specific user.
     * @param int $userId The ID of the user.
     * @return array An array of product details ready for review.
     */
    public function getProductsToReviewByUser(int $userId): array
{
    $sql = "
        SELECT 
            oi.order_item_id,      -- <--- ADDED: Critical for linking review to the order line item
            p.product_id,
            p.name AS product_name,
            p.image_url,
            o.order_id,
            o.order_date
        FROM
            orders o
        JOIN
            order_items oi ON o.order_id = oi.order_id
        JOIN
            products p ON oi.product_id = p.product_id
        LEFT JOIN
            product_reviews r ON oi.product_id = r.product_id AND r.user_id = o.user_id -- Check for existing review
        WHERE
            o.user_id = ? 
            AND o.order_status = 'Delivered'
            AND r.review_id IS NULL -- <--- ADDED: Filter out items that have already been reviewed
        ORDER BY
            o.order_date DESC;
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

    /**
     * Fetches the count of unique products from 'Delivered' orders for a specific user.
     * @param int $userId The ID of the user.
     * @return int The count of products ready for review.
     */
    public function countProductsToReviewByUser(int $userId): int
    {
        // This query counts the *distinct* products in delivered orders that the user has not yet reviewed.
        // NOTE: The current SQL in the original function only gets delivered products.
        // A full implementation would check against a 'reviews' table to exclude already reviewed items.
        // For now, we will count based on the original provided logic.
        $sql = "
            SELECT 
                COUNT(DISTINCT p.product_id) AS review_count
            FROM
                orders o
            JOIN
                order_items oi ON o.order_id = oi.order_id
            JOIN
                products p ON oi.product_id = p.product_id
            LEFT JOIN
                product_reviews r ON p.product_id = r.product_id AND r.user_id = o.user_id 
            WHERE
                o.user_id = ? 
                AND o.order_status = 'Delivered'
                AND r.review_id IS NULL; -- Only count products that have NOT been reviewed by the user
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)($row['review_count'] ?? 0);
    }

    /**
     * Fetches products that the user has already reviewed. (Using mysqli)
     * @param int $user_id
     * @return array
     */
    public function getReviewedProductsByUser($user_id) {
        $query = "
            SELECT 
                pr.rating, 
                pr.comment,
                pr.review_date,
                p.product_id,
                p.name AS product_name,
                p.image_url,
                o.order_id
            FROM product_reviews pr
            JOIN products p ON pr.product_id = p.product_id
            JOIN order_items oi ON pr.order_item_id = oi.order_item_id
            JOIN orders o ON oi.order_id = o.order_id
            WHERE pr.user_id = ?  /* mysqli uses ? placeholder */
            ORDER BY pr.review_date DESC
        ";

        // 1. Use the correct property: $this->conn
        $stmt = $this->conn->prepare($query);
        
        // 2. Use mysqli's bind_param
        $stmt->bind_param('i', $user_id);
        
        $stmt->execute();

        // 3. Use mysqli's get_result and fetch_all
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    // -------------------------------------------------------------------
    //                          EXISTING METHODS
    // -------------------------------------------------------------------

    /**
     * Update user info and profile picture
     */
    public function updateUserInfo($userId, $name, $phone, $address, $profilePicturePath = null) {
        $sql = "UPDATE users SET name = ?, phone_number = ?, address = ?";
        $params = [$name, $phone, $address];
        $types = "sss";

        if ($profilePicturePath) {
            $sql .= ", profile_picture = ?";
            $params[] = $profilePicturePath;
            $types .= "s";
        }

        $sql .= " WHERE user_id = ?";
        $params[] = $userId;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        return $stmt->execute() ? true : $stmt->error;
    }

    /**
     * Update profile picture path only
     */
    public function updateProfilePicture($userId, $imagePath) {
        $stmt = $this->conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->bind_param("si", $imagePath, $userId);
        return $stmt->execute() ? true : $stmt->error;
    }

    /**
     * Update user password hash
     * NOTE: This method expects a pre-hashed password. Hashing should be done in the controller.
     */
    public function updatePasswordHash($userId, $newHashedPassword) {
        $stmt = $this->conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->bind_param("si", $newHashedPassword, $userId);
        return $stmt->execute() ? true : $stmt->error;
    }

    
}