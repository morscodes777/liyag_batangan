<?php
// views/home.php

// Variables expected from HomeController (or SearchController logic embedded):
// $displayUserName
// $userProfilePicture
// $approved_stores        // Used for Home Map/Section
// $recommended_products   // Used for Home Recommended Section

// --- Search Variables (Placeholder: Your controller must populate these if a query exists) ---
// We check for the query parameter in the URL.
$query = $_GET['query'] ?? null;
$is_search_page = !empty($query); // This variable is now kept primarily for the title tag and body class, 
                                 // but the main content will no longer switch based on it.

// If this page is loaded as a search results page, your controller needs to populate
// $store_results and $product_results based on the $query.
// For demonstration, we use empty arrays if they aren't set.
$store_results = $store_results ?? [];
$product_results = $product_results ?? [];
// ------------------------------------------------------------------------------------------

if (!isset($user)) {
    // Ideally should be handled by controller, but fallback here:
    header("Location: index.php?action=login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $is_search_page ? 'Search Results for "' . htmlspecialchars($query) . '"' : 'Liyag Batangan Home' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="public/assets/css/home.css">
    <link rel="stylesheet" href="public/assets/css/responsive-home.css">
    <link rel="stylesheet" href="public/assets/css/notification.css">
    <link rel="stylesheet" href="public/assets/css/search.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="icon" type="image/png" href="public/assets/default/icon/home.png">
</head>
<body class="<?= $is_search_page ? 'search-results-active' : '' ?>">

<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            HELLO, <?= strtoupper(htmlspecialchars($displayUserName)) ?>
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
                    <a class="drop" href="index.php?action=account">Account Management</a>
                    <?php if (!empty($user['user_type']) && $user['user_type'] === 'Vendor'): ?>
                        <a class="drop" href="index.php?action=view_vendor_store">View Store</a>
                    <?php else: ?>
                        <a class="drop" href="index.php?action=create_business">Start Selling</a>
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
                <h4><i class="bi bi-shop"></i> Stores</h4>
                <div class="results-list" id="storeResults">
                    <p class="no-results">Start typing to see results...</p>
                </div>
            </div>

            <div class="search-section">
                <h4><i class="bi bi-egg-fill"></i> Products</h4>
                <div class="results-list" id="productResults">
                    <p class="no-results">Start typing to see results...</p>
                </div>
            </div>
        </div>
    </div>
</header>

<main>
    
        <section class="categories">
            <div class="category-grid">
                <a href="index.php?action=products&category_id=all" class="category-card">
                    <i class="bi bi-grid-fill"></i><span>ALL</span>
                </a>
                <a href="index.php?action=products&category_id=1" class="category-card">
                    <i class="bi bi-egg-fill"></i><span>FOOD</span>
                </a>
                <a href="index.php?action=products&category_id=2" class="category-card">
                    <i class="bi bi-cup-fill"></i><span>BEVERAGES</span>
                </a>
                <a href="index.php?action=products&category_id=3" class="category-card">
                    <i class="bi bi-gift-fill"></i><span>SOUVENIRS</span>
                </a>
            </div>
        </section>

        <section class="checkout-stores">
            <h2 class="section-title">
                Checkout Stores
                <a href="index.php?action=all_stores" class="see-all">View Full Map</a>
            </h2>
            
            <div class="map-container">
                <div id="store-map"></div>
            </div>

            <div class="store-grid-single" data-stores='<?= htmlspecialchars(json_encode($approved_stores), ENT_QUOTES, 'UTF-8') ?>'>
                <?php if (!empty($approved_stores)): ?>
                    <?php foreach ($approved_stores as $store): ?>
                        <div class="store-card-large">
                            <img src="<?= htmlspecialchars($store['logo_url'] ?? 'public/assets/default/default_store_logo.jpg') ?>"
                                alt="<?= htmlspecialchars($store['business_name']) ?>">
                            <div class="store-info">
                                <h3><i class="bi bi-shop"></i> <?= htmlspecialchars($store['business_name']) ?></h3>
                                <p class="store-address"><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($store['business_address']) ?></p>

                                <div class="store-rating-info">
                                    <span class="store-rating">
                                        <i class="bi bi-star-fill"></i>
                                        <?= number_format($store['average_rating'] ?? 0, 1) ?> 
                                    </span>
                                    <span class="review-count">
                                        (<?= number_format($store['total_reviews'] ?? 0) ?> Reviews)
                                    </span>
                                </div>

                                <a href="index.php?action=view_store&vendor_id=<?= urlencode($store['vendor_id']) ?>"
                                class="view-store-btn">View Store</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <section class="recommended-for-you">
            <h2 class="section-title">Recommended For You</h2>
            <div class="product-grid">
                <?php if (!empty($recommended_products)): ?>
                    <?php foreach ($recommended_products as $product): ?>
                        <div class="product-card"
                              data-product='<?= htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8') ?>'>
                            <img src="<?= htmlspecialchars($product['image_url'] ?? 'uploads/products/default_product.jpg') ?>"
                                  alt="<?= htmlspecialchars($product['name']) ?>"
                                  class="product-image">
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
                    <p>No products are currently available.</p>
                <?php endif; ?>
            </div>
        </section>
</main>

<footer class="footer">
    <div class="social-icons">
        <a href="https://www.facebook.com/people/Liyag-Batangan/61583172300285/"><i class="bi bi-facebook"></i></a>
        <a href="https://www.instagram.com/liyag.batangan"><i class="bi bi-instagram"></i></a>
    </div>
    <p class="copyright">&copy; <?= date('Y') ?> Liyag Batangan. All rights reserved.</p>
</footer>

<div id="logoutModal">
    <div class="modal-content">
        <h3>Are you sure you want to logout?</h3>
        <div class="modal-buttons">
            <button class="confirm" id="confirmLogoutBtn">Yes</button>
            <button class="cancel" id="cancelLogoutBtn">Cancel</button>
        </div>
    </div>
</div>

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
                
                <div id="modal-product-rating-display" class="product-rating-display-modal"></div>
                
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

<div id="cartSuccessLottieModal" class="lottie-modal">
    <div class="lottie-modal-content">
        <lottie-player 
            id="lottieCartSuccess" 
            src="public/assets/lotties/check.json" 
            background="transparent"  
            speed="1"  
            style="width: 100px; height: 100px;"  
            loop="false"
        ></lottie-player>
        <p class="success-message">Added to Cart!</p>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<script src="app/scripts/search.js"></script>
<script src="app/scripts/home.js"></script>
<script src="app/scripts/notification.js"></script>

</body>
</html>
                    