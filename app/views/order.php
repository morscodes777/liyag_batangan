<?php
$shipping_fee = 50.00;
$grand_total = $checkout_total + $shipping_fee;

$has_addresses = !empty($user_addresses);
$address_options = '';

if ($has_addresses) {
    foreach ($user_addresses as $address) {
        $display_name = htmlspecialchars($address['label'] ?? 'Saved Address');
        $address_text = htmlspecialchars($address['full_address']);
        $is_default = (isset($address['is_default']) && $address['is_default'] == 1);
        $selected_attr = $is_default ? 'selected' : '';
        
        $address_options .= "<option value=\"{$address['address_id']}\" data-type=\"existing\" {$selected_attr}>";
        $address_options .= "{$display_name} - {$address_text}";
        $address_options .= $is_default ? ' (Default)' : '';
        $address_options .= "</option>";
    }
}

$address_button_disabled = !$has_addresses;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Liyag Batangan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/assets/css/checkouts.css"> 
    <link rel="stylesheet" href="public/assets/css/notification.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="public/assets/default/icon/cart.png">
</head>
<body>

<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            CHECKOUT PROCESS
        </div>

        <nav class="nav-icons">
            <a href="index.php?action=home" title="Home">
                <i class="bi bi-house-fill"></i>
            </a>

            <a href="index.php?action=cart" title="Cart">
                <i class="bi bi-cart-fill"></i>
            </a>

            <div class="notification-dropdown" data-user-id="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>">
                <button id="notificationBtn" title="Notifications">
                    <div class="notification-hover-wrapper">
                        <i class="bi bi-bell-fill"></i>
                        <span id="notificationBadge" class="notification-badge" style="display:none;"></span>
                    </div>
                </button>

                <div class="notification-modal" id="notificationModal">
                    <h4>Notifications</h4>
                    <div class="notification-list" id="notificationList">
                        <p class="no-notifications">Loading notifications...</p>
                    </div>
                </div>
            </div>
            </nav>
    </div>

    <div class="header-banner checkout-title-area">
    <h1 class="banner-title">Finalizing Your Order</h1>
    <p class="banner-subtitle">Complete your purchase with Liyag Batangan.</p>
</div>
</header>

