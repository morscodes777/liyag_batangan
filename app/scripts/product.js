document.addEventListener("DOMContentLoaded", () => {
    // --- Profile Dropdown ---
    const profileBtn = document.getElementById("profileBtn");
    const dropdownMenu = document.getElementById("dropdownMenu");

    // --- Notification Dropdown ---
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationModal = document.getElementById('notificationModal');

    // Toggle function for both dropdowns to keep the logic clean
    function toggleDropdown(button, dropdown) {
        if (dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
        } else {
            // Close other dropdowns
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
            toggleDropdown(profileBtn, dropdownMenu);
        });
    }

    if (notificationBtn && notificationModal) {
        notificationBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDropdown(notificationBtn, notificationModal);
        });
    }

    // --- Global Click Handler to Close Both Menus ---
    document.addEventListener("click", (e) => {
        if (dropdownMenu && !dropdownMenu.contains(e.target) && !e.target.closest('#profileBtn')) {
            dropdownMenu.style.display = "none";
        }
        if (notificationModal && !notificationModal.contains(e.target) && !e.target.closest('#notificationBtn')) {
            notificationModal.style.display = "none";
        }
    });

    // --- Logout Modal ---
    const logoutModal = document.getElementById("logoutModal");
    const confirmLogout = document.getElementById("confirmLogoutBtn");
    const cancelLogout = document.getElementById("cancelLogoutBtn");
    const logoutLink = document.querySelector('.logout-btn');

    if (logoutLink && logoutModal) {
        logoutLink.addEventListener("click", (e) => {
            e.preventDefault(); 
            logoutModal.style.display = 'flex';
        });

        confirmLogout.addEventListener("click", () => {
            logoutLink.closest('form').submit();
        });

        cancelLogout.addEventListener("click", () => {
            logoutModal.style.display = 'none';
        });
    }
    
    // --- Product Modal ---
    const productCards = document.querySelectorAll('.product-card');
    const productModal = document.getElementById('productModal');
    const closeModalButton = productModal ? productModal.querySelector('.close-button') : null;

    if (productCards.length > 0 && productModal) {
        productCards.forEach(card => {
            card.addEventListener('click', (e) => {
                e.preventDefault(); // Stop the link from navigating
                const productData = JSON.parse(card.dataset.product);

                // Populate modal with product data
                document.getElementById('modal-product-image').src = productData.image_url || 'uploads/products/default_product.jpg';
                document.getElementById('modal-product-name').textContent = productData.name;
                document.getElementById('modal-product-description').textContent = productData.description;
                document.getElementById('modal-product-price').textContent = `₱${parseFloat(productData.price).toFixed(2)}`;
                
                // Display the modal
                productModal.style.display = 'block';
            });
        });
    }

    // Close the product modal
    if (productModal && closeModalButton) {
        closeModalButton.addEventListener('click', () => {
            productModal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === productModal) {
                productModal.style.display = 'none';
            }
        });
    }
});