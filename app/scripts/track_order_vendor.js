document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('vendorOrderModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const orderListContainer = document.querySelector('.order-list-container');
    const modalOrderTitle = document.getElementById('modalOrderTitle');
    const modalOrderDetails = document.getElementById('modalOrderDetails');
    const modalStatusTracker = document.getElementById('modalStatusTracker');
    const statusSteps = ['Pending', 'Approved', 'Shipped', 'Out for Delivery', 'Delivered'];
    const statusMessage = document.getElementById('statusMessage');

    // --- Modal Controls ---
    const closeModal = () => {
        modal.style.display = 'none';
        delete modal.dataset.currentOrderId;
    };

    closeModalBtn.addEventListener('click', closeModal);

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // --- Utility Functions ---

    // Function to update the visual status tracker in the modal
    const updateModalStatusTracker = (currentStatus) => {
        const currentStatusIndex = statusSteps.indexOf(currentStatus);

        document.querySelectorAll('#modalStatusTracker .status-step').forEach((stepEl, index) => {
            const link = stepEl.querySelector('.status-update-link');
            
            stepEl.classList.remove('active', 'completed');
            link.classList.remove('disabled');

            if (index < currentStatusIndex) {
                stepEl.classList.add('completed');
            } else if (index === currentStatusIndex) {
                stepEl.classList.add('active');
            }

            // Disable updating to a previous status or the current status
            if (index <= currentStatusIndex) {
                link.classList.add('disabled');
            }
        });
    };

    // Function to render the fetched order data into the modal body
    const renderOrderDetails = (order) => {
        const itemsHtml = order.items.map(item => `
            <div class="modal-item-row">
                <span class="item-name">${item.product_name}</span>
                <span class="item-qty">x${item.quantity}</span>
                <span class="item-price">₱${parseFloat(item.line_total).toFixed(2)}</span>
            </div>
        `).join('');

        modalOrderDetails.innerHTML = `
            <div class="order-summary-box">
                <p><strong>Customer:</strong> ${order.customer_name}</p>
                <p><strong>Contact:</strong> ${order.contact_number}</p>
                <p><strong>Date Placed:</strong> ${new Date(order.order_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
                <p><strong>Payment:</strong> ${order.payment_method}</p>
            </div>

            <h3>Delivery Address (${order.delivery_label})</h3>
            <p class="address-box">${order.delivery_full_address}</p>
            <p class="address-coords">Lat: ${order.delivery_lat || 'N/A'}, Lng: ${order.delivery_lng || 'N/A'}</p>
            
            <h3>Items from Your Store</h3>
            <div class="items-list-box">${itemsHtml}</div>
            
            <div class="order-totals-box">
                <p><strong>Shipping Fee:</strong> ₱${parseFloat(order.shipping_fee).toFixed(2)}</p>
                <p class="grand-total"><strong>TOTAL:</strong> ₱${parseFloat(order.order_total).toFixed(2)}</p>
            </div>
        `;
    };

    // --- AJAX Functions ---

    // **CRITICAL MISSING FUNCTION: Fetches the data for the modal**
    const fetchOrderDetails = async (orderId) => {
        modalOrderDetails.innerHTML = '<p class="loading-message"><i class="bi bi-arrow-clockwise"></i> Loading order details...</p>';
        statusMessage.textContent = ''; // Clear status message

        try {
            const response = await fetch(`index.php?action=get_vendor_order_details&order_id=${orderId}`);
            const data = await response.json();

          if (data.success && data.data) {
            const order = data.data;
            // Normalize property name so renderOrderDetails works
            order.items = order.order_items || [];
            renderOrderDetails(order);
            updateModalStatusTracker(order.order_status);
        } else {
                modalOrderDetails.innerHTML = `<p class="error-message"><i class="bi bi-x-octagon-fill"></i> ${data.message}</p>`;
                updateModalStatusTracker(statusSteps[0]); // Reset status display
            }
        } catch (error) {
            console.error('Error fetching order details:', error);
            modalOrderDetails.innerHTML = '<p class="error-message">Failed to connect to server.</p>';
        }
    };


    // Function to handle the AJAX status update
    async function updateOrderStatus(orderId, newStatus) {
        // Prevent updating if the new status is not an immediate next step
        const currentActiveLink = modalStatusTracker.querySelector('.status-step.active .status-update-link');
        const currentStatus = currentActiveLink ? currentActiveLink.dataset.status : '';
        if (statusSteps.indexOf(newStatus) <= statusSteps.indexOf(currentStatus)) return;


        if (!confirm(`Confirm change: Update Order #${orderId} status to "${newStatus}"?`)) return;

        statusMessage.textContent = `Updating status to **${newStatus}**...`;
        statusMessage.style.color = 'orange';

        try {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', newStatus); // Changed to 'status' to match controller logic
            // VENDOR_ID is not needed here as it's fetched from the session in the controller for security
            
            const response = await fetch('index.php?action=update_vendor_order_status', { 
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();

            if (result.success) {
                statusMessage.textContent = `Status updated to **${result.new_status}** successfully! Refreshing...`;
                statusMessage.style.color = 'green';
                // Update modal immediately for better UX
                updateModalStatusTracker(result.new_status);
                // Reload after a short delay to update the main list
                setTimeout(() => window.location.reload(), 1500); 
            } else {
                statusMessage.textContent = `Update failed: ${result.message}`;
                statusMessage.style.color = 'red';
            }
        } catch (error) {
            statusMessage.textContent = 'Network error during status update.';
            statusMessage.style.color = 'red';
            console.error('Update error:', error);
        }
    }


    // --- Event Listeners ---

    // Listener for the "View/Manage Order" button
    orderListContainer.addEventListener('click', async (e) => {
        if (e.target.classList.contains('view-order-details-btn')) {
            const orderId = e.target.dataset.orderId;
            
            modal.dataset.currentOrderId = orderId; 

            modal.style.display = 'block';
            modalOrderTitle.textContent = `Manage Order #${orderId}`;
            
            // **CALL THE FETCH FUNCTION HERE**
            fetchOrderDetails(orderId);
        }
    });
    
    // Listener for the clickable status steps in the modal
    modalStatusTracker.addEventListener('click', (event) => {
        const link = event.target.closest('.status-update-link');

        if (link && !link.classList.contains('disabled')) {
            event.preventDefault();

            const orderId = modal.dataset.currentOrderId;
            const newStatus = link.dataset.status;
            
            if (orderId && newStatus) {
                updateOrderStatus(orderId, newStatus);
            } else {
                statusMessage.textContent = 'Error: Order ID or new status is missing.';
                statusMessage.style.color = 'red';
            }
        }
    });
});