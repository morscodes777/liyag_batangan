<?php 
// Assumes $vendor_sales and $products_sold are arrays passed from AdminController::salesAndPerformance()

// 1. Data extraction and preparation for Chart.js
$salesLabels = array_column($vendor_sales, 'vendor');
$salesData = array_column($vendor_sales, 'sales');

// Vendor Products Sold Performance Data
$productsLabels = array_column($products_sold, 'vendor');
$productsData = array_column($products_sold, 'products_sold');

// Determine active link for sidebar
$current_action = $_GET['action'] ?? 'admin_dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales and Performance - Liyag Batangan</title>
    <link rel="stylesheet" href="public/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
</head>
<body>

<div class="dashboard-container">
    <aside class="sidebar">
        <div class="logo">Liyag Batangan Admin</div>
        <nav>
            <a href="index.php?action=admin_dashboard" class="<?php echo ($current_action == 'admin_dashboard') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <div class="sidebar-dropdown">
                <a href="vendor" class="dropdown-toggle"><i class="bi bi-shop"></i> Vendor</a>
                <ul class="dropdown-menu">
                    <li>
                        <a href="index.php?action=approved_stores" 
                           class="<?php echo ($current_action == 'approved_stores') ? 'active-sub' : ''; ?>">
                            <i class="bi bi-check-circle"></i> Approved Stores
                        </a>
                    </li>
                    <li>
                        <a href="index.php?action=pending_stores" 
                           class="<?php echo ($current_action == 'pending_stores') ? 'active-sub' : ''; ?>">
                            <i class="bi bi-hourglass-split"></i> Pending Stores
                        </a>
                    </li>
                    <li>
                        <a href="index.php?action=rejected_stores" 
                           class="<?php echo ($current_action == 'rejected_stores') ? 'active-sub' : ''; ?>">
                            <i class="bi bi-x-octagon"></i> Rejected Stores
                        </a>
                    </li>
                </ul>
            </div>
            <a href="index.php?action=product_approval" class="<?php echo ($current_action == 'product_approval' || $current_action == 'view_vendor_products') ? 'active' : ''; ?>">
                <i class="bi bi-bag-check"></i> Product Approval
            </a>
            <a href="index.php?action=salesAndPerformance" class="<?php echo ($current_action == 'salesAndPerformance') ? 'active' : ''; ?>">
                <i class="bi bi-wallet2"></i> Sales & Reports
            </a>
            <a href="index.php?action=logout" class="logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </aside>

    <main class="content">
        <header class="main-header">
            <h1>Sales and Performance Report</h1>
            <div class="user-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong></span>
            </div>
        </header>

        <section class="admin-table-section sales-reports">
            <h2><i class="bi bi-graph-up-arrow"></i> Vendor Payout Performance (Total Sales)</h2>
            <p>This bar chart visualizes the <strong>total vendor payout</strong> from all completed (Delivered) orders.</p>
            <?php if (!empty($vendor_sales)): ?>
                <canvas id="vendorPayoutChart" style="max-height: 400px;"></canvas>
            <?php else: ?>
                <p>No vendor sales data available for delivered orders.</p>
            <?php endif; ?>
        </section>

        <section class="admin-table-section sales-reports">
            <h2><i class="bi bi-box-seam"></i> Vendor Product Sales Volume (Units Sold)</h2>
            <p>This bar chart visualizes the <strong>total product units sold</strong> by each vendor for all completed (Delivered) orders.</p>
            <?php if (!empty($products_sold)): ?>
                <canvas id="productsSoldChart" style="max-height: 400px;"></canvas>
            <?php else: ?>
                <p>No product unit sales data available for delivered orders.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
    // Vendor Dropdown Toggle Logic
    document.querySelector('.dropdown-toggle').addEventListener('click', function(e) {
        e.preventDefault();
        this.parentNode.classList.toggle('open');
    });

    // --- Data passed from PHP to JS ---
    const SALES_LABELS = <?php echo json_encode($salesLabels); ?>;
    const SALES_DATA = <?php echo json_encode($salesData); ?>;
    const PRODUCTS_LABELS = <?php echo json_encode($productsLabels); ?>;
    const PRODUCTS_DATA = <?php echo json_encode($productsData); ?>;

    const goldColor = '#FFD700';
    const darkColor = '#333333';
    
    if (typeof Chart !== 'undefined') {
        // --- Chart 1: Vendor Payout (Sales) ---
        const ctxPayout = document.getElementById('vendorPayoutChart');
        if (ctxPayout) {
            new Chart(ctxPayout.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: SALES_LABELS,
                    datasets: [{
                        label: 'Total Vendor Payout (PHP)',
                        data: SALES_DATA,
                        backgroundColor: goldColor,
                        borderColor: darkColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Payout Amount (PHP)' }
                        },
                        x: {
                            title: { display: true, text: 'Vendor Business Name' }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Payout: ₱' + new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(context.raw);
                                }
                            }
                        }
                    }
                }
            });
        }

        // --- Chart 2: Products Sold ---
        const ctxProducts = document.getElementById('productsSoldChart');
        if (ctxProducts) {
            new Chart(ctxProducts.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: PRODUCTS_LABELS,
                    datasets: [{
                        label: 'Total Product Units Sold',
                        data: PRODUCTS_DATA,
                        backgroundColor: 'rgba(51, 51, 51, 0.7)',
                        borderColor: darkColor,
                         backgroundColor: goldColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Units Sold' }
                        },
                        x: {
                            title: { display: true, text: 'Vendor Business Name' }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    }
</script>

<script src="app/scripts/stores.js"></script>
</body>
</html>
