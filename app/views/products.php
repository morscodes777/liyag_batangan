<?php
// products.php

// The variables below are now expected to be passed from index.php
// $user, $displayUserName, $userProfilePicture
// $categoryId
// $products
$query = $_GET['query'] ?? null;
$is_search_page = !empty($query); // This variable is now kept primarily for the title tag and body class, 
                                 // but the main content will no longer switch based on it.

// If this page is loaded as a search results page, your controller needs to populate
// $store_results and $product_results based on the $query.
// For demonstration, we use empty arrays if they aren't set.
$store_results = $store_results ?? [];
$product_results = $product_results ?? [];
// -----------------------------------------------
// Fallback to prevent errors if variables are not set
$products = $products ?? [];
$categoryId = $categoryId ?? 'all';

if (!isset($user)) {
    // This should ideally be handled by the controller, but as a fallback:
    header("Location: index.php?action=login");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/assets/css/home.css">
     <link rel="stylesheet" href="public/assets/css/responsive-product.css">
    <link rel="stylesheet" href="public/assets/css/notification.css">
    <link rel="stylesheet" href="public/assets/css/search.css">
    <link rel="icon" type="image/png" href="public/assets/default/icon/products.png">
</head>
<body>

<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            All Products
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

            <div class="profile-dropdown">
                <button id="profileBtn" title="Profile">
                    <div class="profile-hover-wrapper">
                        <img src="<?php echo $userProfilePicture ? 'uploads/' . basename($userProfilePicture) : 'public/assets/default/default_profile.jpg'; ?>"
                             alt="Profile" class="profile-icon">
                    </div>
                </button>
                <div class="dropdown-content" id="dropdownMenu">
                    <a href="index.php?action=account">Account Management</a>
                    <?php if (isset($user['user_type']) && $user['user_type'] === 'Vendor'): ?>
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
<div class="search-wrapper">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Search food, stores, or items..." class="search-input" value="<?= $is_search_page ? htmlspecialchars($query) : '' ?>">
        
        <div id="searchResultsDropdown" class="search-results-dropdown">

            <div class="search-section">
                <h4><i class="bi bi-egg-fill"></i> Products</h4>
                <div class="results-list" id="productResults">
                    <p class="no-results">Start typing to see results...</p>
                </div>
            </div>
        </div>
</header>

<main>
    <section class="categories">
        <div class="category-grid">
            <a href="index.php?action=products&category_id=all" class="category-card">
                <i class="bi bi-grid-fill"></i>
                <span>ALL</span>
            </a>
            <a href="index.php?action=products&category_id=1" class="category-card">
                <i class="bi bi-egg-fill"></i>
                <span>FOOD</span>
            </a>
            <a href="index.php?action=products&category_id=2" class="category-card">
                <i class="bi bi-cup-fill"></i>
                <span>BEVERAGES</span>
            </a>
            <a href="index.php?action=products&category_id=3" class="category-card">
                <i class="bi bi-gift-fill"></i>
                <span>SOUVENIRS</span>
            </a>
        </div>
    </section>

    <section class="products-by-category">
        <h2 class="section-title">
            <?php
            // Display a dynamic title based on the category
            $title = 'All Products';
            if ($categoryId == 1) $title = 'Food Products';
            if ($categoryId == 2) $title = 'Beverage Products';
            if ($categoryId == 3) $title = 'Souvenir Products';
            echo $title;
            ?>
        </h2>
        <div class="product-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-product='<?= htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8') ?>'>
                        <img src="<?= htmlspecialchars($product['image_url'] ?? 'public/assets/default/default_product.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <?php if (!empty($product['business_name']) && !empty($product['business_address'])): ?>
                            <div class="product-store-info">
                                <span class="store-name">
                                    <i class="bi bi-shop"></i> 
                                    <?= htmlspecialchars($product['business_name']) ?>
                                </span>
                                <span class="store-address">
                                    <i class="bi bi-geo-alt-fill"></i> 
                                    <?= htmlspecialchars($product['business_address']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                         <?php 
                            $avg_rating = number_format($product['average_rating'] ?? 0, 1);
                            $total_reviews = $product['total_reviews'] ?? 0;
                        ?>
                        <div class="product-rating-info">
                            <span class="product-rating">
                                <i class="bi bi-star-fill"></i>
                                <?= $avg_rating ?> 
                            </span>
                            <span class="review-count">
                                (<?= number_format($total_reviews) ?> Reviews)
                            </span>
                        </div>
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <span class="view-btn">Add to Cart</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products are currently available in this category.</p>
            <?php endif; ?>
        </div>
    </section>
</main>


<div id="productModal" class="modal product-modal-v2">
    <div class="modal-content-v2">
        <button class="close-button" title="Close Modal"></button>
        <div class="modal-body-v2">
            
            <div class="modal-image-container">
                <img id="modal-product-image" src="" alt="Product Image" class="product-image-large">
            </div>
            
            <div class="modal-details-container-v2">
                <h3 id="modal-product-name" class="product-name-modal"></h3>
                
                <div class="modal-store-info-wrapper">
                    <a href="#" id="modal-store-link" class="store-link-modal">
                        <i class="bi bi-shop"></i> 
                        <span id="modal-product-store"></span>
                    </a>
                    <span id="modal-store-address" class="store-address-modal">
                        <i class="bi bi-geo-alt-fill"></i> 
                    </span>
                </div>
                
                <div id="modal-product-rating-display" class="product-rating-display-modal">
                    </div>
                
                <p id="modal-product-description" class="product-description-modal"></p>

                <div class="modal-purchase-footer">
                    <span id="modal-product-price" class="price-modal"></span>
                    
                    <form id="modal-add-to-cart-form" action="index.php?action=add_to_cart" method="POST" onsubmit="return false;">
                        <input type="hidden" name="product_id" id="modal-product-id">
                        <div class="quantity-control-v2">
                            <button type="button" class="quantity-btn" id="decrease-quantity"><i class="bi bi-dash"></i></button>
                            <input type="number" name="quantity" id="modal-product-quantity" value="1" min="1" readonly>
                            <button type="button" class="quantity-btn" id="increase-quantity"><i class="bi bi-plus"></i></button>
                        </div>
                        <button type="submit" class="add-to-cart-btn-v2">
                            <i class="bi bi-cart-plus-fill"></i> Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="app/scripts/home.js"></script>
<script src="app/scripts/notification.js"></script>
<script src="app/scripts/search_products.js"></script>


</body>
</html>