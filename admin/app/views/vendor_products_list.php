<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products for <?php echo htmlspecialchars($vendor_info['business_name'] ?? 'Vendor'); ?> - Liyag Batangan</title>
    <link rel="stylesheet" href="public/assets/css/dashboard.css">
    <link rel="stylesheet" href="public/assets/css/vendor_product.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>
    <?php
    // NOTE: $pending_count, $active_count, and $outofstock_count 
    // are passed directly from the AdminController, based on ALL products.
    
    $product_actions = ['product_approval', 'view_vendor_products', 'productApprovalQueue'];
    $is_product_active = isset($_GET['action']) && in_array($_GET['action'], $product_actions);
    ?>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">Liyag Batangan Admin</div>
            <nav>
                <a href="index.php?action=admin_dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <div class="sidebar-dropdown">
                    <a href="vendor" class="dropdown-toggle"><i class="bi bi-shop"></i> Vendor</a>
                    <ul class="dropdown-menu">
                        <li><a href="index.php?action=approved_stores"><i class="bi bi-check-circle"></i> Approved Stores</a></li>
                        <li><a href="index.php?action=pending_stores"><i class="bi bi-hourglass-split"></i> Pending Stores</a></li>
                        <li><a href="index.php?action=rejected_stores"><i class="bi bi-x-octagon"></i> Rejected Stores</a></li>
                    </ul>
                </div>
                <a href="index.php?action=product_approval" class="<?php echo $is_product_active ? 'active' : ''; ?>"><i class="bi bi-bag-check"></i> Product Approval</a>
                <a href="index.php?action=salesAndPerformance" class="<php if($current_action == 'salesAndPerformance') echo 'active'; ?>"><i class="bi bi-wallet2"></i> Sales & Performance</a>
                <a href="index.php?action=logout" class="logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </aside>

        <main class="content">
            <header class="main-header">
                <h1>Products from: <?php echo htmlspecialchars($vendor_info['business_name'] ?? 'Unknown Vendor'); ?></h1>
                <div class="user-info">
                    <a href="index.php?action=product_approval" class="btn btn-red-outline"><i class="bi bi-arrow-left"></i> Back to Vendor List</a>
                </div>
            </header>

            <section class="summary-cards">
                <div class="card card-alert">
                    <div class="icon-box"><i class="bi bi-clock-history"></i></div>
                    <div class="details">
                        <p>Pending Products</p>
                        <h3><?php echo number_format($pending_count ?? 0); ?></h3>
                    </div>
                </div>
                <div class="card card-success">
                    <div class="icon-box"><i class="bi bi-check2-square"></i></div>
                    <div class="details">
                        <p>Active Products</p>
                        <h3><?php echo number_format($active_count ?? 0); ?></h3>
                    </div>
                </div>
                <div class="card card-danger">
                    <div class="icon-box"><i class="bi bi-box-seam"></i></div>
                    <div class="details">
                        <p>Out of Stock</p>
                        <h3><?php echo number_format($outofstock_count ?? 0); ?></h3>
                    </div>
                </div>
            </section>

            <section class="filter-bar" style="margin-bottom: 20px;">
                <form action="" method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="action" value="<?php echo htmlspecialchars($_GET['action'] ?? 'view_vendor_products'); ?>">
                    <input type="hidden" name="vendor_id" value="<?php echo htmlspecialchars($_GET['vendor_id'] ?? ''); ?>">

                    <strong style="margin-right: 5px;">Filter by Status:</strong>

                    <button type="submit" name="status" value="" 
                        class="btn <?php echo (!isset($_GET['status']) || $_GET['status'] == '') ? 'btn-gold' : 'btn-secondary-outline'; ?>">
                        <i class="bi bi-funnel-fill"></i> All
                    </button>
                    
                    <button type="submit" name="status" value="Active" 
                        class="btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'Active') ? 'btn-success' : 'btn-success-outline'; ?>">
                        <i class="bi bi-check-lg"></i> Active
                    </button>

                    <button type="submit" name="status" value="Pending" 
                        class="btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'btn-alert' : 'btn-alert-outline'; ?>">
                        <i class="bi bi-hourglass-split"></i> Pending
                    </button>

                    <button type="submit" name="status" value="OutOfStock" 
                        class="btn <?php echo (isset($_GET['status']) && $_GET['status'] == 'OutOfStock') ? 'btn-danger' : 'btn-danger-outline'; ?>">
                        <i class="bi bi-box-seam"></i> Out of Stock
                    </button>
                </form>
            </section>
            
            <section class="admin-table-section">
                <h2><i class="bi bi-list-task"></i> Product List</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($vendor_products)): ?>
                            <?php foreach ($vendor_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>₱<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="status-tag status-<?php echo strtolower(str_replace(' ', '-', $product['status'])); ?>">
                                            <?php echo htmlspecialchars($product['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-gold-outline view-details-btn" data-product-id="<?php echo $product['product_id']; ?>">
                                            <i class="bi bi-eye"></i> View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">This vendor currently has no products listed.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main> 
    </div>

    <div id="product-details-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div id="modal-content-placeholder">
                <div class="loading-spinner"></div>
            </div>
            
            <div id="modal-actions" class="modal-actions">
                </div>
        </div>
    </div>
    <div id="lottie-success-modal" class="lottie-modal">
        <div class="lottie-modal-content">
            <div id="lottie-success-animation" style="width: 200px; height: 200px;"></div>
            <h2 id="lottie-modal-title">Status Updated!</h2>
            <p>The product status has been successfully updated.</p>
            <button class="btn btn-gold" onclick="closeLottieModal()">Got It</button>
        </div>
    </div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js"></script>
<script src="app/scripts/stores.js"></script>
<script src="app/scripts/vendor_product.js"></script>
</body>
</html>