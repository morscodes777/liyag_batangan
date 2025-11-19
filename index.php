<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user']['user_id'])) {
        $_SESSION['user_id'] = $_SESSION['user']['user_id'];
    } elseif (isset($_SESSION['user']['id'])) {
        $_SESSION['user_id'] = $_SESSION['user']['id'];
    }
}

// Default page changed from 'login' to 'main_page'
$action = $_GET['action'] ?? 'main_page';

switch ($action) {

    // ✅ New starting page
   case 'main_page':
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/productModels.php'; 
        
        // --- Fetch Data for Homepage ---
        $recommended_products = [];
        try {
            $database = new Database();
            $pdo = $database->connect(); 
            if ($pdo instanceof PDO) {
                $productModel = new ProductModel($pdo);
                // Fetch 4 random active, in-stock products for the "Featured Products" section
                // NOTE: The model uses ProductModel::readRandom($limit)
                $recommended_products = $productModel->readRandom(4); 
            }
        } catch (Throwable $e) {
            error_log("Main page product load error: " . $e->getMessage());
            // Proceed with an empty array if loading fails
        }
        // --- End Fetch Data ---
        
        // Include the view, which will now use $recommended_products
        require_once __DIR__ . '/app/views/main_page.php';
        break;

    case 'register':
    case 'login':
    case 'logout':
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        
        try {
            $database = new Database();
            $pdo = $database->connect(); 
            if (!($pdo instanceof PDO)) {
                throw new Exception('Database connection failed.');
            }
        } catch (Throwable $e) {
            error_log("FATAL ERROR: Failed to establish database connection for AuthController: " . $e->getMessage());
            die("Site Maintenance: Database Error.");
        }
        
        require_once __DIR__ . '/app/controllers/authController.php';
        
        $authController = new AuthController($pdo);

        switch ($action) {
            case 'register':
                $authController->register();
                break;
            case 'logout':
                $authController->logout();
                break;
            case 'login':
            default:
                $authController->login();
                break;
        }
        break;

    case 'send_password_otp':
        require_once __DIR__ . '/app/api/send_password_otp.php';
        break;
        
    case 'verify_password_otp':
        require_once __DIR__ . '/app/api/verify_password_otp.php';
        break;

    case 'verify_and_register':
        require_once __DIR__ . '/app/api/verify_and_register.php';
        break;
        
        
    case 'account':
        require_once __DIR__ . '/app/controllers/accountController.php';
        $accountController = new AccountController();
        $accountController->profile();
        break;

    case 'home':
        require_once __DIR__ . '/app/controllers/homeController.php';
        $homeController = new HomeController();
        $homeController->index();
        break;

    case 'track_orders':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }
        
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/orderModels.php'; 
        require_once __DIR__ . '/app/models/userModels.php';
        require_once __DIR__ . '/app/models/addressModels.php';
        require_once __DIR__ . '/app/models/notificationModels.php';
        require_once __DIR__ . '/app/controllers/orderController.php'; 
        
        try {
            $database = new Database();
            $pdo = $database->connect(); 
            
            $orderModel = new OrderModel($pdo);
            $userModel = new UserModel($pdo);
            $addressModel = new AddressModel($pdo);
            $notificationModel = new NotificationModel($pdo);
            
            $orderController = new OrderController($pdo, $orderModel, $userModel, $addressModel, $notificationModel); 
            $orderController->trackOrders();
        } catch (Throwable $e) {
            error_log("Order tracking error: " . $e->getMessage());
            $_SESSION['error_message'] = "Could not load orders due to a server error.";
            header("Location: index.php?action=account");
            exit;
        }
        break;

    case 'track_orders_vendor':
        error_log("DEBUG: Case 'track_orders_vendor' started.");
        
        $userType = $_SESSION['user']['user_type'] ?? '';
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId || $userType !== 'Vendor') {
            error_log("DEBUG: Vendor access denied. User ID: " . ($userId ?? 'N/A') . ", Type: " . $userType);
            header("Location: index.php?action=login");
            exit;
        }
        error_log("DEBUG: Vendor ID " . $userId . " authenticated and authorized.");
        
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/userModels.php'; 
        require_once __DIR__ . '/app/models/addressModels.php';
        require_once __DIR__ . '/app/models/orderModels.php';
        require_once __DIR__ . '/app/models/notificationModels.php';
        require_once __DIR__ . '/app/controllers/orderController.php';
        error_log("DEBUG: All necessary files required.");

        try {
            $database = new Database();
            $pdo = $database->connect();
            if (!($pdo instanceof PDO)) {
                throw new Exception('Database connection failed to return a PDO instance.');
            }
            error_log("DEBUG: PDO database connection established.");
            
            $orderModel = new OrderModel($pdo);
            $userModel = new UserModel($pdo); 
            $addressModel = new AddressModel($pdo); 
            $notificationModel = new NotificationModel($pdo);
            error_log("DEBUG: Models instantiated.");

            $controller = new OrderController($pdo, $orderModel, $userModel, $addressModel, $notificationModel); 
            error_log("DEBUG: OrderController instantiated.");
            
            $controller->trackOrdersVendor();
            error_log("DEBUG: Calling controller->trackOrdersVendor() completed successfully.");

        } catch (Throwable $e) {
            error_log("ERROR: Vendor track setup failed. " . $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getFile());
            $_SESSION['error_message'] = "Server configuration error. Please contact support. (Code 99)";
            header("Location: index.php?action=login"); 
            exit;
        }
        break;
    case 'get_vendor_order_details':
    case 'get_order_details_vendor':
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/userModels.php';
        require_once __DIR__ . '/app/models/addressModels.php';
        require_once __DIR__ . '/app/models/orderModels.php';
        require_once __DIR__ . '/app/models/notificationModels.php';
        include 'app/controllers/orderController.php';
    
        try {
            $database = new Database();
            $pdo = $database->connect();
            $orderModel = new OrderModel($pdo);
            $userModel = new UserModel($pdo); 
            $addressModel = new AddressModel($pdo); 
            $notificationModel = new NotificationModel($pdo);
    
            $controller = new OrderController($pdo, $orderModel, $userModel, $addressModel, $notificationModel);
            $controller->getOrderDetailsVendorAjax();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
        break;


    case 'update_vendor_order_status':
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/userModels.php';
        require_once __DIR__ . '/app/models/addressModels.php';
        require_once __DIR__ . '/app/models/orderModels.php';
        require_once __DIR__ . '/app/models/notificationModels.php';
        include 'app/controllers/orderController.php';

        try {
            $database = $database ?? new Database();
            $pdo = $pdo ?? $database->connect();
            $orderModel = $orderModel ?? new OrderModel($pdo);
            $userModel = $userModel ?? new UserModel($pdo); 
            $addressModel = $addressModel ?? new AddressModel($pdo); 
            $notificationModel = $notificationModel ?? new NotificationModel($pdo);

            $controller = new OrderController($pdo, $orderModel, $userModel, $addressModel, $notificationModel);
           $controller->updateOrderStatusVendor();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
        break;

    
    case 'cart':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/cartModels.php'; 
        require_once __DIR__ . '/app/controllers/cartController.php'; 
        try {
            $database = new Database();
            $db_conn = $database->connect(); 
            if (!($db_conn instanceof PDO)) {
                throw new Exception('Database connection failed to return a PDO instance.');
            }
            $controller = new CartController($db_conn);
            $controller->viewCart();
        } catch (Throwable $e) {
            error_log("Cart load error: " . $e->getMessage());
            $_SESSION['error_message'] = "Could not load cart due to a server error.";
            header("Location: index.php?action=home");
            exit;
        }
        break;

    case 'update_cart_quantity':
    case 'delete_cart_item':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
            exit;
        }
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/cartModels.php'; 
        require_once __DIR__ . '/app/controllers/cartController.php'; 
        header('Content-Type: application/json');
        try {
            $database = new Database();
            $db_conn = $database->connect(); 
            if (!($db_conn instanceof PDO)) {
                throw new Exception('Database connection failed to return a PDO instance.');
            }
            $controller = new CartController($db_conn); 
            if ($action == 'update_cart_quantity') {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
                    exit;
                }
                $controller->updateQuantity();
            } else {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
                    exit;
                }
                $controller->deleteItem();
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Server error: ' . $e->getMessage() . ' on line ' . $e->getLine()
            ]);
        }
        exit;

    case 'checkout':
    case 'checkout_summary':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/userModels.php';
        require_once __DIR__ . '/app/models/cartModels.php';
        require_once __DIR__ . '/app/models/addressModels.php';
        require_once __DIR__ . '/app/models/orderModels.php'; 
        require_once __DIR__ . '/app/controllers/checkoutController.php'; 
        try {
            $database = new Database();
            $db_conn = $database->connect(); 
            if (!($db_conn instanceof PDO)) {
                throw new Exception('Database connection failed to return a PDO instance.');
            }
            $controller = new CheckoutController($db_conn); 
            if ($action === 'checkout') {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    header("Location: index.php?action=cart");
                    exit;
                }
                $controller->checkout(); 
            } elseif ($action === 'checkout_summary') {
                $controller->summary(); 
            }
        }
        catch (Throwable $e) {
            error_log("FATAL CHECKOUT ERROR: " . $e->getMessage());
            $_SESSION['error_message'] = "Checkout failed due to a server error.";
            header("Location: index.php?action=checkout_summary"); 
            exit;
        }
        break;
        
    case 'place_order':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'User not logged in.']);
            exit;
        }
        
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/userModels.php';
        require_once __DIR__ . '/app/models/addressModels.php';
        require_once __DIR__ . '/app/models/orderModels.php';
        require_once __DIR__ . '/app/models/notificationModels.php';
        require_once __DIR__ . '/app/controllers/orderController.php';
        
        try {
            $database = new Database();
            $pdo = $database->connect();
            
            $orderModel = new OrderModel($pdo);
            $userModel = new UserModel($pdo);
            $addressModel = new AddressModel($pdo);
            $notificationModel = new NotificationModel($pdo);
            
            $controller = new OrderController($pdo, $orderModel, $userModel, $addressModel, $notificationModel);
            $controller->placeOrder();
        } catch (Throwable $e) {
            error_log("Place Order Fatal Error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Order failed due to a server error.';
            header("Location: index.php?action=checkout");
            exit;
        }
        break;
        
    case 'receipt':
        if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
            header("Location: index.php?action=home");
            exit;
        }
        
        // --- Required Model/Config Files ---
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/orderModels.php';
        
        // Include the new class-based controller
        require_once __DIR__ . '/app/controllers/receiptController.php'; 
        
        try {
            $database = new Database();
            $pdo = $database->connect(); 
            if (!($pdo instanceof PDO)) {
                throw new Exception('Database connection failed to return a PDO instance.');
            }
            
            // Instantiate the new ReceiptController
            $controller = new ReceiptController($pdo); 
            
            // Call the viewReceipt method which now handles GCash finalization
            $controller->viewReceipt((int)$_GET['order_id']); 
            
        } catch (Throwable $e) {
            error_log("RECEIPT LOAD ERROR: " . $e->getMessage());
            $_SESSION['error_message'] = "Could not load order details.";
            header("Location: index.php?action=account");
            exit;
        }
        break;

    case 'search_suggestions':
        require_once __DIR__ . '/app/controllers/searchController.php';
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        
        try {
            $database = new Database();
            $pdo = $database->connect();
            if (!($pdo instanceof PDO)) {
                throw new Exception('Database connection failed.');
            }
            
            $searchController = new SearchController($pdo);
            $searchController->getSuggestions();
        } catch (Throwable $e) {
            error_log("Search action error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['stores' => [], 'products' => []]);
            exit;
        }
        break;

    

    case 'process_payment':
    case 'payment_callback':
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/orderModels.php'; 
        require_once __DIR__ . '/app/controllers/paymentController.php'; 
        try {
            $database = new Database();
            $db_conn = $database->connect();
            if (!($db_conn instanceof PDO)) {
                throw new Exception('Database connection failed to return a PDO instance.');
            }
            $controller = new PaymentController($db_conn);
            if ($action === 'process_payment') {
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    header("Location: index.php?action=checkout_summary");
                    exit;
                }
                $controller->processPayment();
            } else {
                $controller->handleCallback();
            }
        } catch (Throwable $e) {
            error_log("Payment error: " . $e->getMessage());
            $_SESSION['error_message'] = "Payment process failed due to a server error.";
            header("Location: index.php?action=checkout_summary");
            exit;
        }
        break;
    case 'reviews': 
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }
        require_once __DIR__ . '/app/views/reviews.php';
        break;

    case 'get_product_reviews':
    case 'submit_review':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit;
        }
        
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/reviewModels.php';
        require_once __DIR__ . '/app/models/userModels.php';
        require_once __DIR__ . '/app/controllers/reviewController.php';

        header('Content-Type: application/json');
        try {
            $database = new Database();
            $pdo = $database->connect();
            if (!($pdo instanceof PDO)) {
                throw new Exception('Database connection failed.');
            }

            $reviewModel = new ReviewModel($pdo);
            $userModel = new UserModel($pdo); 

            $controller = new ReviewController($pdo, $reviewModel, $userModel);

            if ($action === 'get_product_reviews') {
                $controller->getProductReviewsApi();
            } elseif ($action === 'submit_review') {
                $controller->submitReviewApi();
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Review API server error.']);
            error_log("Review API Error: " . $e->getMessage());
            exit;
        }
        exit;
    
    case 'all_stores':
        require_once __DIR__ . '/app/controllers/homeController.php';
        $homeController = new HomeController();
        $homeController->allStores();
        break;

    case 'update_profile':
        require_once __DIR__ .'/app/controllers/accountController.php';
        $controller = new AccountController();
        $controller->updateProfile();
        break;
    
    case 'products':
        require_once __DIR__ . '/app/controllers/productController.php';
        require_once __DIR__ . '/app/models/productModels.php';
        $user = $_SESSION['user'] ?? null;
        $displayUserName = $user['name'] ?? 'User';
        $userProfilePicture = $user['profile_picture'] ?? null;
        $categoryId = $_GET['category_id'] ?? 'all';
        $productController = new ProductController();
        $products = $productController->getProductsByCategory($categoryId);
        include __DIR__ . '/app/views/products.php';
        break;

    case 'create_business':
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/businessModels.php';
        require_once __DIR__ . '/app/controllers/newBusinessController.php'; 
        try {
            $database = new Database();
            $db_conn = $database->connect();
            $vendorController = new VendorController($db_conn);
            $vendorController->createBusinessView();
        } catch (Throwable $e) {
            error_log("Error loading business view: " . $e->getMessage());
            header("Location: index.php?action=home");
            exit;
        }
        break;

    case 'submit_business': 
        require_once __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        require_once __DIR__ . '/app/models/businessModels.php';
        require_once __DIR__ . '/app/controllers/newBusinessController.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $database = new Database();
                $db_conn = $database->connect();
                $vendorController = new VendorController($db_conn);
                $vendorController->submitBusiness();
            } catch (Throwable $e) {
                $_SESSION['error'] = "Server error during submission. " . $e->getMessage();
                header("Location: index.php?action=create_business");
                exit;
            }
        } else {
            header("Location: index.php?action=create_business");
            exit;
        }
        break;

    case 'view_vendor_store':
        require_once __DIR__ . '/app/controllers/storeController.php';
        $controller = new StoreController();
        if ($action === 'view_vendor_store') {
            $controller->viewMyStore();
        } else {
            $controller->viewStore();
        }
    break;

    case 'view_vendor_product':
        require_once __DIR__ . '/app/controllers/vendor_productController.php';
        $productController = new ProductController();
        $productController->viewProduct();
    break;

    case 'update_product':
        require_once __DIR__ . '/app/controllers/vendor_productController.php';
        $productController = new ProductController();
        $productController->updateProduct();
    break;

    case 'delete_product':
        require_once __DIR__ . '/app/controllers/vendor_productController.php';
        $productController = new ProductController();
        $productController->deleteProduct();
    break;

    case 'view_vendor_products_by_category':
        require_once __DIR__ . '/app/controllers/vendor_productController.php';
        $productController = new ProductController();
        $productController->viewVendorProductsByCategory();
    break;

    case 'create_product':
        require_once __DIR__ . '/app/controllers/vendor_productController.php';
        $productController = new ProductController();
        $productController->createProduct();
    break;

    case 'view_store':
        require_once __DIR__ . '/app/controllers/storeController.php';
        $controller = new StoreController();
        $controller->viewStore();
        break;
    case 'add_to_cart':
        require_once __DIR__ . '/app/controllers/homeController.php';
        $homeController = new HomeController();
        $homeController->addToCart(); 
        break;
    case 'fetch_product_reviews':
        require_once __DIR__ . '/app/controllers/homeController.php';
        $homeController = new HomeController();
        $homeController->fetchProductReviews();
    break;

    case 'api_get_notifications':
    case 'api_mark_notification_read':
    case 'api_delete_notification':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
            exit;
        }
        $pdoFile = __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        $controllerFile = __DIR__ . '/app/controllers/notificationController.php';
        if (!is_file($pdoFile) || !is_file($controllerFile)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing essential files for notifications.']);
            exit;
        }
        require_once $pdoFile;
        require_once $controllerFile;
        try {
            $database = new Database();
            $db_conn = $database->connect();
            if (!($db_conn instanceof PDO)) {
                throw new Exception('Database connect() did not return a PDO instance.');
            }
            $notificationController = new NotificationController($db_conn);
            switch ($action) {
                case 'api_get_notifications':
                    $notificationController->getNotificationsApi();
                    break;
                case 'api_mark_notification_read':
                    $notificationController->markAsReadApi(); 
                    break;
                case 'api_delete_notification':
                    $notificationController->deleteNotificationApi();
                    break;
                default:
                    http_response_code(400); 
                    echo json_encode(['success' => false, 'message' => 'Invalid notification action.']);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
        exit;
        
    case 'get_vendor_threads':
    case 'get_thread_messages':
    case 'start_chat_with_vendor':
    case 'load_chat':
    case 'send_chat':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
            exit;
        }
        $pdoFile = __DIR__ . '/app/config/liyab_batangan_db_pdo.php';
        $controllerFile = __DIR__ . '/app/controllers/chatController.php';
        if (!is_file($pdoFile)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Missing PDO file: $pdoFile"]);
            exit;
        }
        if (!is_file($controllerFile)) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Missing controller file: $controllerFile"]);
            exit;
        }
        require_once $pdoFile;
        require_once $controllerFile;
        if (!class_exists('Database')) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database class not found in PDO file.']);
            exit;
        }
        try {
            $database = new Database();
            $db_conn = $database->connect();
            if (!($db_conn instanceof PDO)) {
                throw new Exception('Database connect() did not return a PDO instance.');
            }
            $chatController = new ChatController($db_conn);
            switch ($action) {
                case 'get_vendor_threads':
                    $chatController->getVendorThreadsAction();
                    break;
                case 'get_thread_messages':
                    $chatController->getThreadMessagesAction();
                    break;
                case 'start_chat_with_vendor':
                    $chatController->findOrCreateThreadAndLoadAction();
                    break;
                case 'load_chat':
                    $chatController->loadChatAction();
                    break;
                case 'send_chat':
                    $chatController->sendChatMessageAction();
                    break;
                default:
                    http_response_code(400); 
                    echo json_encode(['success' => false, 'message' => 'Invalid chat action.']);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        exit;
    
   default:
        header("Location: index.php?action=main_page");
        exit;
}