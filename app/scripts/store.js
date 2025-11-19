// Function to fetch and display store details
const fetchStoreDetails = async (vendorId) => {
    // Assuming you have an action in your index.php to fetch vendor/store details by ID
    const url = `index.php?action=get_vendor_details&vendor_id=${vendorId}`;

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.success && data.vendor) {
            const vendor = data.vendor;

            // Get the elements where the name and address should be displayed
            // NOTE: These IDs ('storeNameDisplay', 'storeAddressDisplay') are not present in your HTML,
            // but the function is maintained as provided.
            const storeNameElement = document.getElementById('storeNameDisplay');
            const storeAddressElement = document.getElementById('storeAddressDisplay');

            if (storeNameElement) {
                storeNameElement.textContent = vendor.store_name || 'Store Name Not Found';
            }

            if (storeAddressElement) {
                const address = vendor.address_line1 ? `${vendor.address_line1}, ${vendor.city}, ${vendor.province}` : (vendor.address || 'Address Not Available');
                storeAddressElement.textContent = address;
            }
        } else {
            console.error('Error fetching store details:', data.message || 'Unknown error');
        }
    } catch (error) {
        console.error('Network Error fetching store details:', error);
    }
};

// Function to open the product management modal (Edit/Update)
openProductModal = (product) => {
    const productModal = document.getElementById('productModal');
    if (!productModal) return;

    document.getElementById('modalProductName').textContent = product.name || 'Product Details';
    const imageUrl = product.image_url && product.image_url.trim() !== '' ? product.image_url : 'public/assets/default/default_product.jpg';
    document.getElementById('modalProductImage').src = imageUrl;
    document.getElementById('productName').value = product.name || '';
    document.getElementById('productDescription').value = product.description || '';
    document.getElementById('productPrice').value = product.price || 0;
    document.getElementById('productStock').value = product.stock_quantity || 0;
    document.getElementById('modalProductId').value = product.product_id;
    const productCategorySelect = document.getElementById('productCategory');
    if (productCategorySelect) {
        productCategorySelect.value = product.category_id || '';
    }
    
    // ⭐ FIX: Make the modal visible
    productModal.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', () => {
    const mainElement = document.querySelector('.store-page-main');
    const vendorId = mainElement ? mainElement.dataset.vendorId : null;

    if (!vendorId) {
        console.error('Vendor ID is not available. Cannot initialize store page.');
        return;
    }

    // ⭐ NEW CALL: Fetch store details when the DOM is ready
    fetchStoreDetails(vendorId);

    // --- Modal Elements ---
    const productModal = document.getElementById('productModal');
    const addProductModal = document.getElementById('addProductModal');
    const closeBtn = document.querySelector('#productModal .close-btn'); // Close button for Edit Modal
    
    // ⭐ FIX: The ID closeAddModalBtn is not in HTML. Using the generic class selector here, but also added the specific ID logic below.
    const closeAddModalBtn = document.querySelector('#addProductModal .close-btn');
    const addProductFloatBtn = document.getElementById('addProductFloatBtn');

    // --- Form Elements ---
    const updateProductForm = document.getElementById('updateProductForm');
    const deleteProductBtn = document.getElementById('deleteProductBtn');
    const addProductForm = document.getElementById('addProductForm');

    // --- Image Preview Elements ---
    const newProductImageContainer = document.getElementById('newProductImageContainer');
    const newProductImageInput = document.getElementById('newProductImage');
    const newProductImagePreview = document.getElementById('newProductImagePreview');

    // --- Chat Elements ---
    const chatModal = document.getElementById('chatModal');
    const openChatModalLink = document.getElementById('openChatModalLink');
    const closeChatModalBtn = document.getElementById('closeChatModalBtn');
    const chatMessagesContainer = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    const chatHeaderTitle = document.getElementById('chatHeaderTitle');
    const threadListContainer = document.getElementById('threadListContainer');

    const currentVendorUserId = parseInt(vendorId, 10);

    let currentThreadId = null;
    let lastMessageTimestamp = '1970-01-01 00:00:00';
    let pollingInterval;

    // ===============================================
    // ⭐ MODAL OPEN/CLOSE & PRODUCT FILTERING LOGIC
    // ===============================================

    // --- Image Preview for Add Product (Moved from HTML script) ---
    if (newProductImageContainer && newProductImageInput && newProductImagePreview) {
        newProductImageContainer.addEventListener('click', (e) => {
            if(e.target !== newProductImageInput) { 
                newProductImageInput.click();
            }
        });

        newProductImageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    newProductImagePreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                newProductImagePreview.src = 'public/assets/default/default_product.jpg';
            }
        });
    }

    // --- Add Product Modal Open ---
    if (addProductFloatBtn && addProductModal) {
        addProductFloatBtn.addEventListener('click', () => {
            // Reset form and image on open
            addProductForm.reset();
            newProductImagePreview.src = 'public/assets/default/default_product.jpg';
            addProductModal.style.display = 'block';
        });
    }

    // --- Modal Close Button Handlers ---
    // Edit Product Modal Close
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            productModal.style.display = 'none';
        });
    }

    // Add Product Modal Close (using the generic class selector)
    if (closeAddModalBtn) {
        closeAddModalBtn.addEventListener('click', () => {
            addProductModal.style.display = 'none';
            addProductForm.reset();
        });
    }

    // --- Close Modals on Outside Click (Overlay) ---
    window.addEventListener('click', (e) => {
        if (e.target === productModal) {
            productModal.style.display = 'none';
        }
        if (e.target === addProductModal) {
            addProductModal.style.display = 'none';
        }
        if (e.target === chatModal) {
            // Use the dedicated close chat function for consistency
            chatModal.classList.remove('open');
            stopPolling();
        }
    });

    // --- Product Table Filtering Logic ---
    const filterButtons = document.querySelectorAll('.product-filters .filter-btn');
    const allProductRows = document.querySelectorAll('.product-table tbody tr[data-product-id]');
    const noResultsMessage = document.getElementById('noResultsMessage');

    const filterProducts = (filter) => {
        let visibleCount = 0;

        allProductRows.forEach(row => {
            const status = row.getAttribute('data-status');
            // Check for the specific stock-low class
            const isLowStock = row.querySelector('.stock-low');

            let shouldShow = false;

            switch (filter) {
                case 'all':
                    shouldShow = true;
                    break;
                case 'active':
                case 'pending':
                case 'inactive':
                    shouldShow = (status === filter);
                    break;
                case 'low-stock':
                    shouldShow = (isLowStock !== null);
                    break;
            }

            if (shouldShow) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Toggle the "No products match the selected filter" message
        if (noResultsMessage) {
            // Only show if there are actual product rows but none match the filter
            if (allProductRows.length > 0 && visibleCount === 0) {
                noResultsMessage.style.display = 'table-row';
            } else {
                noResultsMessage.style.display = 'none';
            }
        }
    };

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            const filterValue = this.getAttribute('data-filter');
            filterProducts(filterValue);
        });
    });

    // Initial filter when page loads
    filterProducts('all');

    // ===============================================
    // ASYNC FORM SUBMISSION (Add/Update/Delete)
    // ===============================================

    // --- Add Product Submission ---
    addProductForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(addProductForm);
        formData.append('vendor_id', vendorId);

        try {
            const response = await fetch('index.php?action=create_product', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (response.status === 201) {
                alert(data.message);
                addProductModal.style.display = 'none';
                window.location.reload(); // Reload to update table and chart
            } else {
                alert(data.error || 'Failed to add product.');
            }
        } catch (error) {
            console.error('Error adding product:', error);
            alert('Network failure. Failed to add product.');
        }
    });

    // --- Update Product Submission ---
    updateProductForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const productId = document.getElementById('modalProductId').value;
        if (!productId) {
            alert('Error: Product ID is missing. Cannot update.');
            return;
        }

        const formData = new FormData(updateProductForm);
        // Note: The form doesn't handle image file updates in the provided HTML.
        // Assuming file upload is handled separately or not implemented here.
        // The hidden input 'product_id' is already included in formData.

        try {
            const formData = new FormData(updateProductForm);
            const response = await fetch(`index.php?action=update_product&product_id=${productId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, // 💡 ADD THIS HEADER
                body: new URLSearchParams(formData)
            });

            const data = await response.json();

            if (response.status === 200) {
                alert(data.message);
                productModal.style.display = 'none';
                window.location.reload(); // Reload to update table
            } else {
                alert(data.error || 'Failed to update product.');
            }
        } catch (error) {
            console.error('Error updating product:', error);
            alert('Network failure. Failed to update product.');
        }
    });

    // --- Delete Product Handler ---
    deleteProductBtn.addEventListener('click', async () => {
        const productId = document.getElementById('modalProductId').value;
        if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`index.php?action=delete_product&product_id=${productId}`, {
                method: 'POST'
            });

            const data = await response.json();

            if (response.status === 200) {
                alert(data.message);
                productModal.style.display = 'none';
                window.location.reload(); // Reload to remove product from table
            } else {
                alert(data.error || 'Failed to delete product.');
            }
        } catch (error) {
            console.error('Error deleting product:', error);
            alert('Network failure. Failed to delete product.');
        }
    });

    // ===============================================
    // CHAT/MESSAGING LOGIC
    // ===============================================

    const stopPolling = () => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    };

    const startPolling = () => {
        if (pollingInterval) clearInterval(pollingInterval);

        pollingInterval = setInterval(() => {
            if (chatModal.classList.contains('open') && currentThreadId) {
                loadMessages(currentThreadId, false);
            }
        }, 3000);
    };

    const renderMessage = (message) => {
        const isSelf = message.sender_user_id == currentVendorUserId;
        const messageClass = isSelf ? 'self' : 'other';

        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message', messageClass);

        const time = new Date(message.sent_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        messageElement.innerHTML = `
            ${message.message_content}
            <span class="message-time">${time}</span>
        `;
        chatMessagesContainer.appendChild(messageElement);

        if (message.sent_at > lastMessageTimestamp) {
            lastMessageTimestamp = message.sent_at;
        }
    };

    const scrollToBottom = () => {
        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
    };

    const loadMessages = async (threadId, isInitialLoad = true, customerName = null) => {
        if (!threadId) return;
        currentThreadId = threadId;

        if (isInitialLoad) {
            chatMessagesContainer.innerHTML = '<p style="text-align: center; color: #aaa; margin-top: 50px;"><i class="bi bi-arrow-clockwise spin"></i> Loading messages...</p>';
            lastMessageTimestamp = '1970-01-01 00:00:00';
            if (customerName) {
                chatHeaderTitle.textContent = `Chat with ${customerName}`;
            }
        }

        const url = `index.php?action=get_thread_messages&thread_id=${threadId}${isInitialLoad ? '' : `&last_timestamp=${lastMessageTimestamp}`}`;

        try {
            const response = await fetch(url);
            const data = await response.json();

            if (data.success && data.messages) {
                if (isInitialLoad) {
                    chatMessagesContainer.innerHTML = '';
                }

                data.messages.forEach(renderMessage);
                scrollToBottom();

            } else if (data.message && isInitialLoad) {
                chatMessagesContainer.innerHTML = `<p style="text-align: center; color: red; margin-top: 50px;">${data.message}</p>`;
            }
        } catch (error) {
            console.error('Network Error loading messages:', error);
            if (isInitialLoad) {
                chatMessagesContainer.innerHTML = '<p style="text-align: center; color: red; margin-top: 50px;">Failed to connect to chat server.</p>';
            }
        }
    };

    const sendMessage = async () => {
        const messageContent = messageInput.value.trim();
        if (!messageContent || !currentThreadId) return;

        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const tempMessageElement = document.createElement('div');
        tempMessageElement.classList.add('chat-message', 'self', 'sending');
        tempMessageElement.innerHTML = `${messageContent}<span class="message-time">${time}</span>`;
        chatMessagesContainer.appendChild(tempMessageElement);
        scrollToBottom();

        messageInput.value = '';

        try {
            const response = await fetch('index.php?action=send_chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    thread_id: currentThreadId,
                    message_content: messageContent
                })
            });

            const data = await response.json();

            if (data.success) {
                tempMessageElement.classList.remove('sending');
            } else {
                tempMessageElement.classList.add('failed');
                alert('Failed to send message: ' + (data.message || 'Unknown error.'));
            }

        } catch (error) {
            console.error('Send message error:', error);
            tempMessageElement.classList.add('failed');
            alert('Network failure. Message not sent.');
        }
    };

    const renderThreadList = (threads) => {
        if (!threadListContainer) return;

        threadListContainer.innerHTML = '';
        if (threads.length === 0) {
            threadListContainer.innerHTML = '<p class="no-threads" style="text-align: center; padding: 20px; color: #888;">No active conversations found.</p>';
            chatMessagesContainer.innerHTML = '<p style="text-align: center; color: #888; margin-top: 50px;">Select a conversation to begin.</p>';
            return;
        }
        
        threads.forEach((thread, index) => {
            const threadElement = document.createElement('div');
            threadElement.classList.add('thread-item');

            const customerName = thread.customer_name || `Customer #${thread.customer_user_id}`;

            let profilePicture = 'public/assets/default/default_profile.jpg';

            if (thread.customer_profile_picture) {
                if (thread.customer_profile_picture.includes('/')) {
                    profilePicture = thread.customer_profile_picture;
                } else {
                    profilePicture = 'uploads/' + thread.customer_profile_picture;
                }
            }

            // Set the first thread as active and load its messages
            if (index === 0) {
                threadElement.classList.add('active');
                chatHeaderTitle.textContent = `Chat with ${customerName}`;
                currentThreadId = thread.thread_id;
                loadMessages(currentThreadId, true, customerName);
            }

            threadElement.dataset.threadId = thread.thread_id;
            threadElement.dataset.customerName = customerName;

            const lastMessageTime = thread.last_message_at ? new Date(thread.last_message_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'No messages';

            threadElement.innerHTML = `
                <img src="${profilePicture}" alt="${customerName}'s avatar" class="thread-avatar">
                <div class="thread-info">
                    <div class="thread-name">${customerName}</div>
                    <div class="thread-time">${lastMessageTime}</div>
                </div>
            `;

            threadElement.addEventListener('click', (e) => {
                document.querySelectorAll('.thread-item').forEach(item => item.classList.remove('active'));
                e.currentTarget.classList.add('active');

                const newThreadId = e.currentTarget.dataset.threadId;
                const newCustomerName = e.currentTarget.dataset.customerName;

                if (newThreadId != currentThreadId) {
                    stopPolling();
                    loadMessages(newThreadId, true, newCustomerName);
                    startPolling();
                }
            });

            threadListContainer.appendChild(threadElement);
        });
    };

    const loadVendorThreads = async () => {
        if (threadListContainer) {
            threadListContainer.innerHTML = '<p class="loading-threads" style="text-align: center; padding: 20px; color: #aaa;"><i class="bi bi-arrow-clockwise spin"></i> Loading conversations...</p>';
        }

        try {
            const response = await fetch('index.php?action=get_vendor_threads');
            const threads = await response.json();

            if (response.ok && Array.isArray(threads)) {
                renderThreadList(threads);
                // Start polling only after threads are loaded and the initial message load is triggered
                if (threads.length > 0) {
                    startPolling();
                }
            } else if (!response.ok && threads.message) {
                console.error('Error fetching vendor threads:', threads.message);
                if (threadListContainer) {
                    threadListContainer.innerHTML = `<p class="error-threads">Error: ${threads.message}</p>`;
                }
                chatMessagesContainer.innerHTML = '<p style="text-align: center; color: red; margin-top: 50px;">Failed to load chat data.</p>';
            } else {
                if (threadListContainer) {
                    threadListContainer.innerHTML = '<p class="no-threads">No active conversations found.</p>';
                }
                chatMessagesContainer.innerHTML = '<p style="text-align: center; color: #888; margin-top: 50px;">Select a conversation to begin.</p>';
                chatHeaderTitle.textContent = 'Conversations';
            }
        } catch (error) {
            console.error('Network Error fetching vendor threads:', error);
            if (threadListContainer) {
                threadListContainer.innerHTML = '<p class="error-threads">Failed to connect to chat server.</p>';
            }
        }
    };
    
    // --- Chat Modal Event Listeners ---
    if (openChatModalLink && chatModal) {
        openChatModalLink.addEventListener('click', (e) => {
            e.preventDefault();
            chatModal.classList.add('open');
            chatModal.style.display = 'grid'; // Ensure modal is visible
            loadVendorThreads(); // Load threads when opening the chat
        });
    }

    if (closeChatModalBtn) {
        closeChatModalBtn.addEventListener('click', () => {
            chatModal.classList.remove('open');
            chatModal.style.display = 'none'; // Ensure modal is hidden
            stopPolling();
        });
    }

    if (sendMessageBtn) {
        sendMessageBtn.addEventListener('click', sendMessage);
    }

    if (messageInput) {
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
                e.preventDefault();
            }
        });
    }
});