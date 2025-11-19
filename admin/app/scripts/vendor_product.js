document.addEventListener('DOMContentLoaded', () => {
    // Existing elements
    const modal = document.getElementById('product-details-modal');
    const closeBtn = document.querySelector('.close-btn');
    const modalContentPlaceholder = document.getElementById('modal-content-placeholder');
    const modalActions = document.getElementById('modal-actions');

    // NEW Lottie elements
    const lottieModal = document.getElementById('lottie-success-modal');
    const lottieContainer = document.getElementById('lottie-success-animation');

    // 1. Initialize Lottie Animation (Use a generic success/check animation JSON URL)
    // NOTE: Replace this with the actual URL of your Lottie JSON file!
    const lottieAnimation = lottie.loadAnimation({
        container: lottieContainer, 
        renderer: 'svg',
        loop: false,
        autoplay: false, // Don't play until success
        path: 'public/assets/lotties/check.json' // Example Success Checkmark
    });


    // Existing functions
    const closeModal = () => {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    };

    window.closeLottieModal = () => {
        lottieModal.classList.remove('show');
        lottieAnimation.stop();
    }
    
    // NEW function to show the Lottie modal
    const showLottieSuccessModal = (status) => {
        document.getElementById('lottie-modal-title').textContent = status === 'Active' ? 'Product Approved!' : 'Product Rejected!';
        lottieModal.classList.add('show');
        lottieAnimation.goToAndPlay(0, true);
    }
    // End existing functions setup

    closeBtn.onclick = closeModal;
    window.onclick = (event) => {
        if (event.target == modal) {
            closeModal();
        }
        if (event.target == lottieModal) {
            closeLottieModal();
        }
    };
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (modal.classList.contains('active')) {
                closeModal();
            } else if (lottieModal.classList.contains('show')) {
                closeLottieModal();
            }
        }
    });

    document.querySelector('.admin-table-section').addEventListener('click', (event) => {
        const btn = event.target.closest('.view-details-btn');
        if (btn) {
            const productId = btn.getAttribute('data-product-id');
            showModal(productId);
        }
    });

    // ... (showModal function remains the same)
    const showModal = (productId) => {
        modalContentPlaceholder.innerHTML = '<div class="loading-spinner"></div>';
        modalActions.innerHTML = '';
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('active');
        }, 10);


        fetch(`index.php?action=getProductDetails&product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.product) {
                    const p = data.product;

                    const formattedPrice = '₱' + parseFloat(p.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    const contentHtml = `
                        <h2>${p.name}</h2>
                        <div class="product-details-grid">
                            <div class="product-image-box">
                                <img src="${p.image_url || 'public/assets/images/placeholder.png'}" alt="${p.name}">
                                <div class="detail-item" style="margin-top: 15px;">
                                    <strong>Vendor Name</strong>
                                    <p>${p.vendor_name || 'N/A'}</p>
                                </div>
                            </div>
                            <div class="product-info">
                                <div class="detail-item description">
                                    <strong>Description</strong>
                                    <p>${p.description}</p>
                                </div>
                                <div class="detail-item">
                                    <strong>Price</strong>
                                    <p class="text-gold">${formattedPrice}</p>
                                </div>
                                <div class="detail-item">
                                    <strong>Stock Quantity</strong>
                                    <p>${p.stock_quantity}</p>
                                </div>
                                <div class="detail-item">
                                    <strong>Category</strong>
                                    <p>${p.category_name || 'N/A'}</p>
                                </div>
                                <div class="detail-item">
                                    <strong>Current Status</strong>
                                    <span id="modal-status-tag-${p.product_id}" class="status-tag status-${p.status.toLowerCase().replace(' ', '-')}">${p.status}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    modalContentPlaceholder.innerHTML = contentHtml;

                    let actionsHtml = `<button class="btn btn-secondary-outline" onclick="closeModal()">Close</button>`;
                    if (p.status === 'Pending') {
                        actionsHtml += `
                            <button data-product-id="${productId}" data-new-status="Active" class="btn btn-success product-action-btn"><i class="bi bi-check-lg"></i> Approve Product</button>
                            <button data-product-id="${productId}" data-new-status="Rejected" class="btn btn-danger product-action-btn"><i class="bi bi-x-lg"></i> Reject Product</button>
                        `;
                    }
                    modalActions.innerHTML = actionsHtml;

                } else {
                    modalContentPlaceholder.innerHTML = '<p class="text-danger">Error: Could not load product details.</p>';
                    modalActions.innerHTML = `<button class="btn btn-secondary-outline" onclick="closeModal()">Close</button>`;
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                modalContentPlaceholder.innerHTML = '<p class="text-danger">An unexpected error occurred while fetching details.</p>';
                modalActions.innerHTML = `<button class="btn btn-secondary-outline" onclick="closeModal()">Close</button>`;
            });
    };
    // ... (End showModal function)

    modalActions.addEventListener('click', (event) => {
        const btn = event.target.closest('.product-action-btn');
        if (btn) {
            const productId = btn.getAttribute('data-product-id');
            const newStatus = btn.getAttribute('data-new-status');

            modalActions.querySelectorAll('button').forEach(b => b.disabled = true);

            updateProductStatus(productId, newStatus, btn);
        }
    });

    const updateProductStatus = (productId, newStatus, buttonElement) => {
        const action = newStatus === 'Active' ? 'approveProduct' : 'rejectProduct';

        fetch(`index.php?action=${action}&product_id=${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 1. Update the status tag in the main product table
                const tableRow = document.querySelector(`[data-product-id="${productId}"]`).closest('tr');
                if (tableRow) {
                    const statusTag = tableRow.querySelector('.status-tag');
                    if (statusTag) {
                        statusTag.textContent = data.new_status;
                        statusTag.className = `status-tag status-${data.new_status.toLowerCase().replace(' ', '-')}`;
                    }
                }

                // 2. Close the product details modal
                closeModal(); 
                
                // 3. SHOW THE LOTTIE SUCCESS MODAL
                showLottieSuccessModal(data.new_status);

            } else {
                alert('Failed to update product status: ' + (data.message || 'Unknown error.'));
                modalActions.querySelectorAll('button').forEach(b => b.disabled = false);
            }
        })
        .catch(error => {
            console.error('Product status update error:', error);
            alert('An error occurred during the product status update.');
            modalActions.querySelectorAll('button').forEach(b => b.disabled = false);
        });
    };

    window.closeModal = closeModal;
    window.closeLottieModal = closeLottieModal; // Make it globally accessible for the button
});