<?php
// views/account_management.php

// Ensure user is logged in and has user_id set
if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php?action=login");
    exit;
}
$user = $_SESSION['user'];
$user_id = $_SESSION['user_id'];

// 1. Require the Account Model
require_once __DIR__ . '/../models/accountModels.php';

// 2. Instantiate the Model
$accountModel = new AccountModel();

// 3. Fetch Real Analytics Data
$totalSpent = $accountModel->getTotalSpentByUser($user_id);
$totalOrders = $accountModel->getTotalOrdersByUser($user_id);

// Fetch data for the Pie Chart using the new model method
$categoryData = $accountModel->getSpendingByCategory($user_id);

// Fetch data for the Bar Chart
$monthlyData = $accountModel->getMonthlySpending($user_id);

$totalSpent = $accountModel->getTotalSpentByUser($user_id);
$totalOrders = $accountModel->getTotalOrdersByUser($user_id);
// Add this line:
$productsToReviewCount = $accountModel->countProductsToReviewByUser($user_id);
// Fetch data for the Pie Chart using the new model method
$categoryData = $accountModel->getSpendingByCategory($user_id);


// Placeholder for actual user data (replace with database fetch if needed)
$userData = [
    'name' => $user['name'] ?? 'User Name',
    'email' => $user['email'] ?? 'user@example.com',
    'phone_number' => $user['phone_number'] ?? 'N/A',
    'address' => $user['address'] ?? 'Click to set address on map',
    'latitude' => $user['latitude'] ?? '',
    'longitude' => $user['longitude'] ?? '',
    'user_type' => $user['user_type'] ?? 'Customer',
    'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
];

// If $monthlyData was empty from DB, use sample data structure for chart initialization
if (empty($monthlyData)) {
    $monthlyData = [
        ['month' => 'Jan', 'spent' => 0],
        ['month' => 'Feb', 'spent' => 0],
        ['month' => 'Mar', 'spent' => 0],
    ];
}


function formatPhoneNumber($number) {
    // Remove all non-digits
    $cleaned = preg_replace('/\D/', '', $number);

    // If it starts with '63', remove it.
    if (str_starts_with($cleaned, '63')) {
        $cleaned = substr($cleaned, 2);
    }
    // If it starts with '0', remove it.
    if (str_starts_with($cleaned, '0')) {
        $cleaned = substr($cleaned, 1);
    }
    
    // CRITICAL: Ensure it starts with '9' if we need to enforce the mobile prefix
    if (!str_starts_with($cleaned, '9') && strlen($cleaned) > 0) {
        $cleaned = '9' . $cleaned; 
    }

    // Ensure it's 10 digits (the Philippine mobile number part)
    if (strlen($cleaned) >= 10) {
        $part1 = substr($cleaned, 0, 3);
        $part2 = substr($cleaned, 3, 3);
        $part3 = substr($cleaned, 6, 4);
        return "+63 $part1 $part2 $part3"; // Format: +63 XXX XXX XXXX
    }
    return htmlspecialchars($number); // Return original if format is unexpected
}
// --- End of data setup ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Management - Liyag Batangan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/assets/css/home.css">
    <link rel="stylesheet" href="public/assets/css/notification.css">
    <link rel="stylesheet" href="public/assets/css/account_management.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="public/assets/default/icon/account.png">
</head>
<body>

<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            ACCOUNT MANAGEMENT
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

            <div class="profile-dropdown">
            <button id="profileBtn" title="Profile">
                <div class="profile-hover-wrapper">
                    <img src="<?php echo $user['profile_picture'] ? 'uploads/' . basename($user['profile_picture']) : 'public/assets/default/default_profile.jpg'; ?>" 
                        alt="Profile" class="profile-icon">
                    <span class="profile-name"><?php echo htmlspecialchars($user['name']); ?></span>
                </div>
            </button>

                <div class="dropdown-content" id="dropdownMenu">
                    <a href="index.php?action=account">Account Management</a>
                    <?php if (!empty($user['user_type']) && $user['user_type'] === 'Vendor'): ?>
                        <a href="index.php?action=view_vendor_store">View Store</a>
                    <?php else: ?>
                        <a href="index.php?action=create_business">Start Selling</a>
                    <?php endif; ?>

                    <form method="POST" action="index.php?action=logout">
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                </div>
            </div>
        </nav>
    </div>
