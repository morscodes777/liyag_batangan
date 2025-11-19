<?php
$vendorModel = new VendorModel();

// 1. Fetch the approved vendors (assuming this replaces your old mock data)
$approved_vendors = $vendorModel->getVendorsByStatus('Approved'); 

// 2. Loop through the vendors and fetch total sales
if (!empty($approved_vendors)) {
    foreach ($approved_vendors as &$vendor) { // Use & for reference to modify the array
        // Use the vendor_id from the fetched data
        $vendor['sales'] = $vendorModel->getTotalVendorSales($vendor['vendor_id']);
    }
    unset($vendor); // Break the reference
}

// Fallback if no vendors are found
if (!isset($approved_vendors)) {
     $approved_vendors = [];
}
$active_action = 'approved_stores';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approved Stores</title>
    <link rel="stylesheet" href="public/assets/css/dashboard.css">
    <link rel="stylesheet" href="public/assets/css/approved_store.css">
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
                </div>
                
                <a href="index.php?action=product_approval" class="<?php echo (isset($_GET['action']) && in_array($_GET['action'], ['product_approval', 'view_vendor_products'])) ? 'active' : ''; ?>"><i class="bi bi-bag-check"></i> Product Approval</a>
                                <a href="index.php?action=salesAndPerformance" class="<php if($current_action == 'salesAndPerformance') echo 'active'; ?>"><i class="bi bi-wallet2"></i> Sales & Performance</a>
                <a href="index.php?action=logout" class="logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </aside>

        <main class="content">
            <header class="main-header">
                <h1>Approved Stores</h1>
                <div class="user-info">
                    <span>Welcome, **<?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>**</span>
                </div>
            </header>

            <section class="admin-table-section full-width-table">
                <h2><i class="bi bi-check-circle-fill"></i> Active Vendors</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Total Sales</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approved_vendors as $vendor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vendor['name']); ?></td>
                                <td>₱<?php echo number_format($vendor['sales'] ?? 0, 2); ?></td>
                                <td>
                                    <span class="status-tag status-<?php echo strtolower($vendor['status']); ?>">
                                        <?php echo htmlspecialchars($vendor['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button 
                                        class="btn btn-gold-outline view-profile-btn" 
                                        data-vendor-id="<?php echo htmlspecialchars($vendor['vendor_id']); ?>">
                                        <i class="bi bi-eye"></i> View Profile
                                    </button>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($approved_vendors)): ?>
                            <tr><td colspan="5">No currently approved vendors found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        </main>
    </div>

    <div id="vendorProfileModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Vendor Profile</h3>
                <button class="modal-close-btn"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div id="modalLoading" class="loading-state">
                    <div class="spinner"></div>
                    <p style="margin-top: 15px;">Fetching vendor details...</p>
                </div>
                
                <div id="modalContent" style="display: none;">
                    
                    <div class="profile-header">
                        <img id="storeLogo" src="" alt="Store Logo" class="profile-logo" onerror="this.onerror=null;this.src='public/assets/images/default_logo.png';">
                        <h4 id="businessName"></h4>
                        <p id="storeStatus" class="status-tag status-approved"></p>
                    </div>

                    <div id="businessDescription" class="modal-description"></div>

                    <div class="info-grid">
                        <div class="info-card">
                            <strong>Owner Name</strong>
                            <span id="ownerName"></span>
                        </div>
                        <div class="info-card">
                            <strong>Owner Email</strong>
                            <span id="ownerEmail"></span>
                        </div>
                        <div class="info-card">
                            <strong>Owner Phone</strong>
                            <span id="ownerPhone"></span>
                        </div>
                        <div class="info-card">
                            <strong>Registration Date</strong>
                            <span id="registrationDate"></span>
                        </div>
                        <div class="info-card full-width">
                            <strong>Business Address</strong>
                            <span id="businessAddress"></span>
                        </div>
                        <div class="info-card full-width">
                            <strong>Verification Document</strong>
                            <a id="verificationDocumentLink" href="#" target="_blank" class="btn btn-gold-outline btn-sm" style="display: none;">
                                <i class="bi bi-file-earmark-arrow-down"></i> View Document
                            </a>
                            <span id="verificationDocumentNone">No document provided.</span>
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="modal-actions" id="modalActions">
                <button id="suspendVendorButton" class="btn btn-danger" style="display:none;"><i class="bi bi-archive"></i> Suspend Vendor</button>
            </div>
        </div>
    </div>
    <script src="app/scripts/stores.js"></script>
    <script src="app/scripts/approved_store.js"></script>
</body>
</html>