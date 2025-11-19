<?php
// app/models/OrderModel.php

class OrderModel {
    private $pdo;
    private $order_table = "orders";
    private $item_table = "order_items";

    public function __construct(PDO $db) {
        $this->pdo = $db;
    }

    /**
     * Creates a new order and inserts the associated order items within a single database transaction.
     * @param int $userId The ID of the user placing the order.
     * @param int $addressId The ID of the selected delivery address.
     * @param string $paymentMethod The payment method ('COD' or 'GCash').
     * @param float $orderTotal The total cost of the order.
     * @param array $selectedItems An array of cart items to include in the order.
     * @return int|false The new order ID on success, or false on failure.
     */
    public function createOrder($userId, $addressId, $paymentMethod, $orderTotal, $selectedItems) {
        if (empty($selectedItems)) return false;

        try {
            // Start Transaction
            $this->pdo->beginTransaction();

            // 1. Insert the main order record
            $order_query = "INSERT INTO " . $this->order_table . " 
                            (user_id, address_id, payment_method, total_amount, status, created_at) 
                            VALUES (?, ?, ?, ?, 'Pending', NOW())";
            
            $stmt = $this->pdo->prepare($order_query);
            $stmt->execute([$userId, $addressId, $paymentMethod, $orderTotal]);
            
            $orderId = $this->pdo->lastInsertId();

            if (!$orderId) {
                $this->pdo->rollBack();
                error_log("Failed to get lastInsertId after creating order.");
                return false;
            }

            // 2. Insert order items
            $item_query = "INSERT INTO " . $this->item_table . " 
                            (order_id, product_id, vendor_id, quantity, price, subtotal) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            
            $item_stmt = $this->pdo->prepare($item_query);

            // Group items by vendor to handle potential multi-vendor orders (optional but good practice)
            $vendor_subtotals = [];

            foreach ($selectedItems as $item) {
                // Ensure the keys match what's returned by CartModel::getCartItemsByIds
                $productId = $item['product_id'];
                $vendorId = $item['vendor_id']; // Assuming your cart item structure includes this
                $quantity = $item['quantity'];
                $price = $item['product_price']; // Assuming this is the price at the time of order
                $subtotal = $quantity * $price;

                if (!$item_stmt->execute([$orderId, $productId, $vendorId, $quantity, $price, $subtotal])) {
                    throw new Exception("Failed to insert order item for product_id: " . $productId);
                }
                
                // Track subtotal per vendor
                $vendor_subtotals[$vendorId] = ($vendor_subtotals[$vendorId] ?? 0) + $subtotal;
            }

            // Optional: Update the order to reflect vendor subtotals or create vendor-specific sub-orders here if needed.

            // Commit Transaction
            $this->pdo->commit();
            return $orderId;

        } catch (Exception $e) {
            // Rollback the transaction on any error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Order creation failed (Transaction Rolled Back): " . $e->getMessage());
            return false;
        }
    }
}