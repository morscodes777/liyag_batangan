<?php
// views/track_order.php

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php?action=login");
    exit;
}

$user_id = $_SESSION['user_id'];
require_once __DIR__ . '/../models/orderModels.php';
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';

$database = new Database();
$pdo = $database->connect();

$orderModel = new OrderModel($pdo);

// 1. FETCH ALL ORDER COUNTS
$statusCounts = $orderModel->countOrdersByStatus($user_id);

// Set the default status to 'Pending' if no status is in the URL.
$currentFilterStatus = $_GET['status'] ?? 'Pending'; 

// Fetch filtered orders
$orders = $orderModel->getOrdersWithLocationsByUserId($user_id, $currentFilterStatus);

$statusSteps = ['Pending', 'Approved', 'Shipped', 'Out for Delivery', 'Delivered'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Orders - Liyag Batangan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/assets/css/track_order.css"> 
    <link rel="stylesheet" href="public/assets/css/home.css">
    <link rel="stylesheet" href="public/assets/css/notification.css">
    <link rel="stylesheet" href="https://cdn.rawgit.com/tomickek/leaflet-ant-path/master/dist/leaflet-ant-path.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" /> 
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <link rel="icon" type="image/png" href="public/assets/default/icon/tracking.png">
    
</head>
<body>


<a href="index.php?action=account" class="back-link floating"><i class="bi bi-arrow-left-circle-fill"></i> Back to Account</a>
<a href="index.php?action=reviews" class="review-link floating"><i class="bi bi-star-fill"></i> Reviews</a>

<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            ORDER TRACKING
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

</header>

<main class="order-tracker-main">
<section class="categories status-filter-section">
    <div class="category-grid1">
        <?php
        // Define all status options
        $filterOptions = [
            'Pending' => ['label' => 'PENDING', 'icon' => 'bi-hourglass-split'],
            'Approved' => ['label' => 'APPROVED', 'icon' => 'bi-hand-thumbs-up-fill'],
            'Shipped' => ['label' => 'SHIPPED', 'icon' => 'bi-box-seam-fill'],
            'Out for Delivery' => ['label' => 'OUT FOR DELIVERY', 'icon' => 'bi-truck'],
            'Delivered' => ['label' => 'DELIVERED', 'icon' => 'bi-check-circle-fill'],
        ];
        
        // Ensure $currentFilterStatus is available (it's defined at the top of the file)
        // Ensure $statusCounts is available (if you implemented the counting feature)
        
        // Generate the status filter cards
        foreach ($filterOptions as $statusValue => $details) {
            
            // Get the count, defaulting to 0 if the status has no orders (if counting is implemented)
            $count = $statusCounts[$statusValue] ?? 0; // Keep this if you implemented the counting model function
            // $count = 0; // Use this if you HAVEN'T implemented the counting model function yet

            $isActiveFilter = ($statusValue == $currentFilterStatus) ? 'active-filter' : '';
            $urlStatus = urlencode($statusValue);
            
            echo "
            <a href=\"index.php?action=track_orders&status={$urlStatus}\" class=\"category-card1 {$isActiveFilter}\">
                <i class=\"bi {$details['icon']}\"></i>
                <span class=\"status-label\">{$details['label']}</span>
                <span class=\"order-count\">({$count})</span>
            </a>";
            
        }
        ?>
    </div>
</section>
    <div class="order-list-container">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): 
                $currentStatusIndex = getStatusIndex($order['order_status'], $statusSteps);
                
                // Fetch coordinates and ensure safe defaults
                $deliveryLat = $order['delivery_lat'] ?? '';
                $deliveryLng = $order['delivery_lng'] ?? '';
                $vendorLat = $order['vendor_lat'] ?? '';
                $vendorLng = $order['vendor_lng'] ?? '';
                $vendorName = htmlspecialchars($order['vendor_name'] ?? 'Store');
                $orderId = htmlspecialchars($order['order_id']);
                
                // Check if both sets of coordinates are available (crude check)
                $hasCoords = (is_numeric($deliveryLat) && is_numeric($vendorLat));
                
                // Access order items 
                $orderItems = $order['order_items'] ?? [['product_name' => 'Product Details Unavailable', 'quantity' => 1]];
            ?>
                <div class="order-card">
                    <div class="order-summary">
                        <span class="order-id">#<?= $orderId; ?></span>
                        <span class="order-date">Date: <?= date("M d, Y", strtotime($order['order_date'])); ?></span>
                        <span class="order-total">Total: ₱<?= number_format($order['order_total'], 2); ?></span>
                        <span class="order-status-badge status-<?= strtolower(str_replace(' ', '', $order['order_status'])); ?>">
                            <?= htmlspecialchars($order['order_status']); ?>
                        </span>
                    </div>
                    
                    <div class="order-items-summary">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="item-detail">
                                <h2><?= htmlspecialchars($item['product_name']); ?></h2>
                                <p class="item-quantity">Quantity: <?= htmlspecialchars($item['quantity']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="status-tracker-container">
                        <ul class="status-tracker">
                            <?php foreach ($statusSteps as $index => $step): 
                                $isCompleted = $index <= $currentStatusIndex;
                                $isActive = $index === $currentStatusIndex;
                                $icon = match($step) {
                                    'Pending' => 'bi-hourglass-split',
                                    'Approved' => 'bi-hand-thumbs-up-fill',
                                    'Shipped' => 'bi-box-seam-fill',
                                    'Out for Delivery' => 'bi-truck',
                                    'Delivered' => 'bi-check-circle-fill',
                                    default => 'bi-circle'
                                };
                            ?>
                                <li class="status-step <?= $isCompleted ? 'completed' : ''; ?> <?= $isActive ? 'active' : ''; ?>">
                                    <div class="status-icon-wrapper">
                                        <i class="bi <?= $icon; ?>"></i>
                                    </div>
                                    <span class="status-label"><?= htmlspecialchars($step); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="order-map-container">
                        <h3>Delivery Route Map</h3>
                        <?php if ($hasCoords): ?>
                        <div 
                            id="map-<?= $orderId; ?>" 
                            class="order-map"
                            data-delivery-lat="<?= $deliveryLat; ?>"
                            data-delivery-lng="<?= $deliveryLng; ?>"
                            data-vendor-lat="<?= $vendorLat; ?>"
                            data-vendor-lng="<?= $vendorLng; ?>"
                            data-vendor-name="<?= $vendorName; ?>"
                        ></div>
                        <?php else: ?>
                        <p class="map-error">Location data is unavailable for this order (Order #<?= $orderId; ?>).</p>
                        <?php endif; ?>
                    </div>
                    <div class="order-details-link">
                        <a href="index.php?action=receipt&order_id=<?= $orderId; ?>">
                            View Order Details <i class="bi bi-arrow-right-circle"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-orders">
                <i class="bi bi-box-seam-fill"></i>
                <p>You haven't placed any orders yet. Start shopping!</p>
                <a href="index.php?action=products&category_id=all" class="shop-now-btn">Shop Now</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
<script src="https://cdn.rawgit.com/tomickek/leaflet-ant-path/master/dist/leaflet-ant-path.js"></script>
<script src="app/scripts/track_order.js"></script>
<script src="app/scripts/notification.js"></script>

</body>
</html>