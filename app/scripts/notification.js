document.addEventListener('DOMContentLoaded', function() {
    const notificationDropdown = document.querySelector('.notification-dropdown');
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationModal = document.getElementById('notificationModal');
    const notificationList = document.getElementById('notificationList');
    const notificationBadge = document.getElementById('notificationBadge');
    const userId = notificationDropdown ? notificationDropdown.getAttribute('data-user-id') : null;

    // --- Notification Handlers ---

    function formatTimeAgo(timestamp) {
        const now = new Date();
        const past = new Date(timestamp);
        const diff = now - past;
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (seconds < 60) return `${seconds}s ago`;
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        return past.toLocaleDateString();
    }

    function renderNotifications(notifications) {
        notificationList.innerHTML = '';
        if (!Array.isArray(notifications) || notifications.length === 0) {
            notificationList.innerHTML = '<p class="no-notifications">No new notifications.</p>';
            notificationBadge.style.display = 'none';
            return;
        }

        let unreadCount = 0;
        notifications.forEach(notif => {
            if (parseInt(notif.is_read) === 0) unreadCount++;

            const notifItem = document.createElement('div');
            notifItem.classList.add('notification-item');
            if (parseInt(notif.is_read) === 0) notifItem.classList.add('unread');
            notifItem.setAttribute('data-id', notif.notification_id);
            notifItem.innerHTML = `
                <div class="notif-header">
                    <h5 class="notif-title">${notif.title}</h5>
                    <span class="notif-time">${formatTimeAgo(notif.created_at)}</span>
                </div>
                <div class="notif-message-container">
                    <p class="notif-message-short">${notif.message.substring(0, 50)}${notif.message.length > 50 ? '...' : ''}</p>
                    <p class="notif-message-full" style="display:none;">${notif.message}</p>

                </div>
            `;
            notificationList.appendChild(notifItem);
        });

        if (unreadCount > 0) {
            notificationBadge.textContent = unreadCount;
            notificationBadge.style.display = 'block';
        } else {
            notificationBadge.style.display = 'none';
        }

        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', handleNotificationClick);
        });
        document.querySelectorAll('.notif-delete-btn').forEach(btn => {
            btn.addEventListener('click', handleDeleteClick);
        });
    }

    async function fetchNotifications() {
        if (!userId) return;
        
        try {
            const url = `index.php?action=api_get_notifications&user_id=${userId}`;
            const response = await fetch(url);
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP Error ${response.status}: Failed to fetch notifications. Server response: ${errorText.substring(0, 100)}...`);
            }

            const data = await response.json();
            
            if (data && data.error) {
                throw new Error(`API Error: ${data.error}`);
            }

            renderNotifications(data);

        } catch (error) {
            console.error('Error fetching notifications:', error);
            // This displays a user-friendly error in the list
            notificationList.innerHTML = `<p class="no-notifications">Failed to load notifications. Please check the network or server logs. (${error.message || 'Network error'})</p>`;
        }
    }

    function markAsRead(notificationId) {
        fetch(`index.php?action=api_mark_notification_read&id=${notificationId}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.notification-item[data-id='${notificationId}']`);
                if (item) item.classList.remove('unread');
                const currentCount = parseInt(notificationBadge.textContent || 0);
                if (currentCount > 0) {
                    const newCount = currentCount - 1;
                    notificationBadge.textContent = newCount;
                    if (newCount === 0) notificationBadge.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }

    function deleteNotification(notificationId) {
        fetch(`index.php?action=api_delete_notification&id=${notificationId}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.notification-item[data-id='${notificationId}']`);
                if (item) item.remove();
                
                if (item && item.classList.contains('unread')) {
                     const currentCount = parseInt(notificationBadge.textContent || 0);
                     if (currentCount > 0) {
                         const newCount = currentCount - 1;
                         notificationBadge.textContent = newCount;
                         if (newCount === 0) notificationBadge.style.display = 'none';
                     }
                }
                
                if (notificationList.children.length === 0) {
                    notificationList.innerHTML = '<p class="no-notifications">No new notifications.</p>';
                }
            } else {
                alert('Failed to delete notification.');
            }
        })
        .catch(error => console.error('Error deleting notification:', error));
    }

    function handleNotificationClick(event) {
        const item = event.currentTarget;
        const notificationId = item.getAttribute('data-id');

        if (event.target.closest('.notif-delete-btn')) {
            return;
        }

        const fullMessage = item.querySelector('.notif-message-full');
        const shortMessage = item.querySelector('.notif-message-short');
        const deleteBtn = item.querySelector('.notif-delete-btn');
        
        const isExpanded = fullMessage.style.display === 'block';

        if (isExpanded) {
            fullMessage.style.display = 'none';
            shortMessage.style.display = 'block';
            deleteBtn.style.display = 'none';
            item.classList.remove('expanded');
        } else {
            document.querySelectorAll('.notification-item.expanded').forEach(expandedItem => {
                if (expandedItem !== item) {
                    expandedItem.classList.remove('expanded');
                    expandedItem.querySelector('.notif-message-full').style.display = 'none';
                    expandedItem.querySelector('.notif-message-short').style.display = 'block';
                    expandedItem.querySelector('.notif-delete-btn').style.display = 'none';
                }
            });

            fullMessage.style.display = 'block';
            shortMessage.style.display = 'none';
            deleteBtn.style.display = 'block';
            item.classList.add('expanded');

            if (item.classList.contains('unread')) {
                markAsRead(notificationId);
            }
        }
    }

    function handleDeleteClick(event) {
        event.stopPropagation();
        const notificationId = event.currentTarget.getAttribute('data-id');
        if (confirm('Are you sure you want to delete this notification?')) {
            deleteNotification(notificationId);
        }
    }

    // --- Global Event Listeners ---

    if (notificationBtn) {
        notificationBtn.addEventListener('click', function(event) {
            event.stopPropagation();
            notificationModal.classList.toggle('active');
            if (notificationModal.classList.contains('active')) {
                fetchNotifications(); 
            }
        });
    }

    window.addEventListener('click', function(event) {
        if (notificationDropdown && !notificationDropdown.contains(event.target)) {
            notificationModal.classList.remove('active');
        }
    });

    // --- Existing Home/Search/Modal Logic (Keep this) ---
    
    const productCards = document.querySelectorAll('.product-card');
    const productModal = document.getElementById('productModal');
    const modalCloseBtn = productModal ? productModal.querySelector('.close-button') : null;
    const qtyInput = document.getElementById('modal-product-quantity');
    const decreaseBtn = document.getElementById('decrease-quantity');
    const increaseBtn = document.getElementById('increase-quantity');

    productCards.forEach(card => {
        card.addEventListener('click', function() {
            const productData = JSON.parse(this.getAttribute('data-product'));
            
            document.getElementById('modal-product-image').src = productData.image_url || 'uploads/products/default_product.jpg';
            document.getElementById('modal-product-name').textContent = productData.name;
            document.getElementById('modal-product-description').textContent = productData.description;
            document.getElementById('modal-product-price').textContent = `₱${parseFloat(productData.price).toFixed(2)}`;
            document.getElementById('modal-product-id').value = productData.product_id;
            qtyInput.value = 1;

            productModal.style.display = 'flex';
        });
    });

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', () => {
            productModal.style.display = 'none';
        });
    }

    if (decreaseBtn && qtyInput) {
        decreaseBtn.addEventListener('click', () => {
            let currentQty = parseInt(qtyInput.value);
            if (currentQty > 1) {
                qtyInput.value = currentQty - 1;
            }
        });
    }

    if (increaseBtn && qtyInput) {
        increaseBtn.addEventListener('click', () => {
            let currentQty = parseInt(qtyInput.value);
            qtyInput.value = currentQty + 1;
        });
    }
});