document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('vendorOrderModal');
    const closeBtn = modal.querySelector('.close-btn');
    const orderDetailsContainer = document.getElementById('modalOrderDetails');
    const statusForm = document.getElementById('updateOrderStatusForm');
    const statusSelect = document.getElementById('newOrderStatus');
    const statusMsg = document.getElementById('statusMessage');

    // Function to open the modal and load order data
    document.querySelectorAll('.view-order-details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            modal.style.display = 'block';
            document.getElementById('modalOrderTitle').textContent = `Manage Order #${orderId}`;
            statusForm.setAttribute('data-order-id', orderId);
            statusMsg.textContent = ''; 

            // 1. Fetch Order Details (requires a new AJAX handler in your router/controller)
            fetch(`index.php?action=get_order_details_vendor&order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.order && data.items) {
                        // Update select input with current status
                        statusSelect.value = data.order.order_status;

                        // Build the order details HTML
                        let itemsHtml = data.items.map(item => `
                            <li>${item.product_name} (x${item.quantity}) @ ₱${parseFloat(item.unit_price).toFixed(2)}</li>
                        `).join('');

                        const detailsHtml = `
                            <h3>Customer: ${data.order.customer_name}</h3>
                            <p><strong>Address:</strong> ${data.order.delivery_street}, ${data.order.city}, ${data.order.province}</p>
                            <p><strong>Payment:</strong> ${data.order.payment_method}</p>
                            <h4>Items Ordered:</h4>
                            <ul class="item-list">${itemsHtml}</ul>
                            <p class="order-summary"><strong>Order Total:</strong> ₱${parseFloat(data.order.order_total).toFixed(2)}</p>
                            <p class="order-summary"><strong>Shipping Fee:</strong> ₱${parseFloat(data.order.shipping_fee).toFixed(2)}</p>
                            <p class="order-summary total"><strong>Grand Total:</strong> ₱${(parseFloat(data.order.order_total) + parseFloat(data.order.shipping_fee)).toFixed(2)}</p>
                        `;
                        orderDetailsContainer.innerHTML = detailsHtml;
                    } else {
                        orderDetailsContainer.innerHTML = `<p class="error-msg">Error: Could not load order details.</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching order details:', error);
                    orderDetailsContainer.innerHTML = `<p class="error-msg">Error: Failed to connect to server.</p>`;
                });
        });
    });

    // Close modal listeners
    closeBtn.onclick = () => modal.style.display = 'none';
    window.onclick = (event) => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };

    // 2. Handle Status Update Form Submission (requires another AJAX handler)
    statusForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const orderId = this.getAttribute('data-order-id');
        const newStatus = statusSelect.value;
        
        statusMsg.textContent = 'Updating status...';
        
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('new_status', newStatus);

        fetch('index.php?action=update_order_status', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusMsg.textContent = `Status updated to ${newStatus}! Refreshing...`;
                // Simple refresh to update the list
                setTimeout(() => {
                    window.location.reload(); 
                }, 1000);
            } else {
                statusMsg.textContent = `Error: ${data.message || 'Failed to update status.'}`;
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            statusMsg.textContent = 'Network error. Failed to update status.';
        });
    });
});