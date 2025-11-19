<?php
require_once __DIR__ . '/../models/adminModel.php';
require_once __DIR__ . '/../models/vendorModel.php';
require_once __DIR__ . '/../models/productModel.php';

class AdminController {
    private $adminModel;
    private $vendorModel;
    private $productModel;

    public function __construct() {
        $this->adminModel = new AdminModel();
        $this->vendorModel = new VendorModel();
        $this->productModel = new ProductModel();
        
        $this->checkAdminAccess();
    }

    private function checkAdminAccess() {
        if (!isset($_SESSION['user_id']) || ($_SESSION['is_admin_logged_in'] ?? false) !== true) {
            header("Location: index.php?action=admin_login");
            exit;
        }
    }

    public function dashboard() {
        $users = $this->adminModel->getAllUsers();
        $pending_vendors = $this->vendorModel->getVendorsByStatus('Pending');
        $pending_products = $this->productModel->getPendingProducts();
        $total_platform_sales = $this->adminModel->getTotalPlatformSales();
        $vendor_sales = $this->adminModel->getVendorPayoutPerformance();

        require_once __DIR__ . '/../views/admin_dashboard.php';
    }

    // --- NEW METHOD FOR AJAX REQUEST ---
    public function getCommissionHistoryAjax() {
        // Only allow access via AJAX or an explicit action request
        if (!isset($_GET['ajax'])) {
            http_response_code(403);
            exit;
        }
        
        $history = $this->adminModel->getCommissionHistory();
        
        // Prepare data for JSON output
        $response = $history ?: [];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit; // Important to exit after sending JSON response
    }
    // --- END NEW METHOD ---

    public function viewUser() {
        $userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $user = false;
        $error = '';

        if ($userId) {
            $user = $this->adminModel->getUserById($userId);
            if (!$user) {
                $error = "User not found.";
            }
        } else {
            $error = "Invalid user ID provided.";
        }

        require_once __DIR__ . '/../views/admin/view_user.php';
    }
    
    public function pendingStores() {
        $pending_vendors = $this->vendorModel->getVendorsByStatus('Pending');
        require_once __DIR__ . '/../views/pending_stores.php';
    }
    
    public function approvedStores() {
        $approved_vendors = $this->vendorModel->getVendorsByStatus('Approved');
        require_once __DIR__ . '/../views/approved_stores.php';
    }

    public function rejectedStores() {
        $rejected_vendors = $this->vendorModel->getVendorsByStatus('Rejected');
        require_once __DIR__ . '/../views/rejected_stores.php';
    }
    
    public function productApproval() {
        $pending_vendors = $this->vendorModel->getVendorsByStatus('Pending');
        $total_sales = 150000;

        $all_vendors = $this->vendorModel->getVendorsByStatus('Approved');
        
        $pending_products = $this->productModel->getPendingProducts();
        $pending_product_count = count($pending_products);
        
        require_once __DIR__ . '/../views/product.php';
    }

    public function viewVendorProducts() {
        $vendor_id = filter_input(INPUT_GET, 'vendor_id', FILTER_VALIDATE_INT);
        $filter_status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$vendor_id) {
            $_SESSION['error_message'] = "Invalid vendor ID.";
            header("Location: index.php?action=product_approval");
            exit;
        }

        $vendor_info = $this->vendorModel->getVendorById($vendor_id);
        
        if (!$vendor_info) {
            $_SESSION['error_message'] = "Vendor not found.";
            header("Location: index.php?action=product_approval");
            exit;
        }

        $vendor_products = $this->productModel->getProductsByVendor($vendor_id, $filter_status);
        
        $counts = $this->productModel->countProductsByVendorStatus($vendor_id);
        $pending_count = $counts['pending_count'] ?? 0;
        $active_count = $counts['active_count'] ?? 0;
        $outofstock_count = $counts['outofstock_count'] ?? 0;
        
        if (!empty($vendor_products)) {
            usort($vendor_products, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }

        require_once __DIR__ . '/../views/vendor_products_list.php';
    }

    public function getProductDetails() {
        $product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
        
        header('Content-Type: application/json');

        if (!$product_id) {
            echo json_encode(['success' => false, 'message' => 'Product ID is missing.']);
            return;
        }

        $product = $this->productModel->getProductDetails($product_id);

        if ($product) {
            if (!empty($product['image_url'])) {
                $IMAGE_BASE_URL = "http://localhost/liyag_batangan_web/";
                $product['image_url'] = $IMAGE_BASE_URL . $product['image_url'];
            }
            
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
        }
    }
    
    public function productApprovalQueue() {
        $pending_products = $this->productModel->getPendingProducts();
    }
    
    // Handles the approval action
public function approveProduct() {
    if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing or invalid Product ID.']);
        return;
    }

    $product_id = (int)$_GET['product_id'];
    $new_status = 'Active'; 

    $update_result = $this->productModel->updateProductStatus($product_id, $new_status);

    header('Content-Type: application/json');
    if ($update_result) {
        echo json_encode([
            'success' => true,
            'message' => 'Product approved successfully.',
            'product_id' => $product_id,
            'new_status' => $new_status
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error or product status was already ' . $new_status . '.',
            'product_id' => $product_id
        ]);
    }
}


// Handles the rejection action
public function rejectProduct() {
    if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing or invalid Product ID.']);
        return;
    }

    $product_id = (int)$_GET['product_id'];
    $new_status = 'Rejected'; 

    $update_result = $this->productModel->updateProductStatus($product_id, $new_status);

    header('Content-Type: application/json');
    if ($update_result) {
        echo json_encode([
            'success' => true,
            'message' => 'Product rejected successfully.',
            'product_id' => $product_id,
            'new_status' => $new_status
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error or product status was already ' . $new_status . '.',
            'product_id' => $product_id
        ]);
    }
}

    public function salesAndPerformance() {
        $vendor_sales = $this->adminModel->getVendorPayoutPerformance();
        $products_sold = $this->adminModel->getVendorProductsSoldPerformance();

        if ($vendor_sales === false) {
            $vendor_sales = [];
        }
        if ($products_sold === false) {
            $products_sold = [];
        }

        require_once __DIR__ . '/../views/sales.php';
    }
    public function commissionHistory() {
        $commission_history = $this->adminModel->getCommissionHistory();

        if ($commission_history === false || empty($commission_history)) {
            $commission_history = [];
            $error_message = "No commission history found.";
        } else {
            $error_message = "";
        }

        require_once __DIR__ . '/../views/admin_dashboard.php';
    }
}