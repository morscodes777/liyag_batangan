<?php
// index.php (Initial Public Homepage)

// Define placeholder variables for a public view
$query = $_GET['query'] ?? null;
$is_search_page = !empty($query);

// Use empty arrays since no user is logged in and no search is performed by default
$store_results = $store_results ?? [];
$product_results = $product_results ?? [];
$approved_stores = $approved_stores ?? []; 
$recommended_products = $recommended_products ?? []; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $is_search_page ? 'Search Results for "' . htmlspecialchars($query) . '"' : 'Welcome to Liyag Batangan' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"> 
    <link rel="stylesheet" href="public/assets/css/main_page.css">  
    <link rel="icon" type="image/png" href="public/assets/default/icon/logo.png">
    <meta name="description" content="Ever tried shopping the Batangueño way?...Discover more at Liyag Batangan!">
</head>
<body class="<?= $is_search_page ? 'search-results-active' : '' ?>">

 <header class="header-nav minimal-hero-header">
        <div class="logo">
            <a href="index.php">LIYAG BATANGAN</a>
        </div>
        <nav class="user-actions">
            <a>|</a>
            <a href="index.php?action=login" class="sign-in-link" aria-label="Sign In or Sign Up">
                 Sign In/Sign Up <i class="bi bi-arrow-right"></i>
            </a>
        </nav>
    </header>

<main class="<?= $is_search_page ? 'search-main' : '' ?>">

    
    <?php if ($is_search_page): ?>

        <h1 class="search-page-title">Results for "<span class="query-term"><?= htmlspecialchars($query) ?></span>"</h1>

       <section class="product-results">
        <h2 class="section-title"><i class="bi bi-egg-fill"></i> Products Found (<?= count($product_results) ?>)</h2>
        <div class="product-grid">
            <?php if (!empty($product_results)): ?>
                <?php foreach ($product_results as $product): ?>
                    <div class="product-card"
                        data-product='<?= htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8') ?>'>
                        <img src="<?= htmlspecialchars($product['image_url'] ?? 'uploads/products/default_product.jpg') ?>"
                            alt="<?= htmlspecialchars($product['name']) ?>"
                            class="product-image">
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
                        
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <span class="view-btn login-required-btn">Add to Cart</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-results-message">No products matched your search query.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php else: ?>
    
        <section class="hero-section">
            <div class="hero-text">
                <h1>Ever tried shopping the Batangueño way?</h1>
                <p>Discover more at Liyag Batangan!</p>
                <div class="hero-buttons">
                    <a href="index.php?action=products&category_id=all" class="shop-now-btn">Shop Now</a>
                    <a href="#why-choose" class="learn-more-btn">Learn More</a>
                </div>
            </div>
            <div class="hero-image">
                <div style="background-color: #fff; width: 300px; height: 300px; border-radius: 8px; display: flex; justify-content: center; align-items: center;">
                    <img src="uploads/default/batangas-coffee.png" alt="Batangas Coffee" style="max-width: 90%; max-height: 90%; object-fit: contain;">
                </div>
            </div>
        </section>

        <section class="categories">
            <h2 class="section-title" style="border:none; padding-left: 0;">Featured Products</h2>
            <div class="product-grid">
                <?php 
                    // REMOVE THE HARDCODED $featured_products ARRAY
                ?>
                <?php 
                    // ✅ USE THE VARIABLE PASSED FROM THE FRONT CONTROLLER
                    foreach ($recommended_products as $product): ?>
                    <div class="product-card" data-product='<?= htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8') ?>'>
                        <img src="<?= htmlspecialchars($product['image_url'] ?? 'uploads/products/default_product.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <span class="view-btn login-required-btn">Add to Cart</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>


        <section class="how-it-works">
            <h2>How It Works</h2>
            <p>Shop or sell in just a few easy steps.</p>
            <div class="steps-container">
                <div class="step-card">
                    <i class="bi bi-person-plus-fill"></i>
                    <h3>1. Create Account</h3>
                    <p>Sign up in seconds to start your shopping or open your store.</p>
                </div>
                <div class="step-card">
                    <i class="bi bi-search"></i>
                    <h3>2. Explore Products</h3>
                    <p>Browse curated collections and discover your next purchase.</p>
                </div>
                <div class="step-card">
                    <i class="bi bi-cart-check-fill"></i>
                    <h3>3. Shop & Enjoy</h3>
                    <p>Easy checkout, fast delivery, and support at every step.</p>
                </div>
            </div>
        </section>

        <section class="why-choose" id="why-choose">
            <h2>Why Choose <span style="color: var(--primary-color);">Liyag Batangan</span>?</h2>
            <p style="margin-bottom: 25px; color: #666;">A new way to shop and sell, with trust, speed, and purpose.</p>
            <div class="why-choose-grid">
                <div class="reason-card">
                    <h3>Batangas to the World</h3>
                    <p>The platform brings Batangueño finest goods to a wider market, making local products available anywhere.</p>
                </div>
                <div class="reason-card">
                    <h3>Promote Local Pride</h3>
                    <p>The name highlights the beauty ("Liyag") and culture of Batangas, encouraging the purchasing of local products.</p>
                </div>
                <div class="reason-card">
                    <h3>Support Small Businesses</h3>
                    <p>It aims to give Batangueño MSMEs a digital space to grow and reach more customers.</p>
                </div>
            </div>
        </section>

    <?php endif; ?>
</main>

<footer class="footer">
    <div class="footer-nav">
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Contact</a>
    </div>
    <div class="social-icons">
        <a href="https://www.facebook.com/people/Liyag-Batangan/61583172300285/"><i class="bi bi-facebook"></i></a>
        <a href="https://www.instagram.com/liyag.batangan/"><i class="bi bi-instagram"></i></a>
    </div>
    <p class="copyright">&copy; <?= date('Y') ?> Liyag Batangan. All rights reserved.</p>
</footer>

<div id="loginRequiredModal" class="modal product-modal-v2">
    <div class="modal-content-v2 modal-cta">
        <button class="close-button" title="Close Modal" onclick="document.getElementById('loginRequiredModal').classList.remove('open'); setTimeout(() => {document.getElementById('loginRequiredModal').style.display = 'none';}, 300);"></button>
        <div class="modal-body-v2" style="text-align: center; max-width: 400px; margin: 0 auto;">
            <i class="bi bi-cart-x-fill" style="font-size: 4rem; color: #FFA500; margin-bottom: 15px;"></i>
            <h3 class="product-name-modal">Cart Access Restricted</h3>
            <p class="product-description-modal">
                You must <strong>login or register</strong> an account to add items to your cart and place an order.
            </p>
            <div class="modal-purchase-footer" style="padding-top: 20px;">
                <a href="index.php?action=login" class="add-to-cart-btn-v2" style="background-color: #5cb85c; margin-right: 10px;">
                    <i class="bi bi-box-arrow-in-right"></i> Login Now
                </a>
                <a href="index.php?action=register" class="add-to-cart-btn-v2" style="background-color: #007bff;">
                    <i class="bi bi-person-add"></i> Register
                </a>
            </div>
        </div>
    </div>
</div>

<script src="app/scripts/home.js"></script>
<script src="app/scripts/main_page.js"></script>
</body>
</html>
