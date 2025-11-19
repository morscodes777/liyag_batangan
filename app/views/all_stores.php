<?php
// views/all_stores.php

// -----------------------------------------------------------------
// PHP DEFINITIONS (Assuming these variables are passed by Controller)
// -----------------------------------------------------------------
// Define the Batangas locations for the filter
$batangas_locations = [
    'Agoncillo', 'Balayan', 'Balete', 'Bauan', 'Calaca', 'Calatagan', 
    'Cuenca', 'Ibaan', 'Laurel', 'Lemery', 'Lian', 'Lobo', 
    'Mabini', 'Malvar', 'Mataasnakahoy', 'Nasugbu', 'Padre Garcia', 'Pinagbayanan', 
    'Rosario', 'San Jose', 'San Juan', 'San Luis', 'San Nicolas', 'San Pascual', 
    'Santa Teresita', 'Santo Tomas', 'Taal', 'Talisay', 'Taysan', 'Tingloy', 'Tuy',
    'Batangas City', 'Lipa City', 'Tanauan City'
];
sort($batangas_locations);

$query = $_GET['query'] ?? null;
$is_search_page = !empty($query);

$store_results = $store_results ?? [];
$product_results = $product_results ?? [];
// -----------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Stores</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/assets/css/home.css">
    <link rel="stylesheet" href="public/assets/css/notification.css">
    <link rel="stylesheet" href="public/assets/css/all_store.css">
    <link rel="stylesheet" href="public/assets/css/search.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="icon" type="image/png" href="public/assets/default/icon/shop.png">
</head>
<body>

<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            LIYAG BATANGAN ALL STORE
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
                        <img src="<?= htmlspecialchars($userProfilePicture ? 'uploads/' . basename($userProfilePicture) : 'public/assets/default/default_profile.jpg') ?>" 
                             alt="Profile" 
                             class="profile-icon">
                    </div>
                </button>
                <div class="dropdown-content" id="dropdownMenu">
                    <a href="index.php?action=account">Account Management</a>
                    <a href="index.php?action=create_business">Start Selling</a>
                    <form method="POST" action="index.php?action=logout">
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                </div>
            </div>
        </nav>
    </div>

    <div class="search-wrapper">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Searc stores..." class="search-input" value="<?= $is_search_page ? htmlspecialchars($query) : '' ?>">
        
        <div id="searchResultsDropdown" class="search-results-dropdown">
            <div class="search-section">
                <h4><i class="bi bi-shop"></i> Stores</h4>
                <div class="results-list" id="storeResults">
                    <p class="no-results">Start typing to see results...</p>
                </div>
            </div>
            
            
        </div>
    </div>
</header>

<main>
    <div id="all-stores-map"></div>

    <div class="stores-container">
        <button id="view-stores-btn" class="floating-btn">View All Stores</button>

        <div class="stores-drawer" id="stores-drawer">
            <button id="close-stores-btn" class="close-btn" title="Close Stores">&times;</button>
            <h2 class="section-title">All Stores</h2>
            
            <div class="filter-controls custom-select-wrapper">
                <label for="location-filter">Filter by Location:</label>
                <select id="location-filter" class="custom-select">
                    <option value="all">All of Batangas</option>
                    <?php foreach ($batangas_locations as $location): ?>
                        <option value="<?= htmlspecialchars($location) ?>"><?= htmlspecialchars($location) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="store-grid-single" data-stores='<?php echo json_encode($all_approved_stores); ?>'>
                <?php if (!empty($all_approved_stores)): ?>
                    <?php foreach ($all_approved_stores as $store): ?>
                        <div class="store-card-large" 
                            data-vendor-id="<?php echo $store['vendor_id']; ?>"
                            data-location="<?php echo htmlspecialchars($store['business_address'] ?? 'Unknown'); ?>" 
                            style="background-image: url('<?php echo htmlspecialchars($store['logo_url'] ?? 'public/assets/default/default_store_logo.jpg'); ?>');">
                            <div class="store-info">
                                <h3><?php echo htmlspecialchars($store['business_name']); ?></h3>
                                <p class="store-address"><?php echo htmlspecialchars($store['business_address']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No approved stores to display yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="app/scripts/home.js"></script>
<script src="app/scripts/all_store.js"></script>
<script src="app/scripts/notification.js"></script>
<script src="app/scripts/search_store.js"></script>
</body>
</html>