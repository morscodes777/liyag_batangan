<?php
// PHP variables expected from AdminController:
// $rejected_vendors (list of rejected vendors)

// Mock Data for demonstration (REMOVE THIS in production)
if (!isset($rejected_vendors)) {
    $rejected_vendors = [
        ['id' => 401, 'name' => 'Fake Goods Trading', 'status' => 'Rejected', 'rejection_reason' => 'Failed document verification'],
        ['id' => 402, 'name' => 'Old Batangas Cafe', 'status' => 'Rejected', 'rejection_reason' => 'Business license expired'],
    ];
}
$active_action = 'rejected_stores';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rejected Stores</title>
    <link rel="stylesheet" href="public/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">Liyag Batangan Admin</div>
            <nav>
                <a href="index.php?action=admin_dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
                
                <div class="sidebar-dropdown active">
                    <a href="#" class="active"><i class="bi bi-shop"></i> Vendor</a>
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
                
              <a href="index.php?action=product_approval" class="<?php echo (isset($_GET['action']) && in_array($_GET['action'], ['product_approval', 'view_vendor_products'])) ? 'active' : ''; ?>"><i class="bi bi-bag-check"></i> Product Approval</a>
                                <a href="index.php?action=salesAndPerformance" class="<php if($current_action == 'salesAndPerformance') echo 'active'; ?>"><i class="bi bi-wallet2"></i> Sales & Performance</a>
                <a href="index.php?action=logout" class="logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </aside>

        <main class="content">
            <header class="main-header">
                <h1>Rejected Stores</h1>
                <div class="user-info">
                    <span>Welcome, **<?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>**</span>
                </div>
            </header>

            <section class="admin-table-section full-width-table">
                <h2><i class="bi bi-x-octagon-fill"></i> Rejected Vendors</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Rejection Reason</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rejected_vendors as $vendor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vendor['name']); ?></td>
                                <td><?php echo htmlspecialchars($vendor['rejection_reason'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-tag status-<?php echo strtolower($vendor['status']); ?>">
                                        <?php echo htmlspecialchars($vendor['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-gold-outline"><i class="bi bi-envelope"></i> Notify Vendor</button>
                                    <button class="btn btn-success"><i class="bi bi-arrow-repeat"></i> Re-approve</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rejected_vendors)): ?>
                            <tr><td colspan="5">No rejected vendors found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        </main>
    </div>
    <script src="app/scripts/stores.js"></script>
</body>
</html>