<main class="checkout-main">
    <form id="checkout-form" method="POST" action="index.php?action=place_order">
        <input type="hidden" name="order_subtotal" value="<?= number_format($checkout_total, 2, '.', '') ?>">
        <input type="hidden" name="shipping_fee" value="<?= number_format($shipping_fee, 2, '.', '') ?>">
        <input type="hidden" name="order_total_final" id="order-total-input" value="<?= number_format($grand_total, 2, '.', '') ?>">
        <input type="hidden" name="final_checkout" value="1">
        <input type="hidden" name="final_selected_items_detailed" id="final-selected-items-detailed-input" 
            value='<?= htmlspecialchars(json_encode(array_map(function($item) {
                // Ensure all these fields exist in your $selected_items array
                return [
                    'cart_item_id' => $item['cart_item_id'], 
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                    'commission_rate' => $item['commission_rate'] ?? 10.00,
                ];
            }, $selected_items)), ENT_QUOTES) ?>'>

        <div class="checkout-steps-container">

            <section class="step-card active" data-step="1">
                <h2><i class="bi bi-geo-alt-fill"></i> 1. Delivery Details</h2>
                <div class="step-content">
                    <p class="customer-info">Delivering to: <strong><?= htmlspecialchars($user_info['name'] ?? 'Customer') ?></strong> (<?= htmlspecialchars($user_info['phone_number'] ?? '') ?>)</p>
                    
                    <label for="delivery_address_id">Choose Delivery Address:</label>
                    <div class="input-group" id="address-selection-group">
                        <select name="delivery_address_id" id="delivery_address_id" required>
                            <?php if ($has_addresses): ?>
                                <?= $address_options ?>
                            <?php else: ?>
                                <option value="" disabled selected>No saved addresses. Please add one below.</option>
                            <?php endif; ?>
                            <option value="new_address">-- Add New Address --</option>
                        </select>
                    </div>

                    <div class="address-form-container" id="new-address-form-container" style="display: none;">
                        <h3><i class="bi bi-pin-map-fill"></i> New Address Details</h3>
                        <label for="new_address_label">Address Nickname (e.g., Home, Office):</label>
                        <input type="text" name="new_address_label" id="new_address_label" placeholder="e.g., My Apartment" maxlength="100">

                        <label for="new_full_address">Full Delivery Address (Click to select on map):</label>
                        <textarea name="new_full_address" id="new_full_address" rows="3" placeholder="Select your location on the map..." readonly></textarea>
                        
                        <input type="hidden" name="new_latitude" id="new_latitude">
                        <input type="hidden" name="new_longitude" id="new_longitude">
                    </div>
                    
                    <button type="button" class="next-step-btn" data-next="2" id="confirm-address-btn" <?= $address_button_disabled ? 'disabled' : '' ?>>
                        Confirm Address & Continue <i class="bi bi-chevron-right"></i>
                    </button>
                    <small class="address-warning-text">Shipping Fee: ₱<?= number_format($shipping_fee, 2) ?> (Fixed rate)</small>
                </div>
            </section>
            
            <section class="step-card" data-step="2">
                <h2><i class="bi bi-wallet-fill"></i> 2. Payment Method</h2>
                <div class="step-content">
                    <div class="payment-option">
                        <input type="radio" id="payment_cod" name="payment_method" value="COD" required>
                        <label for="payment_cod"><i class="bi bi-cash-stack"></i> Cash on Delivery (COD)</label>
                        <p class="method-details">Pay with cash when your order arrives. Status: **Pending** vendor confirmation.</p>
                    </div>
                    
                    <div class="payment-option">
                        <input type="radio" id="payment_gcash" name="payment_method" value="GCash" required>
                        <label for="payment_gcash"><i class="bi bi-phone-fill"></i> GCash (Online)</label>
                        <p class="method-details">Pay instantly using your GCash account. Status: **Approved** upon successful payment.</p>
                    </div>
                    
                    <div class="navigation-buttons">
                        <button type="button" class="prev-step-btn" data-prev="1"><i class="bi bi-chevron-left"></i> Previous Step</button>
                        <button type="button" class="next-step-btn" data-next="3" disabled>
                            Confirm Payment & Review <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </section>

            <section class="step-card" data-step="3">
                <h2><i class="bi bi-check-circle-fill"></i> 3. Review & Place Order</h2>
                <div class="step-content">
                    <h3><i class="bi bi-credit-card-2-front-fill"></i> Payment Method</h3>
                    <div class="review-payment-method" id="payment-method-review">
                        <p class="no-selection-msg">Please select a payment method in Step 2.</p>
                    </div>
                    
                    <h3 class="mt-4">Items to Purchase (<?= count($selected_items) ?>)</h3>
                    <div class="review-items-list">
                        <?php foreach ($selected_items as $item): ?>
                            <div class="review-item">
                                <img src="<?= htmlspecialchars($item['image_url'] ?? 'uploads/products/default_product.jpg') ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                <div class="item-info">
                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                    <span>x<?= htmlspecialchars($item['quantity']) ?></span>
                                </div>
                                <span class="item-price">₱<?= number_format($item['line_total'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="final-summary">
                        <p>Subtotal: <span>₱<?= number_format($checkout_total, 2) ?></span></p>
                        <p>Shipping Fee: <span>₱<?= number_format($shipping_fee, 2) ?></span></p>
                        <p class="grand-total-row">Grand Total: <span id="grand-total-display">₱<?= number_format($grand_total, 2) ?></span></p>
                    </div>
                    
                    <div class="navigation-buttons">
                        <button type="button" class="prev-step-btn" data-prev="2"><i class="bi bi-chevron-left"></i> Previous Step</button>
                        <button type="submit" class="btn btn-primary" id="placeOrderBtn" name="final_checkout" form="checkout-form">
                            <i class="bi bi-bag-check-fill"></i> Place Order Now
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </form>
</main>
<div id="mapModal" class="modal" style="display: none;">
    <div class="modal-content map-modal-content">
        <h3 class="modal-title">Select Your Location</h3>
        <p id="address-display" class="current-address-display">Fetching address...</p>

        <div id="map"></div>
        <div id="center-marker" class="center-marker"></div>

        <div class="map-modal-buttons">
            <button 
                onclick="selectLocation()" 
                id="confirmLocationBtn" 
                class="map-btn select-btn"
                disabled>
                Determining Address...
            </button>
            <button id="closeMapModal" class="map-btn cancel-btn">Cancel</button>
        </div>
    </div>
</div>
<script src="app/scripts/orders.js"></script>
<script src="app/scripts/notification.js"></script>

</body>
</html>