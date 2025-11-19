document.addEventListener('DOMContentLoaded', function() {
    const cartItemsList = document.querySelector('.cart-items-list');
    const selectedTotalSpan = document.getElementById('cart-selected-total');
    const checkoutButton = document.getElementById('checkout-btn');
    
    const checkoutForm = document.getElementById('checkout-form');
    const finalSelectedInput = document.getElementById('final-selected-items-input');
    const checkoutTotalInput = document.getElementById('checkout-total-input');
    
    // Initial setup: Hide all checkboxes and replace click target
    document.querySelectorAll('.item-selection').forEach(checkbox => {
        checkbox.style.display = 'none';
        
        // Ensure initial state in JS matches HTML (data-selected="false")
        const itemDiv = checkbox.closest('.cart-item');
        if (itemDiv.dataset.selected === 'true') {
            itemDiv.dataset.selected = 'false';
            itemDiv.classList.remove('selected');
            checkbox.checked = false;
        }
    });

    function updateSelectedTotal() {
        let selectedTotal = 0.00;
        let selectedCount = 0;
        let selectedItemIds = [];

        document.querySelectorAll('.cart-item[data-selected="true"]').forEach(item => {
            const lineTotal = parseFloat(item.dataset.lineTotal);
            selectedTotal += lineTotal;
            selectedCount++;
            selectedItemIds.push(item.dataset.cartItemId);
        });

        selectedTotalSpan.textContent = '₱' + selectedTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        
        checkoutButton.disabled = selectedCount === 0;

        // Update hidden fields for checkout handler
        finalSelectedInput.value = selectedItemIds.join(',');
        checkoutTotalInput.value = selectedTotal.toFixed(2);
    }

    function toggleItemSelectionState(itemDiv) {
        const currentlySelected = itemDiv.dataset.selected === 'true';
        const checkbox = itemDiv.querySelector('.item-selection');

        if (!currentlySelected) {
            itemDiv.classList.add('selected');
            itemDiv.dataset.selected = 'true';
            if (checkbox) checkbox.checked = true;
        } else {
            itemDiv.classList.remove('selected');
            itemDiv.dataset.selected = 'false';
            if (checkbox) checkbox.checked = false;
        }
        updateSelectedTotal();
    }
    
    if (cartItemsList) {
        cartItemsList.addEventListener('click', function(event) {
            const itemDiv = event.target.closest('.cart-item');
            if (!itemDiv) return;

            if (event.target.closest('.quantity-btn') || event.target.closest('.remove-item-btn') || event.target.classList.contains('custom-checkbox-replacement')) {
                // If the custom replacement label is clicked, allow it to run the toggle below
            } else if (event.target.closest('.quantity-update-control')) {
                 return;
            }
            
            if (event.target.closest('.custom-checkbox-replacement') || !event.target.closest('.item-controls')) {
                toggleItemSelectionState(itemDiv);
            }
        });
    }

    cartItemsList.addEventListener('click', function(event) {
        const button = event.target.closest('.quantity-btn');
        if (!button) return;

        const cartItemId = button.dataset.itemId;
        const itemDiv = document.querySelector(`.cart-item[data-cart-item-id="${cartItemId}"]`);
        const quantityInput = document.getElementById(`qty-${cartItemId}`);
        let currentQty = parseInt(quantityInput.value);
        let unitPrice = parseFloat(itemDiv.dataset.unitPrice);

        let newQty = currentQty;
        if (button.classList.contains('increase-qty')) {
            newQty += 1;
        } else if (button.classList.contains('decrease-qty') && currentQty > 1) {
            newQty -= 1;
        } else {
            return;
        }
        
        fetch('index.php?action=update_cart_quantity', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `cart_item_id=${cartItemId}&quantity=${newQty}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                quantityInput.value = newQty;
                
                const newLineTotal = newQty * unitPrice;
                document.getElementById(`price-${cartItemId}`).textContent = '₱' + newLineTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                
                itemDiv.dataset.quantity = newQty;
                itemDiv.dataset.lineTotal = newLineTotal.toFixed(2);
                
                if (itemDiv.dataset.selected === 'true') {
                    updateSelectedTotal();
                }
            } else {
                alert('Failed to update quantity: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error updating quantity:', error);
            alert('A network error occurred while updating the quantity.');
        });
    });

    cartItemsList.addEventListener('click', function(event) {
        const deleteButton = event.target.closest('.remove-item-btn');
        if (!deleteButton) return;
        
        if (!confirm("Are you sure you want to remove this item from your cart?")) return;

        const cartItemId = deleteButton.dataset.itemId;
        
        fetch('index.php?action=delete_cart_item', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `cart_item_id=${cartItemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const itemDiv = document.querySelector(`.cart-item[data-cart-item-id="${cartItemId}"]`);
                if (itemDiv) {
                    itemDiv.remove();
                    updateSelectedTotal();
                    // Optional: Check if the cart is now empty and update UI accordingly
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        const emptyMessage = `<p class="empty-cart-message">Your cart is empty. Time to find some local goods! <a href="index.php?action=products&category_id=all" class="btn-goto-products">Go to Products</a></p>`;
                        cartItemsList.innerHTML = emptyMessage;
                        document.querySelector('.cart-summary').style.display = 'none';
                    }
                }
            } else {
                alert('Failed to delete item: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error deleting item:', error);
            alert('A network error occurred while deleting the item.');
        });
    });
    
    checkoutButton.addEventListener('click', (e) => {
        if (checkoutButton.disabled) {
            e.preventDefault();
            return;
        }
        
        // Hidden inputs are already updated by updateSelectedTotal
        
        if (finalSelectedInput.value.length === 0) {
            alert('Please select at least one item to proceed to checkout.');
            return;
        }
        
        checkoutForm.submit();
    });

    updateSelectedTotal();
});