</header>


<main>
    <div class="main-content-split">
        <div class="account-container">
            <h2>My Profile</h2>
            <form id="profileForm" method="POST" action="index.php?action=update_profile" enctype="multipart/form-data">
                <div class="image-wrapper">
                    <img id="previewImage" src="<?php echo $user['profile_picture'] ? 'uploads/' . basename($user['profile_picture']) : 'public/assets/default/default_profile.jpg'; ?>" alt="Profile Picture">
                    <input type="file" name="profile_picture" id="profile_picture" class="edit-mode" accept="image/*" onchange="previewSelectedImage(event)">
                </div>

                <div class="profile-info">
                    <div class="profile-row">
                        <div class="profile-label">Name:</div>
                        <div class="profile-value view-mode" data-field="name"><?php echo htmlspecialchars($userData['name']); ?>
                        </div>
                        <input type="text" class="edit-mode" name="name" id="name" 
                            value="<?php echo htmlspecialchars($userData['name']); ?>" 
                            style="display:none;">
                        <span id="name-error" class="error-message" style="display:none;"></span>
                    </div>
                    <div class="profile-row">
                        <div class="profile-label">Email:</div>
                        <div class="profile-value"><?php echo htmlspecialchars($userData['email']); ?></div>
                    </div>
                   <div class="profile-row">
                        <div class="profile-label">Phone:</div>
                        <div class="profile-value view-mode" data-field="phone_number"><?php echo htmlspecialchars(formatPhoneNumber($userData['phone_number'])); ?>
                        </div>
                        <input type="text" class="edit-mode" name="phone_number" id="phone_number" 
                            value="<?php echo htmlspecialchars(formatPhoneNumber($userData['phone_number'])); ?>" 
                            style="display:none;">
                        <span id="phone-error" class="error-message" style="display:none;"></span>
                    </div>
                    <div class="profile-row">
                        <div class="profile-label">Address:</div>
                        <div class="profile-value view-mode" data-field="address"><?php echo htmlspecialchars($userData['address']); ?></div>
                        <textarea class="edit-mode" id="address" name="address" readonly onclick="openMapModal()" rows="3" style="display:none;"><?php echo htmlspecialchars($userData['address']); ?></textarea>
                        <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($userData['latitude'] ?? ''); ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($userData['longitude'] ?? ''); ?>">

                    </div>
                    <div class="profile-row">
                        <div class="profile-label">User Type:</div>
                        <div class="profile-value"><?php echo htmlspecialchars($userData['user_type']); ?></div>
                    </div>
                    <div class="profile-row">
                        <div class="profile-label">Joined:</div>
                        <div class="profile-value"><?php echo date("F j, Y", strtotime($userData['created_at'])); ?></div>
                    </div>
                </div>

                <div class="profile-actions">
                    <button id="editBtn" type="button" class="action-btn">
                        <i class="bi bi-pencil-square"></i> Edit Profile
                    </button>
                    <button id="cancelBtn" type="button" class="action-btn cancel-btn" style="display:none;">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button id="saveBtn" type="submit" class="action-btn save-btn" style="display:none;">
                        <i class="bi bi-save"></i> Save
                    </button>
                </div>
            </form>
        </div>

        <div class="analytics-container">
            <h2 class="section-title">User Status</h2>
            <div class="analytics-grid">
                <div class="analytics-card">
                    <i class="bi bi-wallet2 icon-spent"></i>
                    <div class="analytics-info">
                        <p class="analytics-label">Total Spent</p>
                        <p class="analytics-value">₱<?= number_format($totalSpent, 2); ?></p>
                    </div>
                </div>
                <a href="index.php?action=track_orders" class="analytics-card clickable-card">
                    <i class="bi bi-journal-text icon-orders"></i>
                    <div class="analytics-info">
                        <p class="analytics-label">Total Orders</p>
                        <p class="analytics-value"><?= htmlspecialchars($totalOrders); ?> Product(s)</p>
                    </div>
                </a>
                <a href="index.php?action=reviews" class="analytics-card clickable-card">
                    <i class="bi bi-star-fill"></i>
                    <div class="analytics-info">
                     <p class="analytics-label">To Review</p>
                     <p class="analytics-value"><?= htmlspecialchars($productsToReviewCount); ?> Product(s)</p>
                    </div>
                </a>
            </div>

            <div class="chart-container">
                <div class="chart-card">
                    <h3 class="chart-title">Spending by Category</h3>
                    <canvas id="categoryPieChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3 class="chart-title">Monthly Spending</h3>
                    <canvas id="monthlyBarChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    

