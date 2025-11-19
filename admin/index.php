<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $store_controller_actions = ['view_vendor', 'approve_vendor', 'reject_vendor'];

    if (in_array($_POST['action'], $store_controller_actions)) {
        
        if (!isset($_SESSION['user_id']) || ($_SESSION['is_admin_logged_in'] ?? false) !== true) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit;
        }

        require_once __DIR__ . '/app/controllers/storeController.php';
        exit;
    }
}

$action = $_GET['action'] ?? 'admin_login';

$admin_auth_actions = ['admin_login', 'logout'];

$admin_management_actions = [
    'admin_dashboard', 
    'admin_view_user',
    'pending_stores',
    'approved_stores',
    'rejected_stores',
    'product_approval',
    'view_vendor_products',
    'productApprovalQueue',
    'approveProduct',
    'rejectProduct',
    'getProductDetails',
    'salesAndPerformance',
    'getCommissionHistoryAjax',
];

if (in_array($action, $admin_auth_actions)) {
    require_once __DIR__ . '/app/controllers/AuthController.php';
    $authController = new AuthController();

    switch ($action) {
        case 'logout':
            $authController->logout();
            break;
        case 'admin_login':
        default:
            $authController->adminLogin();
            break;
    }
}

elseif (in_array($action, $admin_management_actions)) {
    if (!isset($_SESSION['user_id']) || ($_SESSION['is_admin_logged_in'] ?? false) !== true) {
        header("Location: index.php?action=admin_login");
        exit;
    }

    require_once __DIR__ . '/app/controllers/adminController.php';
    $adminController = new AdminController();

    switch ($action) {
        case 'admin_dashboard':
            $adminController->dashboard();
            break;
        case 'admin_view_user':
            $adminController->viewUser();
            break;
        case 'pending_stores':
            $adminController->pendingStores();
            break;
        case 'approved_stores':
            $adminController->approvedStores();
            break;
        case 'rejected_stores':
            $adminController->rejectedStores();
            break;
        case 'product_approval':
            $adminController->productApproval();
            break;
        case 'view_vendor_products':
            $adminController->viewVendorProducts(); 
            break;
        case 'productApprovalQueue':
            $adminController->productApprovalQueue();
            break;
        case 'approveProduct':
            $adminController->approveProduct();
            break;
        case 'rejectProduct':
            $adminController->rejectProduct();
            break;
        case 'getProductDetails':
            $adminController->getProductDetails();
            break;
        case 'salesAndPerformance':
            $adminController->salesAndPerformance();
            break;
        case 'getCommissionHistoryAjax': // <-- NEW CASE HANDLER ADDED HERE
            $adminController->getCommissionHistoryAjax();
            break;
            
        default:
            header("Location: index.php?action=admin_dashboard"); 
            exit;
    }
}

else {
    header("Location: index.php?action=admin_login");
    exit;
}