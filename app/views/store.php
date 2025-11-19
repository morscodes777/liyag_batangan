<?php
// views/store.php

// The controller is responsible for defining:
// $user, $store, $userProfilePicture, $product_results, $average_rating, $total_reviews, $categories

// Ensure the necessary variables are set before rendering the page.
if (!isset($user) || !isset($store)) {
    // This check is redundant if the controller handles it, but kept for safety.
    header("Location: index.php?action=login");
    exit;
}

// Customer-facing suggestion logic
$total_products = count($product_results ?? []);
$product_suggestion = $total_products < 10 ? 
    "This shop has a small but curated selection. Check back soon for new additions!" : 
    "A wide variety of products! Explore the different categories for more choices.";

/**
 * Renders star icons based on a rating value.
 * NOTE: This function should ideally be in a shared helper file, but is included here for completeness.
 * @param float $rating The rating value (e.g., 4.2).
 * @return string HTML span containing star icons.
 */
function renderRatingStars($rating) {
    $html = '<div class="star-display-wrapper" aria-label="Rated ' . number_format($rating, 1) . ' out of 5">';
    $rounded_rating = round($rating * 2) / 2; // Round to nearest 0.5
    for ($i = 1; $i <= 5; $i++) {
        $icon_class = '';
        if ($i <= $rounded_rating) {
            $icon_class = 'bi-star-fill'; // Full star
        } elseif ($i - 0.5 == $rounded_rating) {
            $icon_class = 'bi-star-half'; // Half star
        } else {
            $icon_class = 'bi-star'; // Empty star
        }
        $html .= '<i class="star-icon ' . $icon_class . '"></i>';
    }
    $html .= '</div>';
    return $html;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($store['business_name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/assets/css/home.css">
    <link rel="stylesheet" href="public/assets/css/responsive-home.css">
    <link rel="stylesheet" href="public/assets/css/responsive-store.css">
    <link rel="stylesheet" href="public/assets/css/store.css">
    <link rel="stylesheet" href="public/assets/css/stores.css">
    <link rel="stylesheet" href="public/assets/css/search.css">
    <link rel="stylesheet" href="public/assets/css/notification.css">
    
    
    <style>
        .text-yellow { color: #FFD700; }
        .text-muted { color: #e9ecef; }
        .star-rating { white-space: nowrap; }
        .star-rating i { font-size: 1em; }
    </style>
    <link rel="icon" type="image/png" href="public/assets/default/icon/shop.png">
    
</head>
<body>

<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            <?= htmlspecialchars($store['business_name']); ?>
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
                        <img src="<?= htmlspecialchars($userProfilePicture ? 'uploads/' . basename($userProfilePicture) : 'public/assets/default/default_profile.jpg') ?>" 
                             alt="Profile" 
                             class="profile-icon">
                    </div>
                </button>
                <div class="dropdown-content" id="dropdownMenu">
                    <a href="index.php?action=account">Account Management</a>
                    <?php if (isset($user['user_type']) && ($user['user_type'] === 'Vendor' || $user['user_type'] === 'vendor')): ?>
                        <a href="index.php?action=view_vendor_store">View My Store</a>
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
        <input type="text" id="searchInput" placeholder="Search food, stores, or items..." class="search-input">
        
        <div class="search-results-dropdown" id="searchResultsDropdown">
            <div class="search-results-section">
                <h4>Stores</h4>
                <div class="store-results" id="storeResults"></div>
            </div>
            <div class="search-results-section">
                <h4>Products</h4>
                <div class="product-results" id="productResults"></div>
            </div>
            <a href="#" id="viewAllSearch" class="view-all-search" style="display: none;">View All Results</a>
        </div>
    </div>
</header>

<div class="store-header-image-container">
        <img src="<?= htmlspecialchars($store['logo_url'] ?? 'public/assets/default/default_business_picture.jpg') ?>" 
             alt="<?= htmlspecialchars($store['business_name']) ?>" 
             class="business-picture">
        <div class="store-header-fade-overlay"></div>
        <div class="store-header-content">
            <h2><?= htmlspecialchars($store['business_name']); ?></h2>
            <p><?= htmlspecialchars($store['business_address']); ?></p>
        </div>
    </div>

<main class="store-page-main" data-vendor-id="<?= htmlspecialchars($store['vendor_id']); ?>" data-vendor-user-id="<?= htmlspecialchars($store['user_id'] ?? 0); ?>">
    

    <section class="vendor-stats-container">
        
        <div class="stats-card ratings-card">
            <div class="stats-icon-wrapper">
                <i class="bi bi-star-fill icon-ratings"></i>
            </div>
            <div class="stats-info">
                <p class="stats-label">Average Rating</p>
                <p class="stats-value"><?= renderRatingStars($average_rating); ?></p>
                <p class="stats-subtext"><?= number_format($total_reviews); ?> reviews (<?= number_format($average_rating, 1); ?>/5.0)</p>
            </div>
        </div>

        
        <div class="stats-card products-stats-card">
            <div class="stats-icon-wrapper">
                <i class="bi bi-shop icon-products"></i>
            </div>
            <div class="stats-info">
                <p class="stats-label">Total Products</p>
                <p class="stats-value"><?= number_format($total_products); ?></p>
                <p class="stats-subtext"><?= htmlspecialchars($product_suggestion); ?></p>
            </div>
        </div>
        
    </section>
    
    <section class="store-products-section">
        <div class="category-filter-container">
            <div class="category-filter" id="category-filter">
                <button class="filter-btn active" data-category-id="all">All</button>
                <?php foreach ($categories as $category): ?>
                    <button class="filter-btn" data-category-id="<?= htmlspecialchars($category['category_id']); ?>">
                        <?= htmlspecialchars($category['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="filter-track" id="filter-track"></div>
        </div>
        
        <div class="product-grid">
            <?php if (!empty($product_results)): ?>
                <?php foreach ($product_results as $product): ?>
                    
                    <?php 
                        // Use the rating and review count loaded by the model
                        $product_rating = $product['average_rating'] ?? 0.0;
                        // Injecting store details into product data for the modal's use of home.js
                        $product_data_for_js = array_merge($product, [
                            'business_name' => $store['business_name'],
                            'business_address' => $store['business_address'],
                            'vendor_id' => $store['vendor_id']
                        ]);
                    ?>
                    
                    <div class="product-card"
                        data-category-id="<?= htmlspecialchars($product['category_id']); ?>"
                        data-product='<?= htmlspecialchars(json_encode($product_data_for_js), ENT_QUOTES, 'UTF-8') ?>'>
                        
                        <img src="<?= htmlspecialchars($product['image_url'] ?? 'uploads/products/default_product.jpg') ?>"
                            alt="<?= htmlspecialchars($product['name']) ?>"
                            class="product-image">
                        
                        <div class="product-info">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            
                            <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
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
                            </div>
                        
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        
                        <span class="view1-btn">Add to Cart</span> 
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-results-message">No products found for this store.</p>
            <?php endif; ?>
        </div>

        <div id="productModal" class="modal product-modal-v2">
            <div class="modal-content-v2">
                <button class="close-button" id="closeProductModalBtn" title="Close Modal"></button>
                <div class="modal-body-v2">
                    
                    <div class="modal-image-container">
                        <img id="modal-product-image" src="" alt="Product Image" class="product-image-large">
                    </div>
                    
                    <div class="modal-details-container-v2">
                        <h3 id="modal-product-name" class="product-name-modal"></h3>
                        
                        <div class="modal-store-info-wrapper">
                            <a href="index.php?action=view_store&vendor_id=<?= htmlspecialchars($store['vendor_id']); ?>" id="modal-store-link" class="store-link-modal">
                                <i class="bi bi-shop"></i> 
                                <span id="modal-product-store"><?= htmlspecialchars($store['business_name']); ?></span>
                            </a>
                            <span id="modal-store-address" class="store-address-modal">
                                <i class="bi bi-geo-alt-fill"></i> 
                                <?= htmlspecialchars($store['business_address']); ?>
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
    </section>
    
      <button class="chat-toggle-btn" id="chatToggleBtn" title="Chat with Store">
                <i class="bi bi-chat-dots-fill"></i>
            </button>

  


    <div id="chatModal" class="chat-modal">
        <div class="chat-header">
            <span id="chatHeaderTitle">Chat with <?= htmlspecialchars($store['business_name']); ?></span>
            <button class="close-chat-btn" id="closeChatBtn" style="background: none; border: none; color: black; font-size: 1.2rem; cursor: pointer;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <p style="text-align: center; color: #6c757d;">Start a new conversation.</p>
            </div>
        <div class="chat-input-area">
            <input type="text" id="chatMessageInput" placeholder="Type your message...">
            <button id="sendChatMessageBtn"><i class="bi bi-send-fill"></i></button>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="social-icons">
        <a href="https://www.facebook.com/people/Liyag-Batangan/61583172300285/"><i class="bi bi-facebook"></i></a>
        <a href="https://www.instagram.com/liyag.batangan/"><i class="bi bi-instagram"></i></a>
    </div>
    <p class="copyright">&copy; <?= date('Y') ?> Liyag Batangan. All rights reserved.</p>
</footer>


<script src="app/scripts/home.js"></script>
<script src="app/scripts/notification.js"></script>
<script src="app/scripts/stores.js"></script> 
<script src="app/scripts/search_products.js"></script>


</body>
</html>