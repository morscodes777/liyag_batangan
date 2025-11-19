let mainMap;
let modalMap;
let isModalMapInitialized = false;

function generateStarHTML(rating, totalReviews) {
    const avgRating = parseFloat(rating) || 0;
    const total = parseInt(totalReviews) || 0;
    const roundedRating = Math.round(avgRating * 2) / 2;
    let starsHtml = `<div class="rating-stars" title="${avgRating.toFixed(1)} stars">`;
    
    for (let i = 1; i <= 5; i++) {
        let iconClass = 'bi-star';
        
        if (i <= roundedRating) {
            iconClass = 'bi-star-fill';
        } else if (i - 0.5 === roundedRating) {
            iconClass = 'bi-star-half';
        }
        
        starsHtml += `<i class="star-icon ${iconClass}"></i>`;
    }
    
    starsHtml += `</div><span class="review-count">(${total.toLocaleString()} Reviews)</span>`;
    
    return starsHtml;
}

function openProductModal(productData) {
    const productModal = document.getElementById('productModal');
    const modalQuantityInput = document.getElementById('modal-product-quantity');
    const modalStoreName = document.getElementById('modal-product-store');
    const modalStoreAddress = document.getElementById('modal-store-address');
    const modalStoreLink = document.getElementById('modal-store-link');
    const modalRatingDisplay = document.getElementById('modal-product-rating-display');
    
    const imageUrl = productData.image_url 
        ? (productData.image_url.startsWith('uploads/') ? productData.image_url : `uploads/products/${productData.image_url}`)
        : 'uploads/products/default_product.jpg';
        
    document.getElementById('modal-product-image').src = imageUrl;
    document.getElementById('modal-product-name').textContent = productData.name;
    document.getElementById('modal-product-description').textContent = productData.description;
    document.getElementById('modal-product-price').textContent = `₱${parseFloat(productData.price).toFixed(2)}`;
    document.getElementById('modal-product-id').value = productData.product_id;
    
    const storeName = productData.business_name || 'N/A';
    const storeAddress = productData.business_address || 'N/A';
    const vendorId = productData.vendor_id || '';
    const avgRating = productData.average_rating || 0;
    const totalReviews = productData.total_reviews || 0;

    modalStoreName.textContent = storeName;
    modalStoreAddress.innerHTML = `<i class="bi bi-geo-alt-fill"></i> ${storeAddress}`;
    
    modalStoreLink.href = vendorId ? `index.php?action=view_store&vendor_id=${vendorId}` : '#';
    modalStoreLink.style.pointerEvents = vendorId ? 'auto' : 'none';
    
    modalRatingDisplay.innerHTML = generateStarHTML(avgRating, totalReviews);
    
    if (modalQuantityInput) {
        modalQuantityInput.value = 1;
    }
    
    if (productModal) {
        productModal.classList.add('open');
        productModal.style.display = 'block'; 
    }
} 

