<?php

require_once __DIR__ . '/../models/productModels.php';
require_once __DIR__ . '/../models/vendorModels.php'; 
require_once __DIR__ . '/../models/cartModels.php'; 
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';

class HomeController {
    
    public function index() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }
        $user = $_SESSION['user'];
        $displayUserName = $user['name'] ?? 'User';
        $userProfilePicture = $user['profile_picture'] ?? null;

        $db = new Database();
        $conn = $db->connect();

        $vendorModel = new Vendor($conn);
        $productModel = new ProductModel($conn); 

        $approved_stores = $vendorModel->readApproved();
        
        foreach ($approved_stores as &$store) {
            $ratingData = $vendorModel->getStoreAverageRating($store['vendor_id']);
            
            $store['average_rating'] = $ratingData['average_store_rating'] ?? 0.0;
            $store['total_reviews'] = $ratingData['total_reviews_count'] ?? 0;
        }
        unset($store);
        
        $recommended_products = $productModel->readRandom(10); 

        include __DIR__ . '/../views/home.php';
    }
    
    // NEW FUNCTION TO HANDLE AJAX REQUEST FOR REVIEWS
    public function fetchProductReviews() {
        header('Content-Type: application/json');

        if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing or invalid product ID.']);
            exit;
        }

        $productId = (int)$_GET['product_id'];
        
        try {
            $db = new Database();
            $conn = $db->connect();
            $productModel = new ProductModel($conn);

            // Call the model function we added earlier
            $reviews = $productModel->getReviewsByProductId($productId);
            
            // Check if reviews were found and return data
            if ($reviews !== false) {
                echo json_encode($reviews); 
            } else {
                // If the model returns false (unlikely with PDO on success, but safe to check)
                echo json_encode([]); 
            }

        } catch (Exception $e) {
            http_response_code(500); 
            // In production, you would log $e->getMessage() instead of echoing it.
            echo json_encode(['success' => false, 'message' => 'Failed to fetch reviews due to a server error.']);
        }
        
        exit;
    }

    public function addToCart() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'You must be logged in to add items to the cart.']);
            exit;
        }
        
        $user_id = $_SESSION['user']['user_id'];
        
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        
        if (!$product_id || $quantity === false || $quantity < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid product data or quantity.']);
            exit;
        }
        
        $result = false; 

        try {
            $db = new Database();
            $conn = $db->connect();
            $productModel = new ProductModel($conn);
            $cartModel = new CartModel($conn); 

            $product = $productModel->readSingle($product_id);
            
            if (!$product) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Product not found or unavailable.']);
                exit;
            }
            
            $unit_price = $product['price'];

            $total_price_for_quantity = $unit_price * $quantity;

            $result = $cartModel->addItem(
                $user_id, 
                $product_id, 
                $quantity, 
                $total_price_for_quantity
            );

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update cart. Please try again.']);
            }

        } catch (Exception $e) {
            http_response_code(500); 
            echo json_encode(['success' => false, 'message' => 'A critical server error occurred.']);
        }
        
        exit;
    }

    public function allStores() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }
        $user = $_SESSION['user'];
        $displayUserName = $user['name'] ?? 'User';
        $userProfilePicture = $user['profile_picture'] ?? null;

        $db = new Database();
        $conn = $db->connect();

        $vendorModel = new Vendor($conn);

        $all_approved_stores = $vendorModel->readApproved();
        
        foreach ($all_approved_stores as &$store) {
            $ratingData = $vendorModel->getStoreAverageRating($store['vendor_id']);
            
            $store['average_rating'] = $ratingData['average_store_rating'] ?? 0.0;
            $store['total_reviews'] = $ratingData['total_reviews_count'] ?? 0;
        }
        unset($store);

        include __DIR__ . '/../views/all_stores.php';
    }
}