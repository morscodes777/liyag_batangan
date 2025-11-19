<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Approval - Liyag Batangan</title>
    <link rel="stylesheet" href="public/assets/css/dashboard.css">
    <link rel="stylesheet" href="public/assets/css/products.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">Liyag Batangan Admin</div>
            <nav>
                <a href="index.php?action=admin_dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <div class="sidebar-dropdown">
                    <a href="vendor" class="dropdown-toggle"><i class="bi bi-shop"></i> Vendor</a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="index.php?action=approved_stores" 
                            class="<?php echo (isset($_GET['action']) && $_GET['action'] == 'approved_stores') ? 'active-sub' : ''; ?>">
                                <i class="bi bi-check-circle"></i> Approved Stores
                            </a>
                        </li>
                        <li>
                            <a href="index.php?action=pending_stores" 
                            class="<?php echo (isset($_GET['action']) && $_GET['action'] == 'pending_stores') ? 'active-sub' : ''; ?>">
                                <i class="bi bi-hourglass-split"></i> Pending Stores
                            </a>
                        </li>
                        <li>
                            <a href="index.php?action=rejected_stores" 
                            class="<?php echo (isset($_GET['action']) && $_GET['action'] == 'rejected_stores') ? 'active-sub' : ''; ?>">
                                <i class="bi bi-x-octagon"></i> Rejected Stores
                            </a>
                        </li>
                    </ul>
                </div>
                <a href="index.php?action=product_approval" class="<?php echo (isset($_GET['action']) && $_GET['action'] == 'product_approval') ? 'active' : ''; ?>"><i class="bi bi-bag-check"></i> Product Approval</a>
                                <a href="index.php?action=salesAndPerformance" class="<php if($current_action == 'salesAndPerformance') echo 'active'; ?>"><i class="bi bi-wallet2"></i> Sales & Performance</a>
                <a href="index.php?action=logout" class="logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </aside>
        <main class="content">
            <header class="main-header">
                <h1>Product Management</h1>
                <div class="user-info">
                    <span>Welcome, **<?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>**</span>
                </div>
            </header>

           <section class="admin-table-section">
                <h2><i class="bi bi-shop"></i> Active Business: Select a Store</h2>
                <div class="vendor-list-grid">
                    <?php foreach ($all_vendors as $vendor): ?>
                        <?php if ($vendor['status'] == 'Approved'): ?>
                            <a href="index.php?action=view_vendor_products&vendor_id=<?php echo $vendor['vendor_id']; ?>" 
                                class="vendor-card-link">
                                <div class="vendor-selection-card">
                                    
                                    <div class="vendor-details-row">
                                        <i class="bi bi-shop-window detail-icon name-icon"></i> 
                                        <h4 class="detail-text name-text"><?php echo htmlspecialchars($vendor['business_name']); ?></h4>
                                    </div>
                                    
                                    <div class="vendor-details-row">
                                        <i class="bi bi-geo-alt-fill detail-icon address-icon"></i> 
                                        <p class="detail-text address-text"><?php echo htmlspecialchars($vendor['business_address']); ?></p>
                                    </div>

                                </div>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (empty(array_filter($all_vendors, fn($v) => $v['status'] === 'Approved'))): ?>
                        <p>No approved vendors found yet.</p>
                    <?php endif; ?>
                </div>
            </section>
            </main>
    </div>

<script src="app/scripts/stores.js"></script>
</body>
</html>