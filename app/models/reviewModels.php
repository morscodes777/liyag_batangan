<?php
class ReviewModel {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function submitReview($order_item_id, $user_id, $product_id, $rating, $comment) {
        $query = "INSERT INTO product_reviews (order_item_id, user_id, product_id, rating, comment) 
                  VALUES (:order_item_id, :user_id, :product_id, :rating, :comment)";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':order_item_id', $order_item_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
        $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function getReviewStats($product_id) {
        $query = "SELECT 
                    AVG(rating) AS average_rating,
                    COUNT(review_id) AS total_reviews
                  FROM product_reviews
                  WHERE product_id = :product_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRecentReviews($product_id, $limit = 5) {
        $query = "SELECT 
                    pr.rating, 
                    pr.comment, 
                    u.name AS user_name, 
                    pr.review_date
                  FROM product_reviews pr
                  JOIN users u ON pr.user_id = u.user_id
                  WHERE pr.product_id = :product_id
                  ORDER BY pr.review_date DESC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function checkExistingReview($order_item_id, $user_id) {
        $query = "SELECT review_id FROM product_reviews WHERE order_item_id = :order_item_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':order_item_id', $order_item_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
   
}