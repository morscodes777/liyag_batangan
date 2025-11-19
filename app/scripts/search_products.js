document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');
    const productResults = document.getElementById('productResults');

    if (!searchInput) {
        return;
    }

    let debounceTimer;
    let lastQuery = searchInput.value.trim();
    
    function debounce(func, delay) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(func, delay);
    }

    function fetchSuggestions(query) {
        lastQuery = query;

        if (query.length === 0) {
            hideDropdown();
            return;
        }

        fetch(`index.php?action=search_suggestions&query=${encodeURIComponent(query)}`)
            .then(response => {
                return response.json();
            })
            .then(data => {
                // Only pass products; ignore stores
                updateDropdown(data.products || [], query);
                showDropdown();
            })
            .catch(error => {
                // Optional: Handle error (e.g., console.log(error))
            });
    }

    function updateDropdown(products, query) {
        productResults.innerHTML = '';

        if (products.length > 0) {
            products.forEach(product => {
                const productDiv = document.createElement('div');
                productDiv.className = 'search-result-link'; 
                productDiv.setAttribute('role', 'button'); 
                productDiv.setAttribute('data-product', JSON.stringify(product).replace(/'/g, "&apos;"));

                productDiv.innerHTML = `
                    <img src="${product.image_url || 'uploads/products/default_product.jpg'}" 
                         alt="${product.name}" 
                         class="result-image">
                    <div class="result-info">
                        <h5>${product.name}</h5>
                        <p>₱${parseFloat(product.price).toFixed(2)}</p>
                    </div>
                `;
                
                productDiv.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const productData = JSON.parse(this.getAttribute('data-product'));
                    
                    if (typeof openProductModal === 'function') {
                        openProductModal(productData);
                    }
                });
                
                productResults.appendChild(productDiv);
            });
        } else {
            productResults.innerHTML = '<p class="no-results">No products found.</p>';
        }
    }

    function showDropdown() {
        if (document.activeElement === searchInput || searchInput.value.trim().length > 0) {
            searchResultsDropdown.classList.add('active');  // Keep for styling/animations
            searchResultsDropdown.style.display = 'block';  // Add this to override any inline hides
        }
    }

    function hideDropdown() {
        searchResultsDropdown.classList.remove('active');  // Keep for styling/animations
        searchResultsDropdown.style.display = 'none';     // Add this to fully hide it
    }

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        debounce(() => fetchSuggestions(query), 300);
    });

    searchInput.addEventListener('focus', function() {
        const query = this.value.trim();
        if (query.length > 0) {
            fetchSuggestions(query); 
        }
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); 
            hideDropdown();
        }
    });

    document.addEventListener('click', function(e) {
        const productModal = document.getElementById('productModal');
        
        if (!searchInput.contains(e.target) 
            && !searchResultsDropdown.contains(e.target)
            && (!productModal || !productModal.contains(e.target))) {
            hideDropdown();
        }
    });

    hideDropdown();
});
