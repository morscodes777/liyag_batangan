<?php
require_once __DIR__ . '/../models/storeModels.php';
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';

class StoreController {
    private $storeModel;
    private $db_connection;

    public function __construct() {
        $database = new Database();
        $this->db_connection = $database->connect(); 
        $this->storeModel = new StoreModel($this->db_connection);
    }

    private function loadStoreView($store, $product_results, $categories, $user, $userProfilePicture, $average_rating, $total_reviews) {
        // NOTE: $average_rating and $total_reviews are now passed explicitly and are not fetched from $store
        $data = compact('user', 'store', 'userProfilePicture', 'product_results', 'categories', 'average_rating', 'total_reviews');
        
        extract($data);
        
        include __DIR__ . '/../views/store.php';
    }

    public function viewStore() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $user = $_SESSION['user'];
        $userProfilePicture = $user['profile_picture'] ?? null;
        
        $vendorId = $_GET['vendor_id'] ?? null;

        if ($vendorId) {
            $store = $this->storeModel->getStoreDetails($vendorId);
            
            if ($store) {
                // NEW: Fetch the aggregate rating based on all product reviews
                $ratingData = $this->storeModel->getStoreAverageRating($vendorId);
                
                $average_rating = $ratingData['average_store_rating'] ?? 0.0;
                $total_reviews = $ratingData['total_reviews_count'] ?? 0;
                
                $product_results = $this->storeModel->getProductsByVendorId($vendorId);
                $categories = $this->storeModel->getProductCategories();

                // Pass the new rating data to the view loader
                $this->loadStoreView($store, $product_results, $categories, $user, $userProfilePicture, $average_rating, $total_reviews);
            } else {
                echo "Store not found or is currently unavailable."; 
            }
        } else {
            echo "Invalid request. Store ID is missing.";
        }
    }
    
    // viewMyStore is unchanged as it seems to be for the vendor's dashboard view
    public function viewMyStore() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['user_type'] !== 'vendor' && $_SESSION['user']['user_type'] !== 'Vendor')) {
            header("Location: index.php?action=login");
            exit;
        }

        $user = $_SESSION['user'];
        $userProfilePicture = $user['profile_picture'] ?? null;
        
        $userId = $user['user_id'] ?? null;

        if ($userId) {
            $store = $this->storeModel->getStoreDetailsByUserId($userId);
            
            if ($store) {
                $vendorId = $store['vendor_id'];
                
                // 1. FETCH TOTAL SALES HERE
                $totalSales = $this->storeModel->getTotalSalesByVendor($vendorId); 
                
                $products = $this->storeModel->getProductsByVendorId($vendorId); 
                $categories = $this->storeModel->getProductCategories();
                
                $ratingData = $this->storeModel->getStoreAverageRating($vendorId);
                $average_rating = $ratingData['average_store_rating'] ?? 0.0;
                $total_reviews = $ratingData['total_reviews_count'] ?? 0;

                // 2. INCLUDE $totalSales in the data array
                $data = compact('user', 'store', 'userProfilePicture', 'products', 'categories', 'average_rating', 'totalSales');
                extract($data);
                
                // 3. Include the View
                include __DIR__ . '/../views/view_store.php'; 
                
            } else {
                echo "Your store details are not available. Please ensure your account is approved.";
            }
        } else {
            echo "User ID not found in session.";
        }
    }
}   