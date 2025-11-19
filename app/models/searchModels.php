<?php
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';

class SearchModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Search for approved stores matching the query.
     * @param string $query The search term.
     * @return array List of stores.
     */
    public function getStoresByQuery($query) {
        $searchTerm = '%' . $query . '%';
        $stmt = $this->pdo->prepare("
            SELECT vendor_id, business_name, business_address, logo_url
            FROM vendor_account
            WHERE status = 'Approved' AND (business_name LIKE :name_query OR business_address LIKE :address_query)
            LIMIT 5
        ");
        $stmt->execute([
            'name_query' => $searchTerm,
            'address_query' => $searchTerm
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search for active products matching the query, with store and rating info.
     * @param string $query The search term.
     * @return array List of products.
     */
    public function getProductsByQuery($query) {
        $searchTerm = '%' . $query . '%';
        $stmt = $this->pdo->prepare("
            SELECT 
                p.product_id, p.name, p.price, p.image_url, p.description,
                v.business_name, v.business_address,
                COALESCE(AVG(r.rating), 0) AS average_rating,
                COUNT(r.review_id) AS total_reviews
            FROM products p
            LEFT JOIN vendor_account v ON p.vendor_id = v.vendor_id
            LEFT JOIN product_reviews r ON p.product_id = r.product_id
            WHERE p.status = 'Active' AND p.stock_quantity > 0 AND (p.name LIKE :name_query OR p.description LIKE :desc_query)
            GROUP BY p.product_id
            LIMIT 5
        ");
        $stmt->execute([
            'name_query' => $searchTerm,
            'desc_query' => $searchTerm
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}