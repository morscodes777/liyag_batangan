<?php
// app/models/StoreModel.php
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';

class StoreModel {
    private $conn;
    private $vendor_table = "vendor_account";
    private $product_table = "products";
    private $category_table = "product_categories";
    private $reviews_table = "product_reviews";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Fetches all details for a vendor store.
     * @param int $vendorId The ID of the vendor store.
     * @return array|null The store data array, or null if not found.
     */
    public function getStoreDetails($vendorId) {
        $query = "SELECT vendor_id, business_name, business_address, logo_url, average_rating, total_reviews, user_id
                     FROM " . $this->vendor_table . "
                     WHERE vendor_id = ? AND status = 'Approved'
                     LIMIT 1";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return null;

        $stmt->execute([$vendorId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches all approved products for a given vendor.
     * @param int $vendorId The ID of the vendor.
     * @return array List of products.
     */
   public function getProductsByVendorId($vendorId) {
    $query = "
        SELECT 
            p.product_id, p.name, p.description, p.price, p.image_url, 
            p.status, p.stock_quantity, p.category_id,
            COALESCE(pr.average_rating, 0.0) AS average_rating,
            COALESCE(pr.total_reviews, 0) AS total_reviews
        FROM " . $this->product_table . " p
        LEFT JOIN (
            SELECT 
                product_id, 
                AVG(rating) AS average_rating,
                COUNT(review_id) AS total_reviews
            FROM " . $this->reviews_table . " 
            WHERE product_id IS NOT NULL
            GROUP BY product_id
        ) pr ON pr.product_id = p.product_id
        WHERE p.vendor_id = ? 
        ORDER BY p.name";

    $stmt = $this->conn->prepare($query);
    if (!$stmt) return [];

    $stmt->execute([$vendorId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * Fetches store details by User ID (used for the vendor to view their own store).
     * @param int $userId The ID of the user.
     * @return array|null The store data array, or null if not found.
     */
    public function getStoreDetailsByUserId($userId) {
        $query = "SELECT vendor_id, business_name, business_address, logo_url, average_rating, total_reviews, user_id
                     FROM " . $this->vendor_table . "
                     WHERE user_id = ? AND status = 'Approved'
                     LIMIT 1";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return null;
        
        $stmt->execute([$userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get the total sales amount for a specific vendor from completed orders using PDO.
     * @param int $vendorId The ID of the vendor.
     * @return float The total sales amount.
     */
    public function getTotalSalesByVendor($vendorId) {
    $query = "
        SELECT 
            COALESCE(SUM(o.vendor_payout), 0.00) AS total_payout
        FROM orders o
        -- Step 1: Join to order_items to find which products were in the order
        JOIN order_items oi ON o.order_id = oi.order_id
        -- Step 2: Join to products to get the vendor_id associated with those products
        JOIN products p ON oi.product_id = p.product_id
        WHERE p.vendor_id = ? 
        -- Only count orders that are successfully completed/delivered
        AND o.order_status IN ('Delivered', 'Completed')
        -- Crucial: Group by order_id and status to ensure we only sum the 'vendor_payout' once per order
        GROUP BY o.order_id, o.vendor_payout, o.order_status
    ";
    
    $stmt = $this->conn->prepare($query);
    
    if (!$stmt) return 0.00;
    
    // Execute the query using the vendorId
    $stmt->execute([$vendorId]);

    // Fetch all results. Since we grouped by order_id, we get a list of unique payouts.
    // We need to sum them up manually in PHP, as SQL SUM might overcount if one order has multiple vendor products.
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPayouts = 0.00;
    foreach ($rows as $row) {
        $totalPayouts += (float)($row['total_payout'] ?? 0.00);
    }
    
    return $totalPayouts;
}

    /**
     * Fetches all categories for filter buttons.
     */
     public function getProductCategories() {
         $query = "SELECT category_id, name FROM " . $this->category_table . " ORDER BY name";

         $stmt = $this->conn->prepare($query);
         if (!$stmt) return [];
         
         $stmt->execute();
         
         return $stmt->fetchAll(PDO::FETCH_ASSOC);
     }
    
    /**
     * Recalculates and updates the store's average rating and total reviews based on all its product reviews.
     * @param int $vendorId The ID of the vendor.
     * @return bool True on success, false on failure.
     */
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
        LIMIT 1 -- Limit is optional here as it returns one row, but 
                -- standard pagination (LIMIT 0, 25) should not be used here.
    ";

    $stmt = $this->conn->prepare($query);
    if (!$stmt) return null;

    // Use execute() to safely bind the variable to the placeholder
    $stmt->execute([$vendorId]); 
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
}