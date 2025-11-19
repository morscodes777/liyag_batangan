<?php

require_once __DIR__ . '/../models/orderModels.php';

class PaymentController {
    private $db;
    private $orderModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->orderModel = new OrderModel($this->db);
    }

    public function processPayment() {
        // Ensure this method only runs on a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=checkout_summary");
            exit;
        }

        // 1. Retrieve necessary data (e.g., order_id, payment_method, total amount)
        $orderId = $_POST['order_id'] ?? null;
        $paymentMethod = $_POST['payment_method'] ?? 'COD'; // Default to Cash on Delivery

        if (!$orderId) {
            $_SESSION['error_message'] = "Invalid order ID for payment processing.";
            header("Location: index.php?action=view_orders");
            exit;
        }

        // 2. Load the order details
        $order = $this->orderModel->getSingleOrderDetails($orderId, $_SESSION['user_id']);

        if (!$order) {
            $_SESSION['error_message'] = "Order not found or access denied.";
            header("Location: index.php?action=view_orders");
            exit;
        }

        // 3. Handle different payment methods
        switch (strtolower($paymentMethod)) {
            case 'cod':
                // For Cash on Delivery, the payment is successful instantly 
                // from a *system* standpoint, but order status remains 'Pending' or 'Processing'.
                // Your place_order function likely handled the initial save, 
                // so this step might just be a final confirmation/redirect.
                $this->orderModel->updateOrderStatus($orderId, 'Processing', 'Cash on Delivery');
                header("Location: index.php?action=order_success&order_id=" . $orderId);
                exit;

            case 'online':
            case 'paypal':
            case 'stripe':
                // For external gateways, initiate the payment redirection.
                // You would need to include the specific API client here (e.g., PayPal/Stripe SDK)
                // $paymentLink = $this->initiateOnlinePayment($order);
                // header("Location: " . $paymentLink);
                // exit;
                
                // Placeholder for online payment:
                $_SESSION['info_message'] = "Online payment initiated for Order #" . $orderId . ". (Feature not fully implemented)";
                header("Location: index.php?action=checkout_summary");
                exit;

            default:
                $_SESSION['error_message'] = "Invalid payment method selected.";
                header("Location: index.php?action=checkout_summary");
                exit;
        }
    }

    public function handleCallback() {
        // This method handles the return URL from an external payment gateway (e.g., PayPal/Stripe)
        // Note: For real-world use, you must use a secure Webhook/IPN handler, not this simple callback.

        // 1. Get transaction ID/status from GET or POST parameters (depends on gateway)
        $orderId = $_GET['order_id'] ?? null;
        $status = $_GET['status'] ?? 'failed'; 
        $transactionId = $_GET['txn_id'] ?? 'N/A';
        
        if (!$orderId) {
            header("Location: index.php?action=view_orders");
            exit;
        }

        // 2. Verify the payment status with the actual payment gateway (CRITICAL for security)
        // $isVerified = $this->verifyGatewayPayment($transactionId);

        if ($status === 'success') { // && $isVerified
            // 3. Update the order status in your database
            $this->orderModel->updateOrderStatus($orderId, 'Paid', 'Online Payment');
            $this->orderModel->updateOrderTransactionId($orderId, $transactionId);

            $_SESSION['success_message'] = "Payment successful for Order #{$orderId}.";
            header("Location: index.php?action=order_success&order_id=" . $orderId);
            exit;
        } else {
            // Payment failed or was canceled
            $this->orderModel->updateOrderStatus($orderId, 'Payment Failed');

            $_SESSION['error_message'] = "Payment failed or was canceled for Order #{$orderId}.";
            header("Location: index.php?action=view_order_details&order_id=" . $orderId);
            exit;
        }
    }
}