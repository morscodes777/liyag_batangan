<?php
// views/reviews.php

// Ensure session is started (if it's not already handled in your index/router)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Authorization Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?action=login");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = $_SESSION['user'] ?? [];

// 2. Load Model and Fetch Data
require_once __DIR__ . '/../models/accountModels.php';
$accountModel = new AccountModel();

// Use the existing method to get the list of products
// NOTE: $productsToReview MUST include 'order_item_id' from the database join 
// for the functionality to be correct.
$productsToReview = $accountModel->getProductsToReviewByUser($user_id);
$reviewedProducts = $accountModel->getReviewedProductsByUser($user_id);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products to Review - Liyag Batangan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/assets/css/home.css"> 
    <link rel="stylesheet" href="public/assets/css/notification.css">
    <link rel="stylesheet" href="public/assets/css/reviews.css">
    <link rel="stylesheet" href="public/assets/css/track_order.css">
    <link rel="icon" type="image/png" href="public/assets/default/icon/account.png">
</head>
<body>

<a href="index.php?action=account" class="back-link floating"><i class="bi bi-arrow-left-circle-fill"></i> Back to Account</a>
<a href="index.php?action=track_orders" class="track-link floating"><i class="bi bi-journal-text icon-orders"></i> Track Order</a>

<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            PRODUCT REVIEWS
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

    <div class="header-banner">
        <h1 class="banner-title">Products Ready for Review</h1>
        <p class="banner-subtitle">Help the community by sharing your feedback on delivered items.</p>
    </div>
</header>

<main>
    <div class="review-container">
        <?php if (!empty($productsToReview)): ?>
            <ul class="review-list">
                <?php foreach ($productsToReview as $product): ?>
                    <li class="review-item">
                        <img src="<?= htmlspecialchars($product['image_url']); ?>" alt="<?= htmlspecialchars($product['product_name']); ?>">
                        <div class="product-details">
                            <h3><?= htmlspecialchars($product['product_name']); ?></h3>
                            <p>Order ID: #<?= htmlspecialchars($product['order_id']); ?></p>
                            <p>Delivered on: <?= date("F j, Y", strtotime($product['order_date'])); ?></p>
                        </div>
                        <a href="index.php?action=submit_review&product_id=<?= $product['product_id']; ?>&order_id=<?= $product['order_id']; ?>&order_item_id=<?= $product['order_item_id'] ?? 0; ?>" 
                            class="review-btn-link"
                            data-order-item-id="<?= $product['order_item_id'] ?? 0; ?>">
                            Rate The Product
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="no-products">
                <i class="bi bi-check-circle" style="font-size: 48px; color: #FFD700;"></i>
                <h2>All caught up!</h2>
                <p>You currently have no delivered products pending a review.</p>
            </div>
        <?php endif; ?>
    </div>

    <hr class="section-divider">

    <div class="reviewed-container">
        <div class="header-banner reviewed-banner">
            <h1 class="banner-title">My Past Reviews</h1>
            <p class="banner-subtitle">Products you've already rated and commented on.</p>
        </div>
        
        <?php if (!empty($reviewedProducts)): ?>
            <ul class="review-list">
                <?php foreach ($reviewedProducts as $product): ?>
                    <li class="review-item reviewed">
                        <img src="<?= htmlspecialchars($product['image_url']); ?>" alt="<?= htmlspecialchars($product['product_name']); ?>">
                        <div class="product-details">
                            <h3><?= htmlspecialchars($product['product_name']); ?></h3>
                            <p>Order ID: #<?= htmlspecialchars($product['order_id']); ?></p>
                            <p>Reviewed on: <?= date("F j, Y", strtotime($product['review_date'])); ?></p>
                            <div class="user-review-summary">
                                <p>Your Rating: 
                                    <span class="stars-static">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi <?= $i <= $product['rating'] ? 'bi-star-fill' : 'bi-star'; ?>" style="color: gold;"></i>
                                        <?php endfor; ?>
                                    </span> 
                                    (<?= htmlspecialchars($product['rating']); ?>/5)
                                </p>
                                <p class="comment-text">"<?= htmlspecialchars(substr($product['comment'], 0, 80)); ?><?= strlen($product['comment']) > 80 ? '...' : ''; ?>"</p>
                            </div>
                        </div>
                        <span class="review-status-tag">Reviewed <i class="bi bi-check-circle-fill"></i></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
                
            <div class="no-products">
                <i class="bi bi-info-circle" style="font-size: 48px; color: #A9A9A9;"></i>
                <h2>No Reviews Submitted Yet</h2>
                <p>Once you submit a review for a delivered product, it will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<div id="reviewModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-btn">&times;</button>
        
        <h2 id="modalProductName">Rate Product: </h2>
        <input type="hidden" id="modalProductId">
        <input type="hidden" id="modalOrderId">
        <input type="hidden" id="modalOrderItemId">

        <div class="review-stats">
            <div id="existingRatingSummary" class="existing-summary">
                <p>Overall Rating: <span id="avgRatingStars" class="stars"></span> <span id="avgRatingValue">(0.0)</span></p>
                <p><span id="totalReviewsCount">0</span> Reviews</p>
            </div>
            <div class="existing-comments">
                <h4>Recent Community Reviews</h4>
                <div id="commentsList">
                    <p>No reviews yet.</p>
                </div>
            </div>
        </div>
        
        <hr>

        <form id="reviewForm">
            <h3>Your Rating</h3>
            <div class="user-rating" id="userRatingStars">
                <i class="bi bi-star-fill star" data-rating="1"></i>
                <i class="bi bi-star-fill star" data-rating="2"></i>
                <i class="bi bi-star-fill star" data-rating="3"></i>
                <i class="bi bi-star-fill star" data-rating="4"></i>
                <i class="bi bi-star-fill star" data-rating="5"></i>
            </div>
            <input type="hidden" id="selectedRating" name="rating" required>

            <label for="reviewComment">Your Comment (Optional):</label>
            <textarea id="reviewComment" name="comment" rows="4" placeholder="Share your experience..."></textarea>
            
            <button type="submit" class="submit-review-btn">Submit Review</button>
            <p id="reviewMessage" class="message-status" style="display: none;"></p>
        </form>
    </div>
</div>

<script src="app/scripts/notification.js"></script>
<script src="app/scripts/home.js"></script>
<script src="app/scripts/reviews.js"></script>
</body>
</html>