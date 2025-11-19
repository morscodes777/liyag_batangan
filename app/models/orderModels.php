<?php
// app/models/OrderModel.php

class OrderModel {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    }
    
    
    /**
     * Creates a provisional order for online payment. 
     * Status is set to 'Approved'. It does NOT clear the cart.
     * @return int|false The new order_id on success, false on failure.
     */
    public function createTemporaryOrderForPaymongo($userId, $addressId, $paymentMethod, $grandTotal, $shippingFee, array $selectedCartItems) {
        
        $this->db->beginTransaction();
        
        try {
            $orderStatus = 'Approved'; // Initial status for online payments
            $paymentStatus = 'Pending';
            $transactionRef = null;
            $paymongoSessionId = null; // New field for session ID

            // Commission Calculation Logic (Same as in processFullOrder)
            $totalCommission = 0.00;
            $grandTotalFloat = (float)$grandTotal;
            $shippingFeeFloat = (float)$shippingFee;
            
            foreach ($selectedCartItems as $item) {
                $lineTotal = (float)($item['line_total'] ?? 0.00);
                $rate = (float)($item['commission_rate'] ?? 0.00); 
                $commissionAmount = $lineTotal * ($rate / 100.0);
                $totalCommission += $commissionAmount;
            }

            $vendorPayout = $grandTotalFloat - $totalCommission;
            
            // 1. INSERT INTO orders (INCLUDING NEW PAYMONGO FIELDS)
            $sqlOrder = "INSERT INTO orders (user_id, delivery_address_id, payment_method, order_total, shipping_fee, total_commission, vendor_payout, order_status, paymongo_session_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmtOrder = $this->db->prepare($sqlOrder);
            $stmtOrder->execute([
                $userId, 
                $addressId, 
                $paymentMethod, 
                $grandTotalFloat, 
                $shippingFeeFloat, 
                $totalCommission, 
                $vendorPayout, 
                $orderStatus,
                $paymongoSessionId // Initially NULL
            ]);
            
            $orderId = $this->db->lastInsertId();

            if (!$orderId) {
                throw new Exception("Database INSERT failed or lastInsertId returned 0.");
            }

            // 2. INSERT INTO order_items (DO NOT DELETE FROM CART YET)
            $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total, commission_rate) VALUES (?, ?, ?, ?, ?, ?)";
            $sqlDelete = "DELETE FROM cart_item WHERE cart_item_id = ? AND user_id = ?"; // <-- $sqlDelete string is kept
            
            $stmtItem = $this->db->prepare($sqlItem);
            // Removed: $stmtDelete = $this->db->prepare($sqlDelete);

            foreach ($selectedCartItems as $item) {
                $stmtItem->execute([
                    $orderId, 
                    $item['product_id'], 
                    $item['quantity'], 
                    $item['unit_price'], 
                    $item['line_total'],
                    $item['commission_rate']
                ]);
            }

            // 3. INSERT INTO payments
            $sqlPayment = "INSERT INTO payments (order_id, payment_method, amount, payment_status, transaction_ref_id) VALUES (?, ?, ?, ?, ?)";
            $stmtPayment = $this->db->prepare($sqlPayment);
            $stmtPayment->execute([$orderId, $paymentMethod, $grandTotal, $paymentStatus, $transactionRef]);

            // Create Notification
            $notificationTitle = "⏳ Payment Initiated";
            $notificationMessage = "Temporary order #{$orderId} created. Please complete the {$paymentMethod} payment to confirm your purchase.";
            $this->createNotification($userId, $notificationTitle, $notificationMessage);

            $this->db->commit();
            return $orderId;

        } catch (PDOException $e) { 
            $this->db->rollBack();
            error_log("PDO Temporary Order failed: " . $e->getMessage());
            return false;
        } catch (Exception $e) { 
            $this->db->rollBack();
            error_log("Temporary Order failed (Logic Error): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Updates the temporary order with the PayMongo Checkout Session ID.
     * This is needed so we can link the success/webhook back to the order.
     * @return bool
     */
    public function updateOrderWithPaymongoSession(int $orderId, string $sessionId): bool {
        $sql = "UPDATE orders SET paymongo_session_id = ? WHERE order_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sessionId, $orderId]);
    }

    /**
     * Retrieves basic order details using the PayMongo Session ID.
     * Used by the gcash_success controller to verify the order.
     * @return array|false
     */
    public function getOrderDetailsByPaymongoSessionId(string $sessionId) {
        $sql = "SELECT order_id, user_id, order_total, paymongo_session_id, order_status FROM orders WHERE paymongo_session_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Permanently deletes the temporary order and its items.
     * Used for rollback if PayMongo API fails or if payment is cancelled.
     * @return bool
     */
    public function deleteOrder(int $orderId): bool {
        $this->db->beginTransaction();
        try {
            // Delete from dependent tables first
            $this->db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
            $this->db->prepare("DELETE FROM payments WHERE order_id = ?")->execute([$orderId]);
            // Then delete the main order record
            $success = $this->db->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$orderId]);
            
            $this->db->commit();
            return $success;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Failed to delete order (rollback): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Processes the final order, inserting records and clearing the cart.
     * Modified to accept the explicit $orderStatus parameter.
     * @return int|false The new order_id on success, false on failure.
     */
   // Inside app/models/OrderModel.php

// ... (other methods) ...

    /**
     * Processes the final order, inserting records, deducting stock, and clearing the cart.
     * Modified to accept the explicit $orderStatus parameter.
     * @return int|false The new order_id on success, false on failure.
     */
    public function processFullOrder($userId, $addressId, $paymentMethod, $grandTotal, $shippingFee, array $selectedCartItems, string $orderStatus = 'Pending') {
        
        $this->db->beginTransaction();
        
        try {
            $paymentStatus = ($orderStatus === 'Pending' && $paymentMethod === 'COD') ? 'Pending' : 'Completed';
            $transactionRef = ($paymentMethod === 'GCash') ? ('GCASH' . substr(md5(time() . $userId), 0, 10)) : null; 
            
            // Commission Calculation Logic (Omitted for brevity, but remains the same)
            $totalCommission = 0.00;
            $grandTotalFloat = (float)$grandTotal;
            $shippingFeeFloat = (float)$shippingFee;
            
            foreach ($selectedCartItems as $item) {
                $lineTotal = (float)($item['line_total'] ?? 0.00);
                $rate = (float)($item['commission_rate'] ?? 0.00); 
                $commissionAmount = $lineTotal * ($rate / 100.0);
                $totalCommission += $commissionAmount;
            }

            $vendorPayout = $grandTotalFloat - $totalCommission;
            
            error_log("💰 [DEBUG] Commission Calculated: Total Commission=₱" . number_format($totalCommission, 2) . ", Vendor Payout=₱" . number_format($vendorPayout, 2));

            // =========================================================================
            // 1. INSERT INTO orders
            // =========================================================================
            $sqlOrder = "INSERT INTO orders (user_id, delivery_address_id, payment_method, order_total, shipping_fee, total_commission, vendor_payout, order_status, paymongo_session_id) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)"; 
            
            $stmtOrder = $this->db->prepare($sqlOrder);
            $stmtOrder->execute([
                $userId, 
                $addressId, 
                $paymentMethod, 
                $grandTotalFloat, 
                $shippingFeeFloat, 
                $totalCommission, 
                $vendorPayout, 
                $orderStatus,
            ]);
            
            $orderId = $this->db->lastInsertId();

            if (!$orderId) {
                throw new Exception("Database INSERT failed or lastInsertId returned 0.");
            }

            // =========================================================================
            // 2. INSERT INTO order_items AND DELETE FROM cart_item
            // =========================================================================
            $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total, commission_rate) VALUES (?, ?, ?, ?, ?, ?)";
            $sqlDelete = "DELETE FROM cart_item WHERE cart_item_id = ? AND user_id = ?"; 
            
            $stmtItem = $this->db->prepare($sqlItem);
            $stmtDelete = $this->db->prepare($sqlDelete);

            // =========================================================================
            // 3. STOCK DEDUCTION SQL
            // =========================================================================
            $sqlStockDeduct = "UPDATE products SET stock_quantity = stock_quantity - ?, updated_at = NOW() WHERE product_id = ?";
            $stmtStockDeduct = $this->db->prepare($sqlStockDeduct); // NEW STATEMENT

            foreach ($selectedCartItems as $item) {
                // Insert into order_items
                $stmtItem->execute([
                    $orderId, 
                    $item['product_id'], 
                    $item['quantity'], 
                    $item['unit_price'], 
                    $item['line_total'],
                    $item['commission_rate']
                ]);
                
                // Deduct stock from products table (NEW LOGIC)
                $stmtStockDeduct->execute([
                    $item['quantity'], 
                    $item['product_id']
                ]);
                
                // Delete the item from the user's cart
                $stmtDelete->execute([$item['cart_item_id'], $userId]);
            }

            // 4. INSERT INTO payments (No change)
            $sqlPayment = "INSERT INTO payments (order_id, payment_method, amount, payment_status, transaction_ref_id) VALUES (?, ?, ?, ?, ?)";
            $stmtPayment = $this->db->prepare($sqlPayment);
            $stmtPayment->execute([$orderId, $paymentMethod, $grandTotal, $paymentStatus, $transactionRef]);

            // Create successful order notification (No change)
            $notificationTitle = "🎉 Order Placed!";
            $notificationMessage = "Your order #{$orderId} has been successfully placed. Status: {$orderStatus}.";
            $this->createNotification($userId, $notificationTitle, $notificationMessage);


            $this->db->commit();
            return $orderId;

        } catch (PDOException $e) { 
            $this->db->rollBack();
            error_log("PDO Order processing failed: " . $e->getMessage() . " - SQLSTATE: " . $e->getCode());
            return false;
        } catch (Exception $e) { 
            $this->db->rollBack();
            error_log("Order processing failed (Logic Error): " . $e->getMessage());
            return false;
        }
    }
    public function finalizeOrderFromPaymongo(int $orderId, int $userId, array $selectedCartItems): bool {
        $this->db->beginTransaction();

        try {
            // =========================================================================
            // 1. UPDATE ORDER & PAYMENT STATUS
            // =========================================================================
            
            // --- CHANGE MADE HERE ---
            $newOrderStatus = 'Approved'; // Setting the final status to Approved
            // ------------------------
            
            $newPaymentStatus = 'Completed';
            
            // Update Order Status
            $sqlOrderUpdate = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?";
            $this->db->prepare($sqlOrderUpdate)->execute([$newOrderStatus, $orderId]);

            // Update Payment Status
            $sqlPaymentUpdate = "UPDATE payments SET payment_status = ?, payment_date = NOW() WHERE order_id = ?";
            $this->db->prepare($sqlPaymentUpdate)->execute([$newPaymentStatus, $orderId]);


            // =========================================================================
            // 2. DEDUCT STOCK & CLEAR CART (Logic remains the same)
            // =========================================================================
            $sqlStockDeduct = "UPDATE products SET stock_quantity = stock_quantity - ?, updated_at = NOW() WHERE product_id = ?";
            $stmtStockDeduct = $this->db->prepare($sqlStockDeduct);
            
            $sqlDelete = "DELETE FROM cart_item WHERE cart_item_id = ? AND user_id = ?"; 
            $stmtDelete = $this->db->prepare($sqlDelete);

            foreach ($selectedCartItems as $item) {
                // Deduct stock
                $stmtStockDeduct->execute([
                    $item['quantity'], 
                    $item['product_id']
                ]);
                
                // Delete the item from the user's cart
                $stmtDelete->execute([$item['cart_item_id'], $userId]);
            }
            
            // =========================================================================
            // 3. SEND FINAL SUCCESS NOTIFICATION
            // =========================================================================
            $notificationTitle = "✅ Payment Successful! Order Approved!";
            $notificationMessage = "Your payment for order #{$orderId} was successful. Order status is now Approved.";
            $this->createNotification($userId, $notificationTitle, $notificationMessage);


            $this->db->commit();
            return true;

        } catch (PDOException $e) { 
            $this->db->rollBack();
            error_log("❌ PDO Order finalization failed: " . $e->getMessage());
            return false;
        } catch (Exception $e) { 
            $this->db->rollBack();
            error_log("❌ Order finalization failed (Logic Error): " . $e->getMessage());
            return false;
        }
    }
    public function createNotification($userId, $title, $message) {
        try {
            $sql = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            
            $stmt->execute([$userId, $title, $message]);
            return true;
        } catch (PDOException $e) {
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }

    public function getSingleOrderDetails($orderId, $userId) {
        $sql = "
            SELECT 
                o.order_id, o.user_id, o.payment_method, o.order_total, o.shipping_fee, o.order_status, 
                a.label AS address_label, 
                a.full_address, 
                o.order_date AS created_at
            FROM orders o
            LEFT JOIN user_addresses a ON o.delivery_address_id = a.address_id 
            WHERE o.order_id = ? AND o.user_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $order;
    }

    public function updateOrderStatus($orderId, $status, $paymentMethod = null) {
        if ($paymentMethod) {
            $sql = "UPDATE orders SET order_status = ?, payment_method = ?, updated_at = NOW() WHERE order_id = ?";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$status, $paymentMethod, $orderId]);
        } else {
            $sql = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$status, $orderId]);
        }
        return $success;
    }

    /**
     * NEW FUNCTION: Updates the order status only if the order contains at least one item from the given vendor.
     * This is a crucial security check.
     * @param int $orderId
     * @param string $status
     * @param int $vendorId
     * @return bool
     */
    public function updateOrderStatusForVendor($orderId, $status, $vendorId) {
        // SQL to update the order status
        $sqlUpdate = "
            UPDATE orders o
            SET o.order_status = :new_status, o.updated_at = NOW() 
            WHERE o.order_id = :order_id
            AND EXISTS (
                -- Check if the order contains any items sold by this vendor
                SELECT 1
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = o.order_id 
                AND p.vendor_id = :vendor_id 
                LIMIT 1
            )
        ";

        $stmt = $this->db->prepare($sqlUpdate);
        $stmt->bindParam(':new_status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':vendor_id', $vendorId, PDO::PARAM_INT);

        $stmt->execute();

        // Check if a row was actually updated (meaning the check passed and the status was changed)
        return $stmt->rowCount() > 0;
    }

    public function updateOrderTransactionId($orderId, $transactionId) {
        $sql = "UPDATE payments SET transaction_ref_id = ?, payment_status = 'Completed', payment_date = NOW() WHERE order_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$transactionId, $orderId]);
        
        return $success;
    }
    
    public function getOrderItems($orderId) {
        $sql = "
            SELECT 
                oi.*, 
                p.name AS product_name, 
                p.image_url 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC); 
        return $items;
    }
    
    /**
     * Fetches all orders for a user, including delivery and vendor location coordinates,
     * filtered by a specific status.
     * * @param int $userId The ID of the user.
     * @param string $statusFilter The status to filter orders by (e.g., 'Pending', 'Delivered').
     * @return array List of orders with location and item details.
     */
    public function getOrdersWithLocationsByUserId($userId, $statusFilter) {
        
        $sql = "
            SELECT 
                o.order_id, 
                o.order_date, 
                o.order_total, 
                o.order_status, 
                
                -- Delivery Address Coordinates
                ua.latitude AS delivery_lat,
                ua.longitude AS delivery_lng,
                
                -- Vendor Store Coordinates (Using MIN() to get a single vendor's info per order)
                MIN(va.latitude) AS vendor_lat,
                MIN(va.longitude) AS vendor_lng,
                MIN(va.business_name) AS vendor_name
                
            FROM orders o
            
            -- Join to get Delivery Address Coordinates
            LEFT JOIN user_addresses ua ON o.delivery_address_id = ua.address_id
            
            -- Join through order_items and products to get the Vendor
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN vendor_account va ON p.vendor_id = va.vendor_id
            
            WHERE o.user_id = :user_id 
            AND o.order_status = :status_filter
            
            GROUP BY 
                o.order_id, 
                o.order_date, 
                o.order_total, 
                o.order_status,
                o.shipping_fee,
                ua.latitude,
                ua.longitude,
                ua.label,
                ua.full_address
                
            ORDER BY o.order_date DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':status_filter', $statusFilter, PDO::PARAM_STR); 
        
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Iterate through each order and fetch its specific items
        foreach ($orders as &$order) {
            $order['order_items'] = $this->getOrderItems($order['order_id']);
        }
        unset($order); 
        
        return $orders;
    }

    public function countOrdersByStatus($userId) {
        $sql = "
            SELECT 
                order_status, 
                COUNT(order_id) as count 
            FROM orders
            WHERE user_id = :user_id
            GROUP BY order_status
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Fetch as key-value pairs (Status => Count)
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return $results;
    } 

    public function getVendorOrdersByStatus($vendorId, $statusFilter) {
        // Select DISTINCT orders that contain at least one product from this vendor
        $sql = "
            SELECT DISTINCT
                o.order_id,
                o.order_date,
                o.order_total,
                o.shipping_fee,
                o.order_status,
                o.payment_method,
                o.vendor_payout,
                u.name AS customer_name,
                u.user_id AS customer_id,
                a.full_address AS delivery_full_address,
                a.label AS delivery_label
            FROM orders o
            INNER JOIN order_items oi ON o.order_id = oi.order_id
            INNER JOIN products p ON oi.product_id = p.product_id
            INNER JOIN users u ON o.user_id = u.user_id
            LEFT JOIN user_addresses a ON o.delivery_address_id = a.address_id
            WHERE p.vendor_id = :vendor_id 
        ";
        
        // Add status filter if it's set and not empty
        if (!empty($statusFilter)) {
            $sql .= " AND o.order_status = :status_filter";
        }
        
        $sql .= " ORDER BY o.order_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':vendor_id', $vendorId, PDO::PARAM_INT);
        
        // Bind the status only if the condition was added
        if (!empty($statusFilter)) {
            $stmt->bindParam(':status_filter', $statusFilter, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add a function to get the items for a specific order (useful for the modal)
    public function getOrderItemsByOrderId($orderId) {
        $sql = "
            SELECT 
                oi.quantity,
                oi.unit_price,
                p.name AS product_name
            FROM order_items oi
            INNER JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = :order_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getOrderItemsByOrderIdAndVendor($orderId, $vendorId) {
        $sql = "
            SELECT 
                oi.quantity,
                oi.unit_price,
                oi.line_total,
                p.name AS product_name
            FROM order_items oi
            INNER JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = :order_id
            AND p.vendor_id = :vendor_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':vendor_id', $vendorId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSingleVendorOrderDetails($orderId) {
        
        $sql = "
            SELECT 
                o.order_id,
                o.order_date,
                o.order_total,
                o.shipping_fee,
                o.order_status,
                o.payment_method,
                u.name AS customer_name,
                u.user_id AS customer_id,
                u.phone_number AS contact_number,
                a.label AS delivery_label,
                a.full_address AS delivery_full_address,
                a.latitude AS delivery_lat,
                a.longitude AS delivery_lng
            FROM orders o
            INNER JOIN users u ON o.user_id = u.user_id
            LEFT JOIN user_addresses a ON o.delivery_address_id = a.address_id
            
            WHERE o.order_id = :order_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrderUserId(int $orderId): ?int {
        $sql = "SELECT user_id FROM orders WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        $userId = $stmt->fetchColumn();
        return $userId ? (int)$userId : null;
    }

    
}