document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById('searchInput');
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');
    
    const profileBtn = document.getElementById("profileBtn");
    const dropdownMenu = document.getElementById("dropdownMenu");
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationModal = document.getElementById('notificationModal');

    function toggleDropdown(dropdown) {
        if (dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
        } else {
            const allDropdowns = [dropdownMenu, notificationModal];
            allDropdowns.forEach(modal => {
                if (modal && modal !== dropdown) {
                    modal.style.display = 'none';
                }
            });
            dropdown.style.display = 'block';
        }
    }

    if (profileBtn && dropdownMenu) {
        profileBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            toggleDropdown(dropdownMenu);
        });
    }

    if (notificationBtn && notificationModal) {
        notificationBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDropdown(notificationModal);
        });
    }

   document.addEventListener("click", (e) => {
        // Existing code for profile and notification dropdowns...
        if (dropdownMenu && !dropdownMenu.contains(e.target) && !e.target.closest('#profileBtn')) {
            dropdownMenu.style.display = "none";
        }
        if (notificationModal && !notificationModal.contains(e.target) && !e.target.closest('#notificationBtn')) {
            notificationModal.style.display = "none";
        }
        
        // Updated: Add modal check to prevent hiding dropdown when clicking inside the product modal
        const productModal = document.getElementById('productModal');
        if (searchResultsDropdown && searchInput && !searchResultsDropdown.contains(e.target) && e.target !== searchInput && (!productModal || !productModal.contains(e.target))) {
            searchResultsDropdown.classList.remove('active');
            searchResultsDropdown.style.display = 'none'; 
        }
    });


    const isSearchPage = document.body.classList.contains('search-results-active');
    const storeContainer = document.querySelector('.store-grid-single');
    
    if (!isSearchPage && storeContainer && storeContainer.dataset.stores) {
        try {
            const approvedStores = JSON.parse(storeContainer.dataset.stores);

            if (approvedStores.length > 0) {
                initializeMainMap(approvedStores);
            }
        } catch (error) {
            console.error("Failed to parse store data:", error);
        }
    } else if (!isSearchPage) {
        console.error("Store container or data-stores attribute not found. Map not initialized.");
    }
    
    const logoutModal = document.getElementById("logoutModal");
    const confirmLogout = document.getElementById("confirmLogoutBtn");
    const cancelLogout = document.getElementById("cancelLogoutBtn");
    const logoutButton = document.querySelector('.profile-dropdown .logout-btn'); 

    if (logoutButton && logoutModal) {
        logoutButton.addEventListener("click", (e) => {
            e.preventDefault(); 
            const logoutForm = e.target.closest('form'); 
            if (logoutForm) {
                confirmLogout.onclick = () => logoutForm.submit();
            }
            logoutModal.style.display = 'flex';
        });

        cancelLogout.addEventListener("click", () => {
            logoutModal.style.display = 'none';
        });
    }

    const productCards = document.querySelectorAll('.product-grid .product-card'); 
    const productModal = document.getElementById('productModal');
    const closeModalButton = productModal ? productModal.querySelector('.close-button') : null; 
    const decreaseQuantityBtn = document.getElementById('decrease-quantity');
    const increaseQuantityBtn = document.getElementById('increase-quantity');
    const modalQuantityInput = document.getElementById('modal-product-quantity');
    const addToCartForm = document.getElementById('modal-add-to-cart-form');

    
    if (productCards.length > 0 && productModal) {
        productCards.forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.classList.contains('view-btn')) {
                    e.stopPropagation(); 
                    
                    try {
                        const productData = JSON.parse(card.dataset.product);
                        openProductModal(productData); 
                    } catch (error) {
                        console.error("Failed to parse product data from card button click:", error);
                    }
                    return;
                }
                
                try {
                    const productData = JSON.parse(card.dataset.product);
                    openProductModal(productData); 
                } catch (error) {
                    console.error("Failed to parse product data:", error);
                }
            });
        });
    }

    if (productModal && closeModalButton) {
        closeModalButton.addEventListener('click', () => {
            productModal.classList.remove('open');
            setTimeout(() => {
                productModal.style.display = 'none';
                if (searchInput && searchInput.value.trim().length > 0) {
                    searchInput.focus(); 
                }
            }, 300);
        });

        window.addEventListener('click', (e) => {
            if (e.target === productModal) {
                productModal.classList.remove('open');
                setTimeout(() => {
                    productModal.style.display = 'none';
                    if (searchInput && searchInput.value.trim().length > 0) {
                        searchInput.focus();
                    }
                }, 300);
            }
        });
    }

    if (decreaseQuantityBtn && increaseQuantityBtn && modalQuantityInput) {
        decreaseQuantityBtn.addEventListener('click', () => {
            let quantity = parseInt(modalQuantityInput.value);
            if (!isNaN(quantity) && quantity > 1) {
                modalQuantityInput.value = quantity; 
            }
        });

        increaseQuantityBtn.addEventListener('click', () => {
            let quantity = parseInt(modalQuantityInput.value);
            if (!isNaN(quantity)) {
                modalQuantityInput.value = quantity; 
            }
        });
    }
    
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', async function(event) {
            event.preventDefault(); 

            const form = this;
            const formData = new FormData(form);
            
            let submitButton = form.querySelector('.add-to-cart-btn-v2') || form.querySelector('.add-to-cart-btn');
            const modalQuantityInput = document.getElementById('modal-product-quantity');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Adding...'; 

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new URLSearchParams(formData), 
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                });

                const result = await response.json(); 
                if (response.ok && result.success) {
                    
                    productModal.classList.remove('open');
                    setTimeout(() => {
                        productModal.style.display = 'none';
                    }, 300); 
                    
                    const successModal = document.getElementById('cartSuccessLottieModal');
                    const lottiePlayer = document.getElementById('lottieCartSuccess');

                    if (successModal && lottiePlayer) {
                        
                        if (typeof lottiePlayer.play === 'function') {
                            
                            successModal.style.display = 'flex';
                            
                            lottiePlayer.seek("0%"); 
                            lottiePlayer.play();
                            
                            setTimeout(() => {
                                successModal.style.display = 'none';
                            }, 2000); 
                            
                        } else {
                            alert('✅ Item added to cart successfully!');
                        }

                    } else {
                        alert('✅ Item added to cart successfully!');
                    }

                } else {
                    alert('⚠️ Error adding item to cart: ' + (result.message || 'Server returned an unknown error.'));
                }

            } catch (error) {
                console.error('AJAX Network Error:', error);
                alert('🛑 An unexpected network error occurred. Please try again.');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-cart-plus-fill"></i> Add to Cart';
                modalQuantityInput.value = 1; 
            }
        });
    }
});

