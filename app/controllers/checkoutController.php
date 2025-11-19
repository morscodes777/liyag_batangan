<?php

require_once __DIR__ . '/../models/orderModels.php';
require_once __DIR__ . '/../models/userModels.php';
require_once __DIR__ . '/../models/cartModels.php';
require_once __DIR__ . '/../models/addressModels.php';

class CheckoutController {
    private $orderModel;
    private $userModel;
    private $cartModel;
    private $addressModel; 

    public function __construct($db) {
        $this->orderModel = new OrderModel($db);
        $this->userModel = new UserModel($db); 
        $this->cartModel = new CartModel($db); 
        $this->addressModel = new AddressModel($db); 
    }

    public function checkout() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=cart");
            exit;
        }

        $selectedItemsString = $_POST['selected_items'] ?? '';
        $checkoutTotal = filter_input(INPUT_POST, 'checkout_total', FILTER_VALIDATE_FLOAT);

        if (empty($selectedItemsString) || $checkoutTotal === false || $checkoutTotal <= 0) {
            $_SESSION['message'] = "Invalid selection or total. Please select items to proceed.";
            $_SESSION['message_type'] = 'error';
            header("Location: index.php?action=cart");
            exit;
        }
        
        $_SESSION['checkout_data'] = [
            'selected_item_ids_string' => $selectedItemsString,
            'checkout_total' => $checkoutTotal
        ];

        header("Location: index.php?action=checkout_summary");
        exit;
    }

    public function summary() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $userId = $_SESSION['user_id'];
        
        $checkoutData = $_SESSION['checkout_data'] ?? null;
        
        if (empty($checkoutData) || !isset($checkoutData['selected_item_ids_string'])) {
            $_SESSION['message'] = "No items found for checkout summary. Please select items from your cart.";
            $_SESSION['message_type'] = 'error';
            header("Location: index.php?action=cart"); 
            exit;
        }

        $checkout_total = (float)$checkoutData['checkout_total'];
        $selectedItemsString = $checkoutData['selected_item_ids_string'];

        $cart_item_ids = array_map('intval', array_filter(explode(',', $selectedItemsString)));
        
        if (empty($cart_item_ids) || $checkout_total <= 0) {
            $_SESSION['message'] = "Invalid items selected for checkout.";
            $_SESSION['message_type'] = 'error';
            header("Location: index.php?action=cart"); 
            exit;
        }

        $_SESSION['cart_item_ids_to_purchase'] = $cart_item_ids;

        $selected_items = $this->cartModel->getCartItemsByIds($cart_item_ids, $userId);
        
        if (empty($selected_items) || count($selected_items) !== count($cart_item_ids)) {
            $_SESSION['message'] = "Security/Data Error: Some selected items could not be validated. Please try again.";
            $_SESSION['message_type'] = 'error';
            header("Location: index.php?action=cart");
            exit;
        }
        
        unset($_SESSION['checkout_data']);

        $user_addresses = $this->addressModel->getUserAddresses($userId); 
        $user_info = $this->userModel->getUserById($userId);
        
        include __DIR__ . '/../views/order.php';
    }

    public function placeOrder() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['final_checkout'])) {
            header("Location: index.php?action=checkout_summary"); 
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        $selectedAddressValue = $_POST['delivery_address_id'] ?? null;
        $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
        $orderTotal = filter_input(INPUT_POST, 'order_total', FILTER_VALIDATE_FLOAT); 
        
        $cartItemIdsToValidate = $_SESSION['cart_item_ids_to_purchase'] ?? []; 
        
        $deliveryAddressId = null;

        // --- NEW ADDRESS HANDLING LOGIC ---
        if ($selectedAddressValue === 'new_address') {
            $fullAddress = trim($_POST['new_full_address'] ?? '');
            $latitude = filter_input(INPUT_POST, 'new_latitude', FILTER_VALIDATE_FLOAT);
            $longitude = filter_input(INPUT_POST, 'new_longitude', FILTER_VALIDATE_FLOAT);
            $label = trim($_POST['new_label'] ?? 'New Delivery Address');
            $contactNumber = trim($_POST['new_contact_number'] ?? $this->userModel->getUserById($userId)['phone_number'] ?? '');

            if (empty($fullAddress) || $latitude === false || $longitude === false) {
                $_SESSION['message'] = "Error: New address details are incomplete or invalid.";
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?action=checkout_summary");
                exit;
            }

            // This requires AddressModel::saveNewAddress to exist
            $newAddressId = $this->addressModel->saveNewAddress(
                $userId, 
                $label, 
                $fullAddress, 
                $latitude, 
                $longitude,
                $contactNumber
            );

            if ($newAddressId) {
                $deliveryAddressId = $newAddressId;
            } else {
                $_SESSION['message'] = "Error: Failed to save new delivery address to the database.";
                $_SESSION['message_type'] = 'error';
                header("Location: index.php?action=checkout_summary");
                exit;
            }

        } else {
            // An existing address was selected
            $deliveryAddressId = filter_var($selectedAddressValue, FILTER_VALIDATE_INT);
        }
        // --- END NEW ADDRESS HANDLING LOGIC ---


        $validAddress = $this->addressModel->getAddressById($deliveryAddressId, $userId);
        $selected_items = $this->cartModel->getCartItemsByIds($cartItemIdsToValidate, $userId); 

        // Final security check
        if (!$validAddress || !in_array($paymentMethod, ['COD', 'GCash']) || $orderTotal <= 0 || empty($selected_items)) {
            $_SESSION['message'] = "Error: Invalid checkout details. Please ensure a valid address and payment method are selected.";
            $_SESSION['message_type'] = 'error';
            header("Location: index.php?action=checkout_summary"); 
            exit;
        }
        
        unset($_SESSION['cart_item_ids_to_purchase']);
        
        $newOrderId = $this->orderModel->createOrder($userId, $deliveryAddressId, $paymentMethod, $orderTotal, $selected_items);

        if ($newOrderId) {
            foreach ($selected_items as $item) {
                $this->cartModel->deleteItem($item['cart_item_id'], $userId);
            }

            $_SESSION['message'] = "Order #{$newOrderId} placed successfully! Please proceed with payment.";
            $_SESSION['message_type'] = 'success';
            
            $successAction = 'order_success'; 
            
            if ($paymentMethod === 'GCash') {
                // Assuming 'gcash_payment' routes to the PaymentController
                header("Location: index.php?action=gcash_payment&order_id={$newOrderId}");
            } else {
                // Redirect to the index router action that handles the receipt view
                header("Location: index.php?action={$successAction}&order_id={$newOrderId}");
            }
            exit;
        } else {
            $_SESSION['message'] = "Order placement failed due to a system error. Please try again.";
            $_SESSION['message_type'] = 'error';
            header("Location: index.php?action=checkout_summary");
            exit;
        }
    }
}