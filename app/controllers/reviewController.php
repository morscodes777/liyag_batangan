<?php
class ReviewController {
    private $db;
    private $reviewModel;
    private $userModel;

    public function __construct(PDO $db, ReviewModel $reviewModel, UserModel $userModel) {
        $this->db = $db;
        $this->reviewModel = $reviewModel;
        $this->userModel = $userModel;
    }

    private function respondJson($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function submitReviewApi() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $userId = $_SESSION['user_id'] ?? null;
        $orderItemId = filter_input(INPUT_POST, 'order_item_id', FILTER_SANITIZE_NUMBER_INT);
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
        $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
        $comment = filter_input(INPUT_POST, 'comment', FILTER_DEFAULT);

        if (!$userId || !$orderItemId || !$productId || !$rating) {
            $this->respondJson(['success' => false, 'message' => 'Missing required fields (user, item, product, or rating).'], 400);
        }

        if ($rating < 1 || $rating > 5) {
            $this->respondJson(['success' => false, 'message' => 'Rating must be between 1 and 5.'], 400);
        }
        
        if ($this->reviewModel->checkExistingReview($orderItemId, $userId)) {
             $this->respondJson(['success' => false, 'message' => 'You have already reviewed this item.'], 409);
        }

        try {
            if ($this->reviewModel->submitReview($orderItemId, $userId, $productId, $rating, $comment)) {
                $this->respondJson(['success' => true, 'message' => 'Review submitted successfully!']);
            } else {
                $this->respondJson(['success' => false, 'message' => 'Failed to save review to the database.'], 500);
            }
        } catch (Throwable $e) {
            error_log("Review Submission Error: " . $e->getMessage());
            $this->respondJson(['success' => false, 'message' => 'An internal server error occurred.'], 500);
        }
    }

    public function getProductReviewsApi() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->respondJson(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $productId = filter_input(INPUT_GET, 'product_id', FILTER_SANITIZE_NUMBER_INT);

        if (!$productId) {
            $this->respondJson(['success' => false, 'message' => 'Product ID is required.'], 400);
        }

        try {
            $stats = $this->reviewModel->getReviewStats($productId);
            $reviews = $this->reviewModel->getRecentReviews($productId);
            
            $averageRating = $stats['average_rating'] ? (float)$stats['average_rating'] : 0;
            $totalReviews = $stats['total_reviews'] ? (int)$stats['total_reviews'] : 0;

            $this->respondJson([
                'success' => true,
                'average_rating' => $averageRating,
                'total_reviews' => $totalReviews,
                'reviews' => $reviews
            ]);

        } catch (Throwable $e) {
            error_log("Review Fetch Error: " . $e->getMessage());
            $this->respondJson(['success' => false, 'message' => 'Could not fetch product review data.'], 500);
        }
    }
}