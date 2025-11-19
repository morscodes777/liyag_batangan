<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Liyag Batangan</title>
    <link rel="stylesheet" href="public/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">Liyag Batangan Admin</div>
            <nav>
                <a href="index.php?action=admin_dashboard" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
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
               <a href="index.php?action=product_approval" class="<?php echo (isset($_GET['action']) && in_array($_GET['action'], ['product_approval', 'view_vendor_products'])) ? 'active' : ''; ?>"><i class="bi bi-bag-check"></i> Product Approval</a>
                <a href="index.php?action=salesAndPerformance" class="<php if($current_action == 'salesAndPerformance') echo 'active'; ?>"><i class="bi bi-wallet2"></i> Sales & Performance</a>
                <a href="index.php?action=logout" class="logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </aside>

        <main class="content">
            <header class="main-header">
                <h1>Dashboard Overview</h1>
                <div class="user-info">
                    <span>Welcome, **<?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>**</span>
                </div>
            </header>

            <section class="summary-cards">
                <div class="card clickable-card commission-card" data-toggle="modal" data-target="#commissionHistoryModal">
                    <div class="icon-box"><i class="bi bi-cash-stack"></i></div>
                    <div class="details">
                        <p>Total Platform Comission</p>
                        <h3>₱<?php echo number_format($total_platform_sales, 2); ?></h3>
                    </div>
                </div>
                <div class="card">
                    <div class="icon-box"><i class="bi bi-shop"></i></div>
                    <div class="details">
                        <p>Pending Vendors</p>
                        <h3><?php echo count($pending_vendors); ?></h3>
                    </div>
                </div>
                <div class="card">
                    <div class="icon-box"><i class="bi bi-check2-square"></i></div>
                    <div class="details">
                        <p>Products Awaiting Approval</p>
                        <h3><?php echo count($pending_products); ?></h3>
                    </div>
                </div>
            </section>

            <section class="admin-table-section">
                <h2><i class="bi bi-shop"></i> Vendor Validation Queue</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_vendors as $vendor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vendor['name']); ?></td>
                                <td>
                                    <span class="status-tag status-<?php echo strtolower(str_replace(' ', '-', $vendor['status'])); ?>">
                                        <?php echo htmlspecialchars($vendor['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="index.php?action=pending_stores" class="btn btn-gold-full">
                                        <i class="bi bi-arrow-right-circle"></i> Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pending_vendors)): ?>
                            <tr><td colspan="4">No vendors currently requiring validation.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

           <section class="admin-table-section">
                <h2><i class="bi bi-box-seam"></i> Product Approval Queue</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Store</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>₱<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                <td><?php echo htmlspecialchars($product['vendor_name'] ?? 'Vendor ID: ' . $product['vendor_id']); ?></td>
                                <td>
                                    <a href="index.php?action=view_vendor_products&vendor_id=<?php echo $product['vendor_id']; ?>&status=Pending" class="btn btn-success">
                                        <i class="bi bi-arrow-right-circle"></i> Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pending_products)): ?>
                            <tr><td colspan="5">No products currently awaiting approval.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
            
            <section class="admin-table-section sales-reports">
                <h2><i class="bi bi-graph-up-arrow"></i> Sales and Vendor Performance</h2>
                <div class="report-summary">
                    <?php foreach ($vendor_sales as $sale): ?>
                        <div class="vendor-sales-card">
                            <h4><?php echo htmlspecialchars($sale['vendor']); ?></h4>
                            <p>₱<?php echo number_format($sale['sales'], 2); ?></p>
                            <div class="gold-progress-bar" style="width: <?php echo ($sale['sales'] / max(array_column($vendor_sales, 'sales'))) * 100; ?>%;"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        </main>
    </div>
    <div class="modal fade" id="commissionHistoryModal" tabindex="-1" role="dialog" aria-labelledby="commissionHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commissionHistoryModalLabel">Platform Commission History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Order Date</th>
                                <th>Order Total</th>
                                <th>Commission</th>
                            </tr>
                        </thead>
                        <tbody id="commissionHistoryTableBody">
                            </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" style="text-align: right;">Total Commission:</th>
                                <th id="modalTotalCommission">₱<?php echo number_format($total_platform_sales, 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="app/scripts/stores.js"></script>
<script src="app/scripts/dashboard.js"></script>
</body>
</html>