<div id="mapModal" class="modal">
    <div class="modal-content map-modal-content">
        <h3 class="modal-title">Select Your Location</h3>
        <p id="address-display" class="current-address-display">Fetching address...</p>

        <div id="map"></div>
        <div id="center-marker"></div>

        <div class="map-modal-buttons">
            <button 
                onclick="selectLocation()" 
                id="confirmLocationBtn" 
                class="map-btn select-btn"
                disabled>
                Determining Address...
            </button>
            <button id="closeMapModal" class="map-btn cancel-btn">Cancel</button>
        </div>
    </div>
</div>
</main>

<footer class="footer">
    <div class="social-icons">
        <a href="https://www.facebook.com/people/Liyag-Batangan/61583172300285/"><i class="bi bi-facebook"></i></a>
        <a href="https://www.instagram.com/liyag.batangan/"><i class="bi bi-instagram"></i></a>
    </div>
    <p class="copyright">&copy; <?= date('Y') ?> Liyag Batangan. All rights reserved.</p>
</footer>



<div id="cancelModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Discard changes?</h3>
        <p>You have unsaved changes. Are you sure you want to cancel?</p>
        <div class="modal-buttons">
            <button id="confirmCancel">Yes, discard</button>
            <button id="closeCancelModal">No, keep editing</button>
        </div>
    </div>
</div>


<script src="app/scripts/account_management.js"></script>
<script src="app/scripts/notification.js"></script>


<script>
    let monthlyBarChartInstance = null;
    let categoryPieChartInstance = null;

    document.addEventListener('DOMContentLoaded', () => {
        const monthlyCtx = document.getElementById('monthlyBarChart');
        if (monthlyCtx) {
            if (monthlyBarChartInstance) {
                monthlyBarChartInstance.destroy();
            }

            const monthlyData = <?= json_encode($monthlyData); ?>;
            monthlyBarChartInstance = new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthlyData.map(d => d.month),
                    datasets: [{
                        label: 'Amount Spent (₱)',
                        data: monthlyData.map(d => d.spent),
                        backgroundColor: 'gold',
                        borderColor: 'orange',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        const categoryCtx = document.getElementById('categoryPieChart');
        if (categoryCtx) {
            if (categoryPieChartInstance) {
                categoryPieChartInstance.destroy();
            }

            const categoryData = <?= json_encode($categoryData); ?>;
            // Define colors dynamically based on the number of categories
            const colors = ['#FFD700', '#DAA520', '#B8860B', '#F0E68C', '#EEDD82', '#CDAA7D', '#FFE4B5'];

            categoryPieChartInstance = new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: categoryData.map(d => d.name),
                    datasets: [{
                        data: categoryData.map(d => d.spent),
                        // Use slice to ensure only the necessary number of colors is used
                        backgroundColor: colors.slice(0, categoryData.length), 
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }
    });
</script>
</body>
</html>