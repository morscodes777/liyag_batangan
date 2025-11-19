<?php

class OrderController {
    private $orderModel;
    private $addressModel;
    private $userModel;
    private $notificationModel;
    private $db;

    public function __construct(
        PDO $db, 
        OrderModel $orderModel,       
        UserModel $userModel, 
        AddressModel $addressModel,
        NotificationModel $notificationModel
    ) {
        $this->db = $db;
        $this->orderModel = $orderModel; // FIX: Assign the injected OrderModel
        $this->addressModel = $addressModel;
        $this->userModel = $userModel;
        $this->notificationModel = $notificationModel; 
    }

    public function trackOrders() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $currentFilterStatus = $_GET['status'] ?? 'Pending'; 

        $orders = $this->orderModel->getOrdersWithLocationsByUserId($user_id, $currentFilterStatus);

        $statusSteps = ['Pending', 'Approved', 'Shipped', 'Out for Delivery', 'Delivered'];

        require_once __DIR__ . '/../views/track_order.php';
    }


    public function placeOrder() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['final_checkout'])) {
            header('Location: index.php?action=checkout');
            exit;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $_SESSION['error_message'] = 'You must be logged in to place an order.';
            header('Location: index.php?action=login');
            exit;
        }
        
        $userInfo = $this->userModel->getUserDetails($userId); 
        $contactNumber = $userInfo['phone_number'] ?? null;

        if (empty($contactNumber) || $contactNumber === 'N/A') {
            $_SESSION['error_message'] = 'User contact number is required for delivery. Please update your profile.';
            header('Location: index.php?action=edit_profile');
            exit;
        }
        
        $shippingFee = filter_var($_POST['shipping_fee'] ?? 0, FILTER_VALIDATE_FLOAT);
        $orderTotalFinal = filter_var($_POST['order_total_final'] ?? 0, FILTER_VALIDATE_FLOAT); 
        $paymentMethod = $_POST['payment_method'] ?? null;
        $addressSelection = $_POST['delivery_address_id'] ?? null;
        $finalItemsJson = $_POST['final_selected_items_detailed'] ?? '[]';
        
        $orderItemsData = json_decode($finalItemsJson, true);

        if ($orderTotalFinal <= 0 || !in_array($paymentMethod, ['COD', 'GCash']) || empty($orderItemsData)) {
            $_SESSION['error_message'] = 'Invalid payment or item data submitted. Please review your order details.';
            header('Location: index.php?action=checkout');
            exit;
        }
        
        $deliveryAddressId = null;

        if ($addressSelection === 'new_address') {
            $newLabel = trim($_POST['new_address_label'] ?? '');
            $newFullAddress = trim($_POST['new_full_address'] ?? '');
            $newLat = filter_var($_POST['new_latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $newLon = filter_var($_POST['new_longitude'] ?? null, FILTER_VALIDATE_FLOAT);

            if (empty($newLabel) || empty($newFullAddress) || $newLat === false || $newLon === false) {
                 $_SESSION['error_message'] = 'Please complete all new address details (nickname, full address, and map selection).';
                 header('Location: index.php?action=checkout');
                 exit;
            }

            $deliveryAddressId = $this->addressModel->saveNewAddress(
                $userId, 
                $newLabel, 
                $newFullAddress, 
                $newLat, 
                $newLon, 
                $contactNumber
            );
            
        } else {
            $deliveryAddressId = filter_var($addressSelection, FILTER_VALIDATE_INT);

            if (!$this->addressModel->getAddressById($deliveryAddressId, $userId)) {
                 $_SESSION['error_message'] = 'Selected address is invalid.';
                 header('Location: index.php?action=checkout');
                 exit;
            }
        }

        if (!$deliveryAddressId) {
            $_SESSION['error_message'] = 'Could not save or retrieve the delivery address.';
            header('Location: index.php?action=checkout');
            exit;
        }

        $orderId = $this->orderModel->processFullOrder(
            $userId, 
            $deliveryAddressId, 
            $paymentMethod, 
            $orderTotalFinal, 
            $shippingFee, 
            $orderItemsData
        );

        if ($orderId) {
            $_SESSION['success_message'] = 'Order placed successfully!';
            header('Location: index.php?action=receipt&order_id=' . $orderId);
            exit;
        } else {
            $_SESSION['error_message'] = 'Failed to place order due to a system error. Please try again.';
            header('Location: index.php?action=checkout');
            exit;
        }
    }
    
    public function viewReceipt(int $orderId) {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $userId = $_SESSION['user_id'];

        $orderDetails = $this->orderModel->getSingleOrderDetails($orderId, $userId);

        if (!$orderDetails) {
            $_SESSION['error_message'] = "Receipt not found or you don't have permission to view it.";
            header("Location: index.php?action=home");
            exit;
        }

        $orderItems = $this->orderModel->getOrderItems($orderId);

        include __DIR__ . '/../views/receipt.php';
    }

    public function trackOrdersVendor() {
        
        $vendorId = $_SESSION['user']['vendor_id'] ?? null;
        $userType = $_SESSION['user']['user_type'] ?? 'Customer'; 

        if ($userType !== 'Vendor' || !$vendorId) { 
            header("Location: index.php?action=login");
            exit;
        }

        $statusFilter = $_GET['status'] ?? 'Pending';
        $validStatuses = ['Pending', 'Approved', 'Shipped', 'Out for Delivery', 'Delivered'];
        if (!in_array($statusFilter, $validStatuses)) {
            $statusFilter = 'Pending';
        }

        try {
            $orders = $this->orderModel->getVendorOrdersByStatus($vendorId, $statusFilter);
            
            
            if (!empty($orders)) {
                foreach ($orders as &$order) { 
                    $items = $this->orderModel->getOrderItemsByOrderIdAndVendor($order['order_id'], $vendorId);
                    $order['items'] = $items; 
                }
                unset($order); 
            }
            
        } catch (\Throwable $e) {
             error_log("FATAL ERROR in getVendorOrdersByStatus: " . $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getFile());
             throw $e;
        }
        require __DIR__ . '/../views/track_order_vendor.php';
    }
    
    public function getOrderDetailsVendorAjax() {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'An error occurred.', 'data' => null];

        try {
            $vendorId = $_SESSION['user']['vendor_id'] ?? null;
            $userType = $_SESSION['user']['user_type'] ?? null;
            
            if ($userType !== 'Vendor' || !$vendorId) {
                http_response_code(401); 
                $response['message'] = 'Unauthorized or session expired. Please log in again.';
                echo json_encode($response);
                exit;
            }
            
            $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

            if (!$orderId) {
                $response['message'] = 'Invalid Order ID provided.';
                echo json_encode($response);
                exit;
            }

            $orderDetails = $this->orderModel->getSingleVendorOrderDetails($orderId);

            if (!$orderDetails) {
                $response['message'] = 'Order details not found.';
                echo json_encode($response);
                exit;
            }
            
            $orderItems = $this->orderModel->getOrderItemsByOrderIdAndVendor($orderId, $vendorId);
            
            if (empty($orderItems)) {
                error_log("SECURITY ALERT: Vendor " . $vendorId . " attempted to view order " . $orderId . " with no associated items.");
                $response['message'] = 'Order found, but contains no items from your store. Access denied.';
                echo json_encode($response);
                exit;
            }

            $orderDetails['order_items'] = $orderItems;
            
            $response['success'] = true;
            $response['message'] = 'Order details fetched successfully.';
            $response['data'] = $orderDetails;

        } catch (\Throwable $e) { 
            http_response_code(500);
            error_log("AJAX Vendor Order Fatal Error: " . $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getFile());
            $response['message'] = "Internal Server Error: A fatal error occurred on the server (see logs for detail).";
            
            $response['message'] .= " Error: " . $e->getMessage();
        }

        echo json_encode($response);
        exit;
    }


    public function updateOrderStatusVendor() {
        header('Content-Type: application/json');

        $vendorId = $_SESSION['user']['vendor_id'] ?? null;
        $userType = $_SESSION['user']['user_type'] ?? null;

        // The security check is correct
        if ($userType !== 'Vendor' || !$vendorId || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request.']);
            exit;
        }

        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        // FIX HERE: Change 'new_status' to 'status' to match your JavaScript
        $newStatus = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
        
        $validStatuses = ['Pending', 'Approved', 'Shipped', 'Out for Delivery', 'Delivered'];

        if (!$orderId || !in_array($newStatus, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid data provided (Order ID missing or invalid status).']);
            exit;
        }

        $result = $this->orderModel->updateOrderStatusForVendor($orderId, $newStatus, $vendorId);

        if ($result) {
            try {
                $customerId = $this->orderModel->getOrderUserId($orderId);
                if ($customerId) {
                    $title = "Order #{$orderId} Status Update";
                    // Added new_status to the response array for client-side update
                    $message = "Your order status has been updated to **{$newStatus}** by the vendor."; 
                    $link = "index.php?action=track_orders"; 
                    
                    $this->notificationModel->createNotification(
                        $customerId,
                        $title,
                        $message,
                        $link
                    );
                }
                
                http_response_code(200);
                // INCLUDE new_status in the response for JS to update the modal tracker
                echo json_encode(['success' => true, 'message' => 'Status updated successfully.', 'new_status' => $newStatus]); 
            } catch (Throwable $e) {
                error_log("Notification failed for Order #{$orderId}: " . $e->getMessage());
                // Still report primary success, but acknowledge notification failure
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Status updated successfully, but notification failed.', 'new_status' => $newStatus]);
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Database update failed. The order was not updated (may not belong to your store or status is already set).']);
        }
    }
    
}

if (!function_exists('getStatusIndex')) {
    function getStatusIndex($status, $steps) {
        $index = array_search($status, $steps);
        return ($index !== false) ? $index : 0;
    } 
}