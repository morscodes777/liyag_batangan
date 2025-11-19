document.addEventListener('DOMContentLoaded', function() {
    const modalOverlay = document.getElementById('reviewModal');
    const closeBtn = modalOverlay.querySelector('.close-btn');
    const reviewForm = document.getElementById('reviewForm');
    const userRatingStars = document.querySelectorAll('#userRatingStars .star');
    const selectedRatingInput = document.getElementById('selectedRating');
    const messageStatus = document.getElementById('reviewMessage');
    const productLinks = document.querySelectorAll('.review-btn-link');

    function showMessage(message, isSuccess = true) {
        messageStatus.textContent = message;
        messageStatus.style.backgroundColor = isSuccess ? '#d4edda' : '#f8d7da';
        messageStatus.style.color = isSuccess ? '#155724' : '#721c24';
        messageStatus.style.display = 'block';
    }

    function resetModal() {
        modalOverlay.classList.remove('active');
        reviewForm.reset();
        selectedRatingInput.value = '';
        messageStatus.style.display = 'none';
        userRatingStars.forEach(star => star.classList.remove('selected', 'hovered'));
        document.getElementById('commentsList').innerHTML = '<p>Loading reviews...</p>';
        document.getElementById('avgRatingStars').textContent = '';
        document.getElementById('avgRatingValue').textContent = '(0.0)';
        document.getElementById('totalReviewsCount').textContent = '0';
    }

    function renderStars(container, rating) {
        container.innerHTML = '';
        const fullStars = Math.floor(rating);
        const hasHalf = rating % 1 !== 0;

        for (let i = 1; i <= 5; i++) {
            const icon = document.createElement('i');
            icon.classList.add('bi', 'bi-star-fill');
            if (i <= fullStars) {
                icon.style.color = '#FFD700';
            } else if (i === fullStars + 1 && hasHalf) {
                icon.classList.add('bi-star-half');
                icon.style.color = '#FFD700';
            } else {
                icon.style.color = '#ccc';
            }
            container.appendChild(icon);
        }
    }

    function fetchProductReviews(productId) {
        const userId = document.querySelector('.notification-dropdown').dataset.userId;

        fetch(`index.php?action=get_product_reviews&product_id=${productId}&user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                const commentsList = document.getElementById('commentsList');
                commentsList.innerHTML = '';

                const avgRating = parseFloat(data.average_rating || 0).toFixed(1);
                const totalReviews = data.total_reviews || 0;
                document.getElementById('avgRatingValue').textContent = `(${avgRating})`;
                document.getElementById('totalReviewsCount').textContent = totalReviews;
                renderStars(document.getElementById('avgRatingStars'), parseFloat(avgRating));

                if (data.reviews && data.reviews.length > 0) {
                    data.reviews.forEach(review => {
                        const div = document.createElement('div');
                        div.classList.add('comment-item');
                        const starsContainer = document.createElement('span');
                        renderStars(starsContainer, review.rating);

                        div.innerHTML = `
                            <p>
                                <span class="comment-author">${review.user_name}</span> 
                                ${starsContainer.innerHTML} 
                            </p>
                            <p class="comment-text">${review.comment}</p>
                        `;
                        commentsList.appendChild(div);
                    });
                } else {
                    commentsList.innerHTML = '<p>No reviews yet.</p>';
                }
            })
            .catch(error => {
                document.getElementById('commentsList').innerHTML = '<p style="color:red;">Error loading reviews.</p>';
            });
    }

    productLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = new URL(this.href);
            const productId = url.searchParams.get('product_id');
            const orderId = url.searchParams.get('order_id');

            const item = this.closest('.review-item');
            const productName = item.querySelector('h3').textContent;
            
            const orderItemId = this.dataset.orderItemId; 

            document.getElementById('modalProductName').textContent = `Rate Product: ${productName}`;
            document.getElementById('modalProductId').value = productId;
            document.getElementById('modalOrderId').value = orderId;
            document.getElementById('modalOrderItemId').value = orderItemId;
            
            fetchProductReviews(productId);

            modalOverlay.classList.add('active');
        });
    });

    closeBtn.addEventListener('click', resetModal);
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            resetModal();
        }
    });

    userRatingStars.forEach(star => {
        star.addEventListener('mouseover', function() {
            const rating = parseInt(this.dataset.rating);
            userRatingStars.forEach(s => {
                s.classList.toggle('hovered', parseInt(s.dataset.rating) <= rating);
            });
        });

        star.addEventListener('mouseout', function() {
            userRatingStars.forEach(s => s.classList.remove('hovered'));
        });

        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            selectedRatingInput.value = rating;
            userRatingStars.forEach(s => {
                s.classList.toggle('selected', parseInt(s.dataset.rating) <= rating);
            });
        });
    });

    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const rating = selectedRatingInput.value;
        const comment = document.getElementById('reviewComment').value;
        const productId = document.getElementById('modalProductId').value;
        const orderId = document.getElementById('modalOrderId').value;
        const orderItemId = document.getElementById('modalOrderItemId').value;

        if (!rating) {
            showMessage('Please select a rating.', false);
            return;
        }

        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('order_id', orderId);
        formData.append('order_item_id', orderItemId);
        formData.append('rating', rating);
        formData.append('comment', comment);

        fetch('index.php?action=submit_review', { 
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, true);
                setTimeout(() => {
                    resetModal();
                    window.location.reload();
                }, 1500);
            } else {
                showMessage(data.message, false);
            }
        })
        .catch(error => {
            showMessage('A server error occurred during submission.', false);
        });
    });
});