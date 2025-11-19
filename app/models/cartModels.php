<?php

class CartModel {
    private $conn;
    private $table_name = "cart_item";

    public function __construct($db) {
        // $db is expected to be a PDO connection object
        $this->conn = $db;
    }

    public function addItem($user_id, $product_id, $quantity, $total_price_of_new_item) {
        // 1. Check for existing item
        $query = "SELECT cart_item_id, quantity, price FROM " . $this->table_name . " WHERE user_id = ? AND product_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $product_id]);
        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_item) {
            // Update item
            $new_quantity = $existing_item['quantity'] + $quantity;
            $new_total_price = $existing_item['price'] + $total_price_of_new_item; 

            $update_query = "UPDATE " . $this->table_name . " SET quantity = ?, price = ?, updated_at = NOW() WHERE cart_item_id = ?"; 
            
            $update_stmt = $this->conn->prepare($update_query); 
            $success = $update_stmt->execute([$new_quantity, $new_total_price, $existing_item['cart_item_id']]);
            
            return $success;

        } else {
            // Insert new item
            $insert_query = "INSERT INTO " . $this->table_name . " (user_id, product_id, quantity, price, updated_at) 
                             VALUES (?, ?, ?, ?, NOW())"; // Using NOW() for updated_at in insert as well
            $insert_stmt = $this->conn->prepare($insert_query);
            $success = $insert_stmt->execute([$user_id, $product_id, $quantity, $total_price_of_new_item]); 
            
            return $success;
        }
    }

    public function readCartItemsWithProductDetails($user_id) {
        $query = "
            SELECT 
                ci.cart_item_id, 
                ci.quantity, 
                ci.price AS line_total,
                ci.created_at,
                ci.updated_at, 
                p.product_id,
                p.name AS product_name,
                p.image_url,
                p.price AS unit_price
            FROM 
                " . $this->table_name . " ci 
            INNER JOIN 
                products p ON ci.product_id = p.product_id
            WHERE 
                ci.user_id = ?
            ORDER BY 
                COALESCE(ci.updated_at, ci.created_at) DESC, ci.cart_item_id DESC"; 

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            // PDO error handling is different. We trust the query to be good 
            // and rely on try-catch in the controller, but can check for preparation error.
            $errorInfo = $this->conn->errorInfo();
            if ($errorInfo[0] !== '00000') {
                 throw new Exception("Error preparing cart query: " . $errorInfo[2]);
            }
        }
        
        // 1. PDO execute with parameters array
        $stmt->execute([$user_id]);

        // 2. PDO fetch all results
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. No need for fetch_assoc loop or $stmt->close()
        return $items;
    }
    
    public function getCartItemsByIds($cart_item_ids, $user_id) {
        if (empty($cart_item_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($cart_item_ids), '?'));
        
        $query = "
            SELECT 
                ci.cart_item_id, 
                ci.quantity, 
                ci.price AS line_total,
                ci.created_at,
                ci.updated_at, 
                p.product_id,
                p.name AS product_name,
                p.image_url,
                p.price AS unit_price
            FROM 
                " . $this->table_name . " ci 
            INNER JOIN 
                products p ON ci.product_id = p.product_id
            WHERE 
                ci.user_id = ? AND ci.cart_item_id IN ($placeholders)
            ORDER BY 
                ci.cart_item_id ASC";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            $errorInfo = $this->conn->errorInfo();
            throw new Exception("Error preparing checkout query: " . $errorInfo[2]);
        }

        // The parameters array: [$user_id, $id1, $id2, ...]
        $params = array_merge([$user_id], $cart_item_ids);
        
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateItemQuantity($cart_item_id, $user_id, $new_quantity) {
        // 1. Get unit price
        $query_price = "SELECT p.price FROM products p JOIN cart_item ci ON p.product_id = ci.product_id WHERE ci.cart_item_id = ? AND ci.user_id = ?";
        $stmt_price = $this->conn->prepare($query_price);
        $stmt_price->execute([$cart_item_id, $user_id]);
        $item = $stmt_price->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            return false;
        }

        $unit_price = $item['price'];
        $new_line_total = $unit_price * $new_quantity;

        // 2. Update cart item
        $query_update = "UPDATE cart_item SET quantity = ?, price = ?, updated_at = NOW() WHERE cart_item_id = ? AND user_id = ?";
        $stmt_update = $this->conn->prepare($query_update);
        
        if ($stmt_update->execute([$new_quantity, $new_line_total, $cart_item_id, $user_id])) {
            return $stmt_update->rowCount() > 0;
        } else {
            return false;
        }
    }

    public function deleteItem($cart_item_id, $user_id) {
        $query = "DELETE FROM cart_item WHERE cart_item_id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([$cart_item_id, $user_id])) {
            return $stmt->rowCount() > 0;
        } else {
            return false;
        }
    }

    public function getCartItemTimestamp($cart_item_id, $user_id) {
        $query = "SELECT updated_at FROM " . $this->table_name . " WHERE cart_item_id = ? AND user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$cart_item_id, $user_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $item;
    }
}