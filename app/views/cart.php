<?php


if (!isset($user)) {
    header("Location: index.php?action=login");
    exit;
}

$initial_total = number_format($cart_total ?? 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart - Liyag Batangan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="public/assets/css/home.css">
    <link rel="stylesheet" href="public/assets/css/cart.css">
    <link rel="stylesheet" href="public/assets/css/notification.css">
    <link rel="icon" type="image/png" href="public/assets/default/icon/cart.png">
</head>
<body>

<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            LIYAG BATANGAN CART
        </div>

        <nav class="nav-icons">
             <a href="index.php?action=home" title="Home">
                <i class="bi bi-house-fill"></i>
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


            <div class="profile-dropdown">
                <button id="profileBtn" title="Profile">
                    <div class="profile-hover-wrapper">
                        <img src="<?= htmlspecialchars(
                                $userProfilePicture
                                            ? 'uploads/' . basename($userProfilePicture)
                                            : 'public/assets/default/default_profile.jpg'
                        ) ?>"
                        alt="Profile"
                        class="profile-icon">
                    </div>
                </button>

                <div class="dropdown-content" id="dropdownMenu">
                    <a href="index.php?action=account">Account Management</a>
                    <?php if (!empty($user['user_type']) && $user['user_type'] === 'Vendor'): ?>
                        <a href="index.php?action=view_vendor_store">View Store</a>
                    <?php else: ?>
                        <a href="index.php?action=create_business">Start Selling</a>
                    <?php endif; ?>

                    <form method="POST" action="index.php?action=logout">
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                </div>
            </div>
        </nav>
    </div>
</header>

<main>
    <div class="cart-container">
        <h1 class="section-title">Shopping Cart</h1>

        <?php if (!empty($cart_items)): ?>
            
            <form id="checkout-form" method="POST" action="index.php?action=checkout" style="display: none;">
                <input type="hidden" name="selected_items" id="final-selected-items-input">
                <input type="hidden" name="checkout_total" id="checkout-total-input">
            </form>
            <div class="cart-items-list">
                
                <?php 
                foreach ($cart_items as $date_key => $items_by_date): 
                    $display_date = date('F j, Y', strtotime($date_key));
                ?>
                    <h2 class="date-separator"><?= htmlspecialchars($display_date) ?></h2>

                    <?php 
                    foreach ($items_by_date as $item): 
                        $timestamp_to_use = $item['updated_at'] ?? $item['created_at'];

                        $data_attrs = [
                            'cart-item-id' => $item['cart_item_id'],
                            'product-id' => $item['product_id'],
                            'unit-price' => $item['unit_price'],
                            'quantity' => $item['quantity'],
                            'line-total' => $item['line_total'],
                            'updated-at' => $timestamp_to_use,
                        ];
                        
                        $display_time = date('F j, Y, g:i A', strtotime($timestamp_to_use));

                    ?>
                        <div class="cart-item" 
                            data-selected="false" 
                            <?php foreach ($data_attrs as $key => $value) echo "data-{$key}=\"{$value}\" "; ?>>
                            
                            <input type="checkbox" 
                                class="item-selection" 
                                data-item-id="<?= $item['cart_item_id'] ?>" 
                                id="checkbox-<?= $item['cart_item_id'] ?>" 
                                style="display: none;"
                                > <label for="checkbox-<?= $item['cart_item_id'] ?>" class="custom-checkbox-replacement" title="Select Item">
                            </label>
                            
                            <img src="<?= htmlspecialchars($item['image_url'] ?? 'uploads/products/default_product.jpg') ?>" 
                                alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                class="item-image">
                            
                            <div class="item-details">
                                <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                                <p>Unit Price: ₱<?= number_format($item['unit_price'], 2) ?></p>
                                <small class="item-date">Last Updated: <span id="date-<?= $item['cart_item_id'] ?>"><?= htmlspecialchars($display_time) ?></span></small>
                            </div>
                            
                            <div class="item-controls">
                                <div class="item-price-quantity">
                                    <span class="quantity-display">Qty: <?= htmlspecialchars($item['quantity']) ?></span>
                                    <span class="price" id="price-<?= $item['cart_item_id'] ?>">
                                        ₱<?= number_format($item['line_total'], 2) ?>
                                    </span>
                                </div>
                                
                                <div class="quantity-update-control">
                                    <button type="button" class="quantity-btn decrease-qty" data-item-id="<?= $item['cart_item_id'] ?>">-</button>
                                    <input type="number" class="item-quantity-input" value="<?= htmlspecialchars($item['quantity']) ?>" min="1" 
                                            id="qty-<?= $item['cart_item_id'] ?>" data-item-id="<?= $item['cart_item_id'] ?>" readonly>
                                    <button type="button" class="quantity-btn increase-qty" data-item-id="<?= $item['cart_item_id'] ?>">+</button>
                                    
                                    <button type="button" class="remove-item-btn" data-item-id="<?= $item['cart_item_id'] ?>">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h3 class="total-title">Selected Total: <span id="cart-selected-total">₱<?= $initial_total ?></span></h3>
                <button class="checkout-btn" id="checkout-btn">Proceed to Checkout</button>
            </div>
            
        <?php else: ?>
                <p class="empty-cart-message">Your cart is empty. Time to find some local goods!
                    
                   <a href="index.php?action=products&category_id=all" class="btn-goto-products">
                     Go to Products
                   </a>
                 </p>
            
        <?php endif; ?>

    </div>
</main>

<script src="app/scripts/home.js"></script> 
<script src="app/scripts/cart.js"></script> 
<script src="app/scripts/notification.js"></script>

</body>
</html>