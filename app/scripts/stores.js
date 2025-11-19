document.addEventListener('DOMContentLoaded', () => {

    // --- Filter Element Selectors ---
    const filterContainer = document.getElementById('category-filter');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const productCards = document.querySelectorAll('.product-card');
    const filterTrack = document.getElementById('filter-track');

    // --- Chat Element Selectors ---
    const chatToggleBtn = document.getElementById('chatToggleBtn');
    const floatingChatButton = document.querySelector('.chat-toggle-btn');
    const chatModal = document.getElementById('chatModal');
    const closeChatBtn = document.getElementById('closeChatBtn');
    const chatMessagesContainer = document.getElementById('chatMessages');
    const chatMessageInput = document.getElementById('chatMessageInput');
    const sendChatMessageBtn = document.getElementById('sendChatMessageBtn');
    
    const storePageMain = document.querySelector('.store-page-main');
    // Ensure the element exists before trying to access dataset
    const vendorUserId = storePageMain ? storePageMain.dataset.vendorUserId : null;
    
    let currentThreadId = null; 
    let lastTimestamp = null; 
    let chatPollingInterval = null; 

    // --- Product Filtering Logic ---

    function updateFilterTrack(activeButton) {
        if (!filterContainer || !filterTrack) return;

        // Use requestAnimationFrame for smoother updates (especially on resize)
        requestAnimationFrame(() => {
            const containerRect = filterContainer.getBoundingClientRect();
            const buttonRect = activeButton.getBoundingClientRect();

            filterTrack.style.width = `${buttonRect.width}px`;
            filterTrack.style.transform = `translateX(${buttonRect.left - containerRect.left}px)`;
        });
    }
    
    if (filterButtons.length > 0 && filterTrack && filterContainer) {
        const initialActiveButton = document.querySelector('.filter-btn.active');
        if (initialActiveButton) {
            updateFilterTrack(initialActiveButton);
        }
        
        window.addEventListener('resize', () => {
            const currentActiveButton = document.querySelector('.filter-btn.active');
            if (currentActiveButton) {
                updateFilterTrack(currentActiveButton);
            }
        });

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const categoryId = button.dataset.categoryId;

                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                updateFilterTrack(button);

                productCards.forEach(card => {
                    const cardCategoryId = card.dataset.categoryId;
                    if (categoryId === 'all' || cardCategoryId === categoryId) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    }

    // --- Chat Logic ---

    function appendMessage(senderId, content, sentAt) {
        if (!chatMessagesContainer) return;

        const currentUserId = localStorage.getItem('current_user_id');
        const messageDiv = document.createElement('div');
        const isSelf = currentUserId && senderId.toString() === currentUserId.toString();
        
        messageDiv.classList.add('chat-message', isSelf ? 'self' : 'other');
        messageDiv.innerHTML = `
            <span class="message-content">${content}</span>
            <span class="message-time">${new Date(sentAt).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
        `;
        chatMessagesContainer.appendChild(messageDiv);
        chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight; 
        
        lastTimestamp = sentAt;
    }
    
    function pollForNewMessages() {
        if (!currentThreadId || !lastTimestamp) return;

        fetch(`index.php?action=load_chat&thread_id=${currentThreadId}&last_timestamp=${lastTimestamp}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        appendMessage(msg.sender_user_id.toString(), msg.message_content, msg.sent_at);
                    });
                }
            })
            .catch(error => {
                console.error('Polling error:', error);
            });
    }

    function stopPolling() {
        if (chatPollingInterval) {
            clearInterval(chatPollingInterval);
            chatPollingInterval = null;
        }
    }

    function startPolling() {
        stopPolling();
        chatPollingInterval = setInterval(pollForNewMessages, 3000); 
    }

    function loadChatHistory() {
        if (!vendorUserId || vendorUserId === '0' || !chatMessagesContainer) {
            console.error("Vendor User ID is missing or invalid.");
            return;
        }

        chatMessagesContainer.innerHTML = '<p style="text-align: center; color: #6c757d;">Loading chat history...</p>';

        fetch(`index.php?action=load_chat&vendor_user_id=${vendorUserId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    currentThreadId = data.thread_id;
                    const currentUserId = data.current_user_id.toString();
                    localStorage.setItem('current_user_id', currentUserId);

                    chatMessagesContainer.innerHTML = '';
                    
                    if (data.messages.length === 0) {
                        chatMessagesContainer.innerHTML = '<p style="text-align: center; color: #6c757d;">Start a new conversation.</p>';
                        lastTimestamp = new Date().toISOString(); 
                    } else {
                        data.messages.forEach(msg => {
                            appendMessage(msg.sender_user_id.toString(), msg.message_content, msg.sent_at); 
                        });
                    }
                    
                    startPolling(); 

                } else {
                    chatMessagesContainer.innerHTML = `<p style="text-align: center; color: red;">Error: ${data.message}</p>`;
                    console.error('Chat load error:', data.message);
                }
            })
            .catch(error => {
                chatMessagesContainer.innerHTML = `<p style="text-align: center; color: red;">Error connecting to chat server.</p>`;
                console.error('Network error loading chat:', error);
            });
    }

    function sendNewMessage() {
        const content = chatMessageInput.value.trim();
        if (!content || !currentThreadId || !chatMessageInput || !sendChatMessageBtn) return;

        chatMessageInput.value = '';
        sendChatMessageBtn.disabled = true;

        const formData = new URLSearchParams();
        formData.append('thread_id', currentThreadId);
        formData.append('message_content', content);
        
        const senderId = localStorage.getItem('current_user_id');
        const now = new Date().toISOString();
        appendMessage(senderId, content, now);

        const sentMessageElement = chatMessagesContainer.lastElementChild;

        fetch('index.php?action=send_chat', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('SERVER FAILED to save message. Reason:', data.message);
                if (sentMessageElement) sentMessageElement.remove();
            }
        })
        .catch(error => {
            console.error('NETWORK Error sending message:', error);
            if (sentMessageElement) sentMessageElement.remove();
        })
        .finally(() => {
            sendChatMessageBtn.disabled = false;
        });
    }

    if (chatToggleBtn && chatModal && closeChatBtn && sendChatMessageBtn && chatMessageInput) {
        chatToggleBtn.addEventListener('click', () => {
            const isOpen = chatModal.classList.toggle('open');
            if (isOpen) {
                if (floatingChatButton) {
                    floatingChatButton.style.display = 'none';
                }
                loadChatHistory();
            } else {
                if (floatingChatButton) {
                    floatingChatButton.style.display = 'flex';
                }
                stopPolling(); 
            }
        });

        closeChatBtn.addEventListener('click', () => {
            chatModal.classList.remove('open');
            if (floatingChatButton) {
                floatingChatButton.style.display = 'flex';
            }
            stopPolling();
        });

        sendChatMessageBtn.addEventListener('click', sendNewMessage);
        
        chatMessageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendNewMessage();
            }
        });
    }

    // --- Cleanup/Global Logic (Removed from previous but added a basic chat close on click) ---

    document.addEventListener("click", (e) => {
        // Close Chat Modal if click is outside the button and the modal itself
        if (chatModal && chatModal.classList.contains('open') && 
            !chatModal.contains(e.target) && !e.target.closest('#chatToggleBtn')) {
            chatModal.classList.remove('open');
            stopPolling(); 
            if (floatingChatButton) {
                 floatingChatButton.style.display = 'flex';
            }
        }
    });
    

    
}); 