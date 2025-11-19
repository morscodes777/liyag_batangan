<?php
// index.php?action=place_order Controller Logic

// Assume these variables and models are initialized before this code runs:
// $db = new PDO(...); // The PDO object
// $orderModel = new OrderModel($db);
// $addressModel = new AddressModel($db); // Assumed to exist for address saving

$shipping_fee = 50.00;
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: index.php?action=login&redirect=checkout');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_checkout'])) {
    
    $delivery_address_id = $_POST['delivery_address_id'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;
    $order_subtotal = (float)($_POST['order_total'] ?? 0.00); 
    $grand_total = $order_subtotal + $shipping_fee;
    $selected_items_json = $_POST['final_selected_items'] ?? '[]';

    // --- Validation ---
    if (empty($delivery_address_id) || empty($payment_method) || $order_subtotal <= 0) {
        header('Location: index.php?action=checkout&error=invalid_data');
        exit;
    }

    // --- A. Address Handling ---
    if ($delivery_address_id === 'new_address') {
        $new_label = trim($_POST['new_address_label'] ?? 'New Address');
        $new_full_address = trim($_POST['new_full_address'] ?? '');
        $new_latitude = $_POST['new_latitude'] ?? null;
        $new_longitude = $_POST['new_longitude'] ?? null;
        
        // **Requires AddressModel::saveAddress implementation**
        // You MUST replace the simulation with actual AddressModel logic
        // $delivery_address_id = $addressModel->saveAddress($user_id, $new_label, $new_full_address, $new_latitude, $new_longitude);
        
        // --- SIMULATION --- Replace with actual AddressModel logic
        $delivery_address_id = 99999; 
        if (empty($new_full_address)) { 
            header('Location: index.php?action=checkout&error=address_save_failed');
            exit;
        }
        // ------------------
    }

    // --- B. Securely Fetch Items ---
    // In a real application, you must fetch the detailed item data (prices, quantity) 
    // from the database, not just rely on the hidden input.
    // Assuming $selected_items is retrieved here (with product_id, quantity, unit_price, line_total, cart_item_id)
    // For this example, we'll use a dummy/placeholder fetch:
    $selected_items = json_decode($selected_items_json, true);
    
    if (empty($selected_items)) {
        header('Location: index.php?action=cart&error=empty_cart');
        exit;
    }

    // --- C. Create Order and Update Final Details in ONE GO ---
    
    $order_data = [
        'subtotal' => $order_subtotal,
        'shipping_fee' => $shipping_fee,
        'grand_total' => $grand_total,
        'payment_method' => $payment_method,
    ];

    try {
        // Call the model method that handles the full transaction (insert order, items, payment, and final updates)
        $order_id = $orderModel->processFullOrder(
            $user_id, 
            (int)$delivery_address_id, 
            $order_data, 
            $selected_items
        );

        if (!$order_id) {
            header('Location: index.php?action=checkout&error=order_creation_failed_db');
            exit;
        }
        
        // --- Success Redirect ---
        header('Location: index.php?action=order_success&order_id=' . $order_id);
        exit;

    } catch (Exception $e) {
        // This catches high-level errors from the model
        error_log("Controller Order Processing Failed: " . $e->getMessage());
        header('Location: index.php?action=checkout&error=order_failed');
        exit;
    }

} else {
    // Direct access or missing final_checkout flag
    header('Location: index.php?action=checkout');
    exit;
}