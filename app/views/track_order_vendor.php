<?php
$userType = $_SESSION['user']['user_type'] ?? 'Guest';

if (!isset($_SESSION['user']) || $userType !== 'Vendor') {
    header("Location: index.php?action=login");
    exit;
}

$vendorId = $_SESSION['user']['vendor_id'] ?? null;
$storeName = $_SESSION['user']['store_name'] ?? 'Your Store';

$currentFilterStatus = $_GET['status'] ?? 'Pending';
$statusSteps = ['Pending', 'Approved', 'Shipped', 'Out for Delivery', 'Delivered'];

$currentStatusIndex = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor - Order Tracking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/assets/css/home.css">
    <link rel="stylesheet" href="public/assets/css/responsive-home.css">
    <link rel="stylesheet" href="public/assets/css/track_order.css">
    <link rel="stylesheet" href="public/assets/css/track_order_vendor.css">
    <style>
        /* TEMPORARY DEBUG CSS: This should force the product details to be visible. */
        .order-product-details {
            display: block !important;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 5px solid #ffc107;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            ORDER TRACKING
        </div>
        <nav class="nav-icons">
            <a href="index.php?action=view_vendor_store" title="My Store">
                <i class="bi bi-shop-window"></i>
            </a>
            <a href="index.php?action=home" title="Home">
                <i class="bi bi-house-fill"></i>
            </a>
            </nav>
    </div>
    </header>

<main class="track-order-main">
    <h1 class="page-title">Orders for <?= htmlspecialchars($storeName); ?></h1>
    
    <section class="categories status-filter-section">
       <div class="category-grid">
            <?php
            $filterOptions = [
                'Pending' => ['label' => 'PENDING', 'icon' => 'bi-hourglass-split'],
                'Approved' => ['label' => 'APPROVED', 'icon' => 'bi-hand-thumbs-up-fill'],
                'Shipped' => ['label' => 'SHIPPED', 'icon' => 'bi-box-seam-fill'],
                'Out for Delivery' => ['label' => 'OUT FOR DELIVERY', 'icon' => 'bi-truck'],
                'Delivered' => ['label' => 'DELIVERED', 'icon' => 'bi-check-circle-fill'],
            ];
            
            foreach ($filterOptions as $statusValue => $details) {
                $isActiveFilter = ($statusValue == $currentFilterStatus) ? 'active-filter' : '';
                $urlStatus = urlencode($statusValue);
                echo "
                <a href=\"index.php?action=track_orders_vendor&status={$urlStatus}\" class=\"category-card1 {$isActiveFilter}\">
                    <i class=\"bi {$details['icon']}\"></i>
                    <span class=\"status-label\">{$details['label']}</span>
                </a>";
            }
            ?>
        </div>
    </section>

    <section class="order-list-container">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): 
                $orderStatusClass = strtolower(str_replace(' ', '-', $order['order_status']));
                $currentStatusIndex = array_search($order['order_status'], $statusSteps) !== false 
                                              ? array_search($order['order_status'], $statusSteps) 
                                              : 0;
            ?>
                <div class="order-card order-status-<?= $orderStatusClass; ?>">
                    <div class="order-details">
                        <p>
                            <span>Order #<?= htmlspecialchars($order['order_id']); ?></span>
                            <span class="order-status-badge status-<?= $orderStatusClass; ?>">
                                <?= htmlspecialchars($order['order_status']); ?>
                            </span>
                        </p>
                        <p>Customer: <?= htmlspecialchars($order['customer_name']); ?></p>
                        <p>Total: ₱<?= number_format($order['order_total'], 2); ?></p>
                        <p>Date: <?= date("M d, Y g:i A", strtotime($order['order_date'])); ?></p>
                        <p class="vendor-payout-display">
                            Vendor Payout: 
                            <span class="payout-amount">
                                ₱<?= number_format($order['vendor_payout'], 2); ?>
                            </span>
                        </p>
                    </div>

                    <div class="order-product-details">
                        <?php if (!empty($order['items'])): ?>
                            <?php foreach ($order['items'] as $item): ?>
                                <h2><?= htmlspecialchars($item['product_name']); ?></h2>
                                <p class="quantity">Quantity: <?= htmlspecialchars($item['quantity']); ?></p>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-items">No items listed for this order.</p>
                        <?php endif; ?>
                    </div>

                    <div class="status-tracker-container">
                        <ul class="status-tracker">
                            <?php foreach ($statusSteps as $index => $step): 
                                $isCompleted = $index < $currentStatusIndex; 
                                $isActive = $index === $currentStatusIndex; 
                                
                                $icon = match($step) {
                                    'Pending' => 'bi-hourglass-split',
                                    'Approved' => 'bi-hand-thumbs-up', 
                                    'Shipped' => 'bi-box-seam',
                                    'Out for Delivery' => 'bi-truck',
                                    'Delivered' => 'bi-check-circle-fill',
                                    default => 'bi-circle'
                                };
                                
                                $stepClass = $isActive ? 'active' : ($isCompleted ? 'completed' : '');
                            ?>
                                <li class="status-step <?= $stepClass; ?>">
                                    <div class="status-icon-wrapper">
                                        <i class="bi <?= $icon; ?>"></i>
                                    </div>
                                    <span class="status-label"><?= htmlspecialchars($step); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="order-actions">
                        <button class="view-order-details-btn" data-order-id="<?= htmlspecialchars($order['order_id']); ?>">
                            View/Manage Order
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="no-orders">
                <i class="bi bi-box-seam-fill"></i>
                <p>No orders found with status: **<?= htmlspecialchars($currentFilterStatus); ?>**</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<div id="vendorOrderModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="closeModalBtn">&times;</span>
        <h2 id="modalOrderTitle">Manage Order #</h2>
        
        <div id="modalOrderDetails" class="modal-body-details">
            <p>Loading order details...</p>
        </div>

        <p id="statusMessage" style="margin-top: 10px; font-weight: 600; text-align: center;"></p>
        
        <div class="status-tracker-container">
            <ul class="status-tracker" id="modalStatusTracker">
                <?php 
                foreach ($statusSteps as $index => $step): 
                    $isCompleted = $index < $currentStatusIndex; 
                    $isActive = $index === $currentStatusIndex; 
                    
                    $icon = match($step) {
                        'Pending' => 'bi-hourglass-split',
                        'Approved' => 'bi-hand-thumbs-up', 
                        'Shipped' => 'bi-box-seam',
                        'Out for Delivery' => 'bi-truck',
                        'Delivered' => 'bi-check-circle-fill',
                        default => 'bi-circle'
                    };
                    
                    $stepClass = $isActive ? 'active' : ($isCompleted ? 'completed' : '');
                ?>
                    <li class="status-step <?= $stepClass; ?>">
                        <a href="#" class="status-update-link" data-status="<?= htmlspecialchars($step); ?>">
                            <div class="status-icon-wrapper">
                                <i class="bi <?= $icon; ?>"></i>
                            </div>
                            <span class="status-label"><?= htmlspecialchars($step); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        </div>
</div>
<script src="app/scripts/track_order.js"></script> 
<script src="app/scripts/track_order_vendor.js"></script>
<script>
    const VENDOR_ID = <?= json_encode($vendorId); ?>;
</script>
</body>
</html>