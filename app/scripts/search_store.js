document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');
    const storeResults = document.getElementById('storeResults');

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
                // Only pass stores; ignore products
                updateDropdown(data.stores || [], query);
                showDropdown();
            })
            .catch(error => {
                // Optional: Handle error (e.g., console.log(error))
            });
    }

    function updateDropdown(stores, query) {
        storeResults.innerHTML = '';

        if (stores.length > 0) {
            stores.forEach(store => {
                const storeLink = document.createElement('a');
                storeLink.href = `index.php?action=view_store&vendor_id=${encodeURIComponent(store.vendor_id)}`;
                storeLink.className = 'search-result-link'; 
                storeLink.innerHTML = `
                    <img src="${store.logo_url || 'public/assets/default/default_store_logo.jpg'}" 
                         alt="${store.business_name}" 
                         class="result-image">
                    <div class="result-info">
                        <h5>${store.business_name}</h5>
                        <p>${store.business_address}</p>
                    </div>
                `;
                storeResults.appendChild(storeLink);
            });
        } else {
            storeResults.innerHTML = '<p class="no-results">No stores found.</p>';
        }
    }

    function showDropdown() {
        if (document.activeElement === searchInput || searchInput.value.trim().length > 0) {
            searchResultsDropdown.classList.add('active');
            searchResultsDropdown.style.display = 'block';
        }
    }

    function hideDropdown() {
        searchResultsDropdown.classList.remove('active');
        searchResultsDropdown.style.display = 'none';
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