function initializeMainMap(stores) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const userLatLng = [position.coords.latitude, position.coords.longitude];
                createMap(userLatLng, stores);
            },
            function() {
                createMap([13.7565, 121.0583], stores);
            }
        );
    } else {
        createMap([13.7565, 121.0583], stores);
    }
}

function createMap(centerLatLng, stores) {
    const mapOptions = {
        center: centerLatLng,
        zoom: 13,
        dragging: false,
        touchZoom: false,
        doubleClickZoom: false,
        scrollWheelZoom: false,
        boxZoom: false,
        keyboard: false,
        zoomControl: false
    };

    mainMap = L.map('store-map', mapOptions);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(mainMap);

    const redIcon = L.icon({
    iconUrl: 'data:image/svg+xml;charset=UTF-8,<svg width="24" height="41" viewBox="0 0 24 41" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g transform="translate(-309.000000, -420.000000)"><g transform="translate(309.000000, 420.000000)"><path d="M12,40 C12,40 24,28.892 24,19.9882353 C24,13.3137085 18.627417,8 12,8 C5.372583,8 0,13.3137085 0,19.9882353 C0,28.892 12,40 12,40 Z" fill="%23E00000"></path><path d="M12,23 C9.23857625,23 7,20.7614237 7,18 C7,15.2385763 9.23857625,13 12,13 C14.7614237,13 17,15.2385763 17,18 C17,20.7614237 14.7614237,23 12,23 Z" fill="%23FFFFFF"></path></g></g></g></svg>',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [0, -38] 
});

    L.marker(centerLatLng, {icon: redIcon})
        .addTo(mainMap)
        .bindTooltip("<b>Your Location</b>", { 
            permanent: true,
            direction: 'bottom',
            offset: [0, 1]
        })

    const orangeIcon = L.icon({
        iconUrl: 'data:image/svg+xml;charset=UTF-8,<svg width="24" height="41" viewBox="0 0 24 41" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g transform="translate(-309.000000, -420.000000)"><g transform="translate(309.000000, 420.000000)"><path d="M12,40 C12,40 24,28.892 24,19.9882353 C24,13.3137085 18.627417,8 12,8 C5.372583,8 0,13.3137085 0,19.9882353 C0,28.892 12,40 12,40 Z" fill="%23FFA500"></path><path d="M12,23 C9.23857625,23 7,20.7614237 7,18 C7,15.2385763 9.23857625,13 12,13 C14.7614237,13 17,15.2385763 17,18 C17,20.7614237 14.7614237,23 12,23 Z" fill="%23FFFFFF"></path></g></g></g></svg>',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [0, -38]
    });

    stores.forEach(store => {
        if (store.latitude && store.longitude) {
            const marker = L.marker([store.latitude, store.longitude], {icon: orangeIcon}).addTo(mainMap);
            
            marker.bindTooltip(`
                <i class="bi bi-shop" style="color: orange; font-size: 10px;"></i> 
                ${store.business_name}
            `, { 
                permanent: true, 
                direction: 'top', 
                offset: [0, -25],
                className: 'store-tooltip'
            }).openTooltip();

            const popupContent = `
                <b><i class="bi bi-shop"></i> ${store.business_name}</b><br>
                ${store.business_address}<br>
                <a 
                    href="index.php?action=view_store&vendor_id=${store.vendor_id}" 
                    style="
                        display: inline-block;
                        margin-top: 10px;
                        padding: 8px 15px;
                        background-color: #FFD700;
                        color: #333;
                        text-decoration: none;
                        border-radius: 5px;
                        font-weight: bold;
                        border: 1px solid #CCAC00;
                        transition: background-color 0.2s ease;
                    "
                    onmouseover="this.style.backgroundColor='#FFA500'; this.style.color='#FFF';" 
                    onmouseout="this.style.backgroundColor='#FFD700'; this.style.color='#333';"
                >
                    View Store
                </a>
            `;
            marker.bindPopup(popupContent);
        }
    });
}