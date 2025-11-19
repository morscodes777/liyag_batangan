<?php
if (!isset($pending_vendors)) {
    $pending_vendors = [];
}
$active_action = 'pending_stores';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Stores</title>
    <link rel="stylesheet" href="public/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/assets/css/pending_store.css">
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

            <a href="index.php?action=product_approval"><i class="bi bi-bag-check"></i> Product Approval</a>
                            <a href="index.php?action=salesAndPerformance" class="<php if($current_action == 'salesAndPerformance') echo 'active'; ?>"><i class="bi bi-wallet2"></i> Sales & Performance</a>
            <a href="index.php?action=logout" class="logout-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </aside>

    <main class="content">
        <header class="main-header">
            <h1>Pending Stores</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></span>
            </div>
        </header>

        <section class="admin-table-section full-width-table">
            <h2><i class="bi bi-shop-fill"></i> Stores Awaiting Validation</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Business Name</th>
                        <th>Status</th>
                        <th>Registration Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_vendors as $vendor): ?>
                        <tr>
                            <td><?= htmlspecialchars($vendor['business_name'] ?? $vendor['name']); ?></td>
                            <td>
                                <span class="status-tag status-<?=
                                    strtolower(str_replace(' ', '-', $vendor['status'] ?? 'Pending'));
                                ?>">
                                    <?= htmlspecialchars($vendor['status'] ?? 'Pending'); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($vendor['registration_date'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="btn btn-gold-outline view-docs-btn" data-vendor-id="<?= $vendor['vendor_id']; ?>">
                                    <i class="bi bi-eye"></i> View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pending_vendors)): ?>
                        <tr><td colspan="4">No vendors currently requiring validation.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<div id="viewDocsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalVendorName">Vendor Details</h3>
            <button id="closeModal">✖</button>
        </div>
        
        <div class="vendor-profile-layout">
            
            <div class="vendor-details-info">
                <h4>Primary Information</h4>
                <p><strong>Owner:</strong> <span id="ownerName">N/A</span></p>
                <p><strong>Business Name:</strong> <span id="businessName">N/A</span></p>
                <p><strong>Email:</strong> <span id="vendorEmail">N/A</span></p>
                <p><strong>Phone:</strong> <span id="vendorPhone">N/A</span></p>
                <p><strong>Registration Date:</strong> <span id="regDate">N/A</span></p>
                <p><strong>Vendor ID:</strong> <span id="vendorID">N/A</span></p>
            </div>
            
            <div class="vendor-logo-action">
                <img id="vendorLogo" src="public/assets/images/default_logo.png" alt="Vendor Logo">
                
                <button class="btn btn-document" id="showDocsButton">
                    <i class="bi bi-file-earmark-check"></i> Show Business Documents
                </button>
                
                <hr style="width: 100%; border-top: 1px solid #eee; margin: 15px 0;">
                
                <p style="font-size: 0.9em; color: #666; font-weight: 600;">Admin Actions</p>
                <button class="btn btn-success" id="modalApproveBtn" style="width: 100%; margin-bottom: 5px;">
                    <i class="bi bi-check-lg"></i> Approve Vendor
                </button>
                <button class="btn btn-danger" id="modalRejectBtn" style="width: 100%;">
                    <i class="bi bi-x-lg"></i> Reject (Add Reason)
                </button>
            </div>
        </div>

        <div id="businessDocumentArea">
            <h4>Uploaded Business Documents</h4>
            <div class="document-item">
                <h5>Business Permit/DTI Registration</h5>
                <img id="permitDocument" src="" alt="Business Permit" class="document-img document-img-medium">
            </div>
            <p style="margin-top: 15px; font-style: italic; color: #888;">Ensure all documents are clear and valid before approving.</p>
        </div>
        
    </div>
</div>

<script src="app/scripts/pending_store.js"></script>
<script src="app/scripts/stores.js"></script>

</body>
</html>