<?php
// app/controllers/ReceiptController.php (Refactored to Class)

class ReceiptController {
    private $orderModel;
    private $userId;

    public function __construct(PDO $pdo) {
        $this->userId = $_SESSION['user_id'] ?? null;
        // Ensure OrderModel is included and available before instantiation
        $this->orderModel = new OrderModel($pdo); 
    }

    /**
     * Fetches order details, performs PayMongo finalization if necessary, 
     * and renders the receipt view.
     */
    public function viewReceipt(int $orderId) {
        if (!$this->userId) {
            header("Location: index.php?action=login");
            exit;
        }

        if (!$orderId) {
            header("Location: index.php?action=dashboard");
            exit;
        }

        try {
            // 1. Fetch initial order data (scoped by userId for security)
            $orderDetails = $this->orderModel->getSingleOrderDetails($orderId, $this->userId);
            
            if (!$orderDetails) {
                 throw new Exception("Order not found or access denied.");
            }

            // ====================================================================
            // 2. CHECK FOR PAYMONGO FINALIZATION (CRITICAL ASYNCHRONOUS STEP)
            // PayMongo redirects here on success. We must finalize the order now.
            // We check for GCash AND the status created by the temporary order 
            // (which you set to 'Approved' in your example's temporary order model).
            // ====================================================================
            if ($orderDetails['payment_method'] === 'GCash' && $orderDetails['order_status'] === 'Approved') {
                
                // Retrieve temporary cart data stored in the session by placeOrderController
                $processed_items = $_SESSION['order_items_details_' . $this->userId] ?? null;

                // Only run finalization if we have the critical session data (stock/cart info)
                if ($processed_items) {
                    
                    // This method updates DB status to 'Approved', deducts stock, clears cart, and sends notification.
                    $finalization_success = $this->orderModel->finalizeOrderFromPaymongo(
                        $orderId, 
                        $this->userId, 
                        $processed_items
                    );

                    if ($finalization_success) {
                        // Clear temporary session data after successful finalization
                        unset($_SESSION['items_to_delete_' . $this->userId]);
                        unset($_SESSION['order_items_details_' . $this->userId]);
                        
                        // Refetch order details to show the final, committed status
                        $orderDetails = $this->orderModel->getSingleOrderDetails($orderId, $this->userId);
                    } else {
                        // Critical failure during stock/cart operations
                        error_log("CRITICAL: Finalization failed for order {$orderId}. Manual cleanup may be required.");
                        // The user can still see the receipt, but an admin is notified of the issue
                    }
                }
            }
            
            // 3. Fetch final items (can be done before or after finalization, as order_items are set during temp creation)
            $orderItems = $this->orderModel->getOrderItems($orderId);
            
            if (empty($orderItems)) {
                throw new Exception("Order items missing.");
            }
            
            // 4. Render the view
            include __DIR__ . '/../views/receipt.php'; 

        } catch (Exception $e) {
            error_log("Receipt loading error: " . $e->getMessage());
            // Redirect to a user-friendly error page or dashboard
            header("Location: index.php?action=dashboard&error=Could not load receipt.");
            exit;
        }
    }
}