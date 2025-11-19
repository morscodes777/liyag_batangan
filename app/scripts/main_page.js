// Throttling function to limit how often a function can run
const throttle = (func, limit) => {
    let lastFunc;
    let lastRan;
    return function() {
        const context = this;
        const args = arguments;
        if (!lastRan) {
            func.apply(context, args);
            lastRan = Date.now();
        } else {
            clearTimeout(lastFunc);
            lastFunc = setTimeout(function() {
                if ((Date.now() - lastRan) >= limit) {
                    func.apply(context, args);
                    lastRan = Date.now();
                }
            }, limit - (Date.now() - lastRan));
        }
    };
};

const renderDropdownResults = (targetId, results, isStore = false) => {
    const container = document.getElementById(targetId);
    if (!container) return;
    
    container.innerHTML = '';

    if (results && results.length > 0) {
        results.slice(0, 3).forEach(item => { // Show up to 3 results per section
            const link = document.createElement('a');
            if (isStore) {
                link.href = `index.php?action=view_store&vendor_id=${item.vendor_id}`;
                link.textContent = item.business_name;
            } else {
                // Assuming you have a specific product view page or use the main search page
                link.href = `index.php?action=home&query=${encodeURIComponent(item.name)}`; 
                link.textContent = item.name;
            }
            container.appendChild(link);
        });
    } else {
        container.innerHTML = '<p class="no-results">No results found.</p>';
    }
};

const handleSearchInput = async (query) => {
    const dropdown = document.getElementById('searchResultsDropdown');
    const viewAllLink = document.getElementById('viewAllSearch');

    if (query.length < 3) {
        dropdown.classList.remove('active');
        // Reset dropdown content when query is too short
        document.getElementById('storeResults').innerHTML = '<p class="no-results">Start typing to see results...</p>';
        document.getElementById('productResults').innerHTML = '<p class="no-results">Start typing to see results...</p>';
        viewAllLink.style.display = 'none';
        return;
    }
    
    // Show dropdown immediately
    dropdown.classList.add('active');

    // Update the "View All" link
    viewAllLink.href = `index.php?action=home&query=${encodeURIComponent(query)}`;
    viewAllLink.innerHTML = `View All Results for "<strong>${query}</strong>"`;
    viewAllLink.style.display = 'block';

    // Placeholder loading state
    document.getElementById('storeResults').innerHTML = '<p class="no-results"><i class="bi bi-arrow-clockwise spin"></i> Searching stores...</p>';
    document.getElementById('productResults').innerHTML = '<p class="no-results"><i class="bi bi-arrow-clockwise spin"></i> Searching products...</p>';
    
    try {
        // Assuming your backend supports an AJAX search action
        const response = await fetch(`index.php?action=ajax_search&query=${encodeURIComponent(query)}`);
        const data = await response.json(); 

        if (data.success) {
            renderDropdownResults('storeResults', data.stores, true);
            renderDropdownResults('productResults', data.products, false);
        } else {
             document.getElementById('storeResults').innerHTML = '<p class="no-results">Search failed.</p>';
             document.getElementById('productResults').innerHTML = '<p class="no-results">Search failed.</p>';
        }

    } catch (error) {
        console.error('AJAX search error:', error);
        document.getElementById('storeResults').innerHTML = '<p class="no-results">Network error.</p>';
        document.getElementById('productResults').innerHTML = '<p class="no-results">Network error.</p>';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const dropdown = document.getElementById('searchResultsDropdown');
    const loginModal = document.getElementById('loginRequiredModal');
    
    // ===============================================
    // LIVE SEARCH EVENT LISTENERS
    // ===============================================

    // Throttle the search function to run no more than once every 300ms
    const throttledSearch = throttle(handleSearchInput, 300);

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            throttledSearch(e.target.value.trim());
        });
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Handle ENTER key to initiate full search page navigation
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const query = searchInput.value.trim();
                if (query.length > 0) {
                    window.location.href = `index.php?action=home&query=${encodeURIComponent(query)}`;
                }
            }
        });
    }

    // ===============================================
    // LOGIN REQUIRED MODAL HANDLER
    // ===============================================
    if (loginModal) {
        document.querySelectorAll('.login-required-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                // Show modal
                loginModal.style.display = 'flex';
                // Trigger transition
                setTimeout(() => {
                    loginModal.classList.add('open');
                }, 10); 
            });
        });
    }

    // ===============================================
    // INITAL SEARCH PAGE LOAD LOGIC
    // ===============================================
    // If we are on the search results page, make sure the dropdown doesn't interfere
    if (document.body.classList.contains('search-results-active')) {
        if (searchInput) {
            // Keep the search term in the input but disable live search dropdown
            searchInput.focus();
        }
    }
}); 