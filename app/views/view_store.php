    <?php
    // views/view_store.php

    // --- Ensure dependencies are available (Typically done in the Controller) ---
    if (!isset($user) || !isset($store)) {
        header("Location: index.php?action=login");
        exit;
    }

    if (!isset($products)) {
        $products = [];
    }

    $categoryMap = [
        1 => 'Food',
        2 => 'Beverages',
        3 => 'Souvenir',
        // Add more categories as needed
    ];

    // Use actual data from the $store array, assuming keys average_rating and total_reviews exist
    $averageRating = $average_rating ?? 0.0;
    $totalReviews = $total_reviews ?? 0;

    // --- 2. Calculate Product Distribution (for the pie chart) from $products (products table) ---
    $productCategoryCounts = [];
    $totalProductsForChart = 0;

    foreach ($products as $product) {
        $categoryId = $product['category_id'] ?? null;
        if ($categoryId !== null) {
            $categoryName = $categoryMap[$categoryId] ?? 'Other';
            
            if (!isset($productCategoryCounts[$categoryName])) {
                $productCategoryCounts[$categoryName] = 0;
            }
            $productCategoryCounts[$categoryName]++;
            $totalProductsForChart++;
        }
    }

    // Convert the counts into the required format for the Chart.js script
    $productCategories = [];
    foreach ($productCategoryCounts as $name => $count) {
        $productCategories[] = ['name' => $name, 'count' => $count];
    }

    // If no products, ensure default structure for chart data
    if (empty($productCategories)) {
        // Add an 'empty' category to show an empty state on the chart if needed, or leave it empty to show no chart
        // For this example, we'll keep it empty to reflect no data
    }


    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($store['business_name']); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <link rel="stylesheet" href="public/assets/css/home.css">
        <link rel="stylesheet" href="public/assets/css/store.css">
        <link rel="stylesheet" href="public/assets/css/responsive-home.css">
        <link rel="stylesheet" href="public/assets/css/notification.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link rel="icon" type="image/png" href="public/assets/default/icon/shop.png">
    </head>
    <body>

    <header class="header">
        <div class="header-top">
            <div class="profile-greeting">
            MY STORE: <?= htmlspecialchars($store['business_name']); ?>
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
                        <?php if (isset($user['user_type']) && $user['user_type'] === 'Vendor'): ?>
                            <a href="index.php?action=view_vendor_store">View Store</a>
                        <?php endif; ?>
                        <form method="POST" action="index.php?action=logout">
                            <button type="submit" class="logout-btn">Logout</button>
                        </form>
                    </div>
                </div>
            </nav>
        </div>
    </header>
     <section class="store-info-section">
            <div class="store-header-image-container">
                <img src="<?= htmlspecialchars($store['logo_url'] ?? 'public/assets/default/default_business_picture.jpg') ?>" 
                    alt="<?= htmlspecialchars($store['business_name']) ?>" 
                    class="business-picture">
                <div class="store-header-fade-overlay"></div>
                <div class="store-header-content">
                    <h2><?= htmlspecialchars($store['business_name']); ?></h2>
                    <p><?= htmlspecialchars($store['business_address']); ?></p>
                </div>
            </div>
        </section>

    <main class="store-page-main" data-vendor-id="<?= htmlspecialchars($store['vendor_id']); ?>">
       

        <section class="categories">
                <div class="category-grid">
                    <a href="#product" class="category-card">
                        <i class="bi bi-shop-window"></i><span>Products</span>
                    </a>
                    <a href="index.php?action=track_orders_vendor" class="category-card">
                        <i class="bi bi-receipt"></i><span>Orders</span>
                    </a>
                     <a href="#" id="openChatModalLink" class="category-card">
                        <i class="bi bi-envelope-fill"></i><span>Message</span>
                    </a>
            </section>

        <section class="vendor-analytics">
            <h2 class="section-title">Vendor Status</h2>
            <div class="analytics-grid">
                <div class="analytics-card">
                    <i class="bi bi-cash-stack icon-sales"></i>
                    <div class="analytics-info">
                        <p class="analytics-label">Total Sales</p>
                        <p class="analytics-value">₱<?= number_format($totalSales, 2); ?></p>
                    </div>
                </div>
                <div class="analytics-card">
                    <i class="bi bi-star-fill icon-ratings"></i>
                    <div class="analytics-info">
                        <p class="analytics-label">Ratings</p>
                        <p class="analytics-value">
                            <?= number_format($averageRating, 1); ?>/5.0 
                            <span class="review-count">(<?= number_format($totalReviews); ?> Reviews)</span>
                        </p>
                    </div>
                </div>
                <div class="analytics-card chart-card"> <p class="analytics-label">Product Distribution</p>
                    <canvas id="productPieChart"></canvas> </div>
            </div>
        </section>
        
    <section id="product">
            <div class="product-list-container">
            <div class="header-bar">
                <i class="bi bi-list"></i>
                <h1>Product List</h1>
            </div>
            
            <div class="product-filters">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="active">Active</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="low-stock">Low Stock</button>
                <button class="filter-btn" data-filter="inactive">Inactive</button>
            </div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        <?php if (empty($products)): ?>
            <tr id="noResultsMessage">
                <td colspan="5" style="text-align: center; padding: 20px; color: #888; font-style: italic;">
                    Your store has no products yet. Click "Add Product" to get started!
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($products as $product): 
                $product_json = htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8');
                $status_class = strtolower($product['status']);
                
                $quantity = $product['stock_quantity'] ?? 0;
                if ($quantity > 10) {
                    $stock_class = 'stock-high';
                } elseif ($quantity >= 5) {
                    $stock_class = 'stock-medium';
                } else {
                    $stock_class = 'stock-low';
                }

                $image_url = $product['image_url'] ?? 'public/assets/default/default_product.jpg';
                $background_style = "background-image: url('" . htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') . "');";
            ?>
            <tr data-product-id="<?= htmlspecialchars($product['product_id']); ?>" data-status="<?= $status_class; ?>">
                <td class="product-name-cell" style="<?= $background_style; ?>">
                    <div class="product-name-overlay"></div>
                    <span><?= htmlspecialchars($product['name']); ?></span>
                </td>
                <td>₱<?= number_format($product['price'], 2); ?></td>
                <td>
                    <span class="stock-indicator <?= $stock_class; ?>">
                        <?= htmlspecialchars($quantity); ?>
                    </span>
                </td>
                <td>
                    <span class="status-badge status-<?= $status_class; ?>">
                        <?= htmlspecialchars($product['status']); ?>
                    </span>
                </td>
                <td class="action-cell">
                    <button class="view-btn" 
                            onclick='openProductModal(<?= $product_json; ?>)'>
                        Manage
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <tr id="noResultsMessage" style="display:none;">
                <td colspan="5" style="text-align: center; padding: 20px; color: #888; font-style: italic;">
                    No products match the selected filter.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
            </table>
            </div>

            <button class="add-product-float-btn" id="addProductFloatBtn" title="Add New Product">
                <i class="bi bi-plus-lg"></i> Add Product
            </button>

            <div id="productModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn"></span>
                    <div class="modal-body">
                        <div class="modal-image-container">
                            <img id="modalProductImage" src="" alt="Product Image">
                        </div>
                        <div class="modal-details">
                            <h2 id="modalProductName"></h2>
                            <form id="updateProductForm">
                                <div class="form-group">
                                    <label for="productName">Product Name</label>
                                    <input type="text" id="productName" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="productDescription">Description</label>
                                    <textarea id="productDescription" name="description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="productPrice">Price</label>
                                    <input type="number" id="productPrice" name="price" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label for="productStock">Stock Quantity</label>
                                    <input type="number" id="productStock" name="stock_quantity" required>
                                </div>
                                <div class="form-group">
                                    <label for="productCategory">Category</label>
                                    <select id="productCategory" name="category_id" required>
                                        <?php foreach ($categoryMap as $id => $name): ?>
                                            <option value="<?= htmlspecialchars($id); ?>">
                                                <?= htmlspecialchars($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <input type="hidden" id="modalProductId" name="product_id">
                                <button type="submit" class="btn btn-primary">Update Product</button>
                                <button type="button" class="btn btn-danger" id="deleteProductBtn">Delete Product</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

    </section>
    <div id="addProductModal" class="modal">
            <div class="modal-content">
                
                <form id="addProductForm" enctype="multipart/form-data" class="modal-body modal-flex-container">
                    
                    <div class="modal-image-container" id="newProductImageContainer">
                        <label for="newProductImage" style="cursor: pointer; display: block; text-align: center;">
                            <img id="newProductImagePreview" src="public/assets/default/default_product.jpg" alt="Product Image Preview">
                            <p style="color: #666; font-weight: bold; margin-top: 10px;">Click to Upload Image</p>
                        </label>
                        <input type="file" id="newProductImage" name="image" accept="image/*" required style="display: none;">
                    </div>
                    
                    <div class="modal-details">
                        <h2>Add New Product</h2>
                        
                        <div class="form-group">
                            <label for="newProductName">Product Name</label>
                            <input type="text" id="newProductName" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="newProductDescription">Description</label>
                            <textarea id="newProductDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="newProductPrice">Price</label>
                            <input type="number" id="newProductPrice" name="price" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="newProductStock">Stock Quantity</label>
                            <input type="number" id="newProductStock" name="stock_quantity" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="newProductCategory">Category</label>
                            <select id="newProductCategory" name="category_id" required>
                                <option value="">Select Category</option>
                                <option value="1">Food</option>
                                <option value="2">Beverages</option>
                                <option value="3">Souvenir</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Add Product</button>
                    </div>
                </form>
        
            </div>
        </div>
         <div id="chatModal" class="modal chat-modal">
            <div class="chat-modal-content">
                <div class="chat-header">
                    <h4 id="chatHeaderTitle">Conversations</h4>
                    <span class="close-btn" id="closeChatModalBtn">&times;</span>
                </div>
                
                <div class="chat-body-grid">
                    <div id="threadListContainer" class="thread-list">
                        <p class="loading-threads">Loading conversations...</p>
                    </div>
                    
                    <div class="chat-main">
                        <div id="chatMessages" class="chat-messages">
                        </div> 
                        
                        <div class="chat-input">
                            <input type="text" id="messageInput" placeholder="Type a message..." aria-label="Message content">
                            <button id="sendMessageBtn">Send</button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </main>

    <footer class="footer">
    <div class="social-icons">
        <a href="https://www.facebook.com/people/Liyag-Batangan/61583172300285/"><i class="bi bi-facebook"></i></a>
        <a href="https://www.instagram.com/liyag.batangan"><i class="bi bi-instagram"></i></a>
    </div>
    <p class="copyright">&copy; <?= date('Y') ?> Liyag Batangan. All rights reserved.</p>
</footer>


    <script src="app/scripts/home.js"></script>
    <script src="app/scripts/store.js"></script>
    <script src="app/scripts/notification.js"></script>

    <script>
        // Live image preview and click-to-upload for the Add Product modal
        document.addEventListener('DOMContentLoaded', () => {
            const newProductImageContainer = document.getElementById('newProductImageContainer');
            const newProductImageInput = document.getElementById('newProductImage');
            const newProductImagePreview = document.getElementById('newProductImagePreview');

            if (newProductImageContainer && newProductImageInput && newProductImagePreview) {
                // Trigger file input click when the container is clicked
                newProductImageContainer.addEventListener('click', () => {
                    newProductImageInput.click();
                });

                // Live image preview
                newProductImageInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            newProductImagePreview.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        newProductImagePreview.src = 'public/assets/default/default_product.jpg'; // Reset to default
                    }
                });
            }

            // --- Chart.js for Product Distribution Pie Chart ---
            const ctx = document.getElementById('productPieChart');
            if (ctx) {
                // Pass PHP data to JavaScript
                const productCategoriesData = <?= json_encode($productCategories); ?>;
                const labels = productCategoriesData.map(cat => cat.name);
                const data = productCategoriesData.map(cat => cat.count);

                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: ['#FFD700', '#DAA520', '#B8860B', '#F0E68C', '#EEDD82', '#CDAA7D', '#FFE4B5'],
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Allows you to control aspect ratio with CSS
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: {
                                        size: 14,
                                        family: 'Poppins'
                                    }
                                }
                            },
                            title: {
                                display: false,
                                text: 'Product Category Distribution'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed !== null) {
                                            // Calculate percentage for the tooltip
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) + '%' : '0%';
                                            label += context.parsed + ' (' + percentage + ')';
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    </body>
    </html>