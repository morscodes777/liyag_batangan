// Global variables for the All Stores map
let allStoresMapInstance;
let storeMarkers = {}; 

// Coordinates for smart zooming (All Cities and Municipalities of Batangas)
const BATANGAS_LOCATIONS_MAP = {
    // Default for Batangas Province
    'province': { center: [13.7565, 121.0583], zoom: 10 }, 
    
    // Cities (Keys are simplified, lowercase, and without " city")
    'batangas': { center: [13.7565, 121.0583], zoom: 12 }, 
    'lipa': { center: [13.9409, 121.1611], zoom: 12 }, 
    'tanauan': { center: [14.0833, 121.1500], zoom: 13 }, 
    
    // Municipalities
    'agoncillo': { center: [13.9292, 120.9417], zoom: 13 },
    'balayan': { center: [13.9500, 120.7333], zoom: 13 },
    'balete': { center: [14.0042, 121.1125], zoom: 14 },
    'bauan': { center: [13.7833, 121.0167], zoom: 13 },
    'calaca': { center: [13.9500, 120.8000], zoom: 13 },
    'calatagan': { center: [13.8400, 120.6200], zoom: 12 },
    'cuenca': { center: [13.8833, 121.0417], zoom: 14 },
    'ibaan': { center: [13.8167, 121.1333], zoom: 13 },
    'laurel': { center: [14.0417, 120.9167], zoom: 13 },
    'lemery': { center: [13.9333, 120.8833], zoom: 13 },
    'lian': { center: [14.0250, 120.6500], zoom: 13 },
    'lobo': { center: [13.6250, 121.2167], zoom: 12 },
    'mabini': { center: [13.7667, 120.9333], zoom: 13 },
    'malvar': { center: [14.0333, 121.1500], zoom: 14 },
    'mataasnakahoy': { center: [14.0083, 121.1167], zoom: 14 },
    'nasugbu': { center: [14.0750, 120.6333], zoom: 12 },
    'padre garcia': { center: [13.9833, 121.1833], zoom: 13 },
    'pinagbayanan': { center: [13.8333, 121.1167], zoom: 14 }, // Using Ibaan/San Jose area as approximation
    'rosario': { center: [13.8500, 121.2000], zoom: 13 },
    'san jose': { center: [13.8833, 121.1167], zoom: 13 },
    'san juan': { center: [13.7833, 121.3833], zoom: 12 },
    'san luis': { center: [13.8500, 120.9167], zoom: 13 },
    'san nicolas': { center: [13.9333, 120.9833], zoom: 14 },
    'san pascual': { center: [13.8167, 121.0000], zoom: 13 },
    'santa teresita': { center: [13.8833, 120.9833], zoom: 14 },
    'santo tomas': { center: [14.1033, 121.1642], zoom: 13 },
    'taal': { center: [13.8833, 120.9250], zoom: 13 },
    'talisay': { center: [14.1000, 121.0500], zoom: 13 },
    'taysan': { center: [13.8000, 121.2000], zoom: 13 },
    'tingloy': { center: [13.6500, 120.8833], zoom: 12 },
    'tuy': { center: [14.0167, 120.7667], zoom: 13 }
};


document.addEventListener("DOMContentLoaded", () => {
    // --- Profile Dropdown --- (Existing Logic)
    const profileBtn = document.getElementById("profileBtn");
    const dropdownMenu = document.getElementById("dropdownMenu");

    // --- Notification Dropdown --- (Existing Logic)
    const notificationBtn = document.getElementById("notificationBtn");
    const notificationModal = document.getElementById("notificationModal");

    function toggleDropdown(dropdown) {
        if (!dropdown) return;
        if (dropdown.style.display === "block") {
            dropdown.style.display = "none";
        } else {
            [dropdownMenu, notificationModal].forEach(modal => {
                if (modal && modal !== dropdown) modal.style.display = "none";
            });
            dropdown.style.display = "block";
        }
    }

    if (profileBtn && dropdownMenu) {
        profileBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            toggleDropdown(dropdownMenu);
        });
    }

    if (notificationBtn && notificationModal) {
        notificationBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            toggleDropdown(notificationModal);
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener("click", (e) => {
        if (dropdownMenu && !dropdownMenu.contains(e.target) && !e.target.closest("#profileBtn")) {
            dropdownMenu.style.display = "none";
        }
        if (notificationModal && !notificationModal.contains(e.target) && !e.target.closest("#notificationBtn")) {
            notificationModal.style.display = "none";
        }
    });

    // ------------------------------------
    // --- Drawer Logic --- (Existing Logic)
    // ------------------------------------
    const openBtn = document.getElementById("view-stores-btn");
    const closeBtn = document.getElementById("close-stores-btn");
    const drawer = document.getElementById("stores-drawer");

    if (openBtn && drawer) {
        openBtn.addEventListener("click", () => {
            drawer.classList.add("active");
        });
    }

    if (closeBtn && drawer) {
        closeBtn.addEventListener("click", () => {
            drawer.classList.remove("active");
        });
    }

    // ------------------------------------
    // --- Store Card Click Logic --- (Existing Logic)
    // ------------------------------------
    const storeCards = document.querySelectorAll('.store-card-large');

    storeCards.forEach(card => {
        card.addEventListener('click', () => {
            const vendorId = card.getAttribute('data-vendor-id');
            if (vendorId) {
                window.location.href = `index.php?action=view_store&vendor_id=${vendorId}`;
            }
        });
    });

     // ------------------------------------
    // --- Map and Store Data Setup ---
    // ------------------------------------
    const storeContainer = document.querySelector(".store-grid-single");
    let approvedStores = [];

    if (storeContainer && storeContainer.dataset.stores) {
        try {
            approvedStores = JSON.parse(storeContainer.dataset.stores);

            if (approvedStores.length > 0) {
                initializeAllStoresMapInstance(approvedStores);
            }
        } catch (error) {
            console.error("Failed to parse store data:", error);
        }
    } else {
        console.error("Store container or data-stores attribute not found.");
    }
    
    // ------------------------------------
    // --- Location Filter Logic ---
    // ------------------------------------
    const locationFilter = document.getElementById('location-filter');

    if (locationFilter) {
        locationFilter.addEventListener('change', function() {
            const selectedLocation = this.value;
            filterStoresAndMapMarkers(selectedLocation, approvedStores);
        });
    }

    
});



// ------------------------------------
// --- Map Initialization Functions ---
// ------------------------------------

function initializeAllStoresMapInstance(stores) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userLatLng = [position.coords.latitude, position.coords.longitude];
                createAllStoresMapInstance(userLatLng, stores);
            },
            () => {
                // Default to Batangas center if geolocation fails
                const province = BATANGAS_LOCATIONS_MAP['province'];
                createAllStoresMapInstance(province.center, stores);
            }
        );
    } else {
        // Default to Batangas center if geolocation is not supported
        const province = BATANGAS_LOCATIONS_MAP['province'];
        createAllStoresMapInstance(province.center, stores);
    }
}

function createAllStoresMapInstance(centerLatLng, stores) {
    // 1. Initialize the Map without a fixed center/zoom
    allStoresMapInstance = L.map("all-stores-map", {
        zoomControl: false
    });

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors"
    }).addTo(allStoresMapInstance);

    // --- Red marker for user ---
    const redIcon = L.icon({
        iconUrl: 'data:image/svg+xml;charset=UTF-8,<svg width="24" height="41" viewBox="0 0 24 41" xmlns="http://www.w3.org/2000/svg"><path d="M12,40 C12,40 24,28.892 24,19.988C24,13.314 18.627,8 12,8 C5.373,8 0,13.314 0,19.988 C0,28.892 12,40 12,40 Z" fill="%23E00000"></path><circle cx="12" cy="18" r="5" fill="%23FFF"/></svg>',
        iconSize: [25, 41],
        iconAnchor: [12, 41]
    });

    const userMarker = L.marker(centerLatLng, { icon: redIcon }).addTo(allStoresMapInstance);
    userMarker.bindTooltip("You are here", { 
        permanent: true, 
        direction: 'bottom', 
        offset: [0, 1],
        className: 'user-label'
    }).openTooltip();

    // --- Orange marker for stores ---
    const orangeIcon = L.icon({
        iconUrl: 'data:image/svg+xml;charset=UTF-8,<svg width="24" height="41" viewBox="0 0 24 41" xmlns="http://www.w3.org/2000/svg"><path d="M12,40 C12,40 24,28.892 24,19.988C24,13.314 18.627,8 12,8 C5.373,8 0,13.314 0,19.988 C0,28.892 12,40 12,40 Z" fill="%23FFA500"></path><circle cx="12" cy="18" r="5" fill="%23FFF"/></svg>',
        iconSize: [25, 41],
        iconAnchor: [12, 41]
    });

    const allStoreCoordinates = [];
    
    // Add markers and store them globally
    stores.forEach(store => {
        if (store.latitude && store.longitude) {
            const latLng = [store.latitude, store.longitude];
            const marker = L.marker(latLng, { icon: orangeIcon });

            marker.bindTooltip(`
                <i class="bi bi-shop" style="color: orange; font-size: 10px;"></i> 
                ${store.business_name}
            `, { 
                permanent: true, 
                direction: 'top', 
                offset: [0, -25],
                className: 'store-tooltip'
            });

            // ... (popupContent definition is omitted for brevity but should be here) ...

            marker.bindPopup(`
                <div style="
                    background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('${store.logo_url || 'public/assets/default/default_store_logo.jpg'}');
                    background-size: cover;
                    background-position: center;
                    padding: 20px;
                    border-radius: 10px;
                    color: #fff;
                    text-shadow: 1px 1px 3px rgba(0,0,0,0.0);
                    width: 250px;
                    text-align: center;
                ">
                    <h3 style="margin: 0 0 10px; font-size: 10px;">${store.business_name}</h3>
                    <p style="margin: 0 0 12px; font-size: 8px;">${store.business_address}</p>
                    <a 
                        href="index.php?action=view_store&vendor_id=${store.vendor_id}" 
                        style="
                            display: inline-block;
                            padding: 8px 15px;
                            background-color: rgba(255, 213, 0, 0.9);
                            color: #000;
                            text-decoration: none;
                            border-radius: 6px;
                            font-weight: bold;
                            transition: all 0.2s ease;
                        "
                        onmouseover="this.style.backgroundColor='rgba(255,165,0,0.95)'; this.style.color='#fff';" 
                        onmouseout="this.style.backgroundColor='rgba(255,213,0,0.9)'; this.style.color='#fff';"
                    >
                        View Store
                    </a>
                </div>
            `);

            marker.addTo(allStoresMapInstance);
            
            // Collect coordinates for initial map fit
            allStoreCoordinates.push(latLng);
            storeMarkers[store.vendor_id] = { marker: marker, location: store.business_address }; 
        }
    });

    // 2. Fit the Map to ALL Markers (including the user marker)
    if (allStoreCoordinates.length > 0) {
        // Add the user's location to the coordinates list
        allStoreCoordinates.push(centerLatLng);
        
        const bounds = L.latLngBounds(allStoreCoordinates);
        
        // Use a small delay for stable rendering before fitting bounds
        setTimeout(() => {
            allStoresMapInstance.fitBounds(bounds, { padding: [50, 50] });
        }, 100);
    }
}

// ------------------------------------
// --- Filter Function (with Smart Zoom) ---
// ------------------------------------

function filterStoresAndMapMarkers(selectedLocation, allStoresData) {
    const storeCards = document.querySelectorAll('.store-card-large');
    const filteredCoordinates = [];
    
    // 1. Prepare Filter Value for Robust Matching
    let filterValue = selectedLocation.toLowerCase();
    
    // Simplify the filter value to match the keys in BATANGAS_LOCATIONS_MAP (e.g., 'lipa city' -> 'lipa')
    // Keep 'province' as is if it's the selected value
    let locationKey = filterValue.replace(/\s+city/g, '').trim(); 

    // Use 'province' key for 'all' selection (assuming 'all' or 'all-batangas' is used in the dropdown)
    if (locationKey === 'all' || locationKey === 'all-batangas' || locationKey === 'province') {
        locationKey = 'province';
    }
    
    // 2. Clear Existing Markers
    for (const vendorId in storeMarkers) {
        if (storeMarkers[vendorId].marker) {
            allStoresMapInstance.removeLayer(storeMarkers[vendorId].marker);
        }
    }
    
    // 3. Filter Store Cards and Markers
    storeCards.forEach(card => {
        // Retrieve and standardize the full address from the card's data-location
        const storeLocation = card.getAttribute('data-location') ? card.getAttribute('data-location').toLowerCase() : '';
        const vendorId = card.getAttribute('data-vendor-id');
        let marker = storeMarkers[vendorId] ? storeMarkers[vendorId].marker : null;

        // Check if the full address includes the simplified filter value
        const shouldBeVisible = locationKey === 'province' || (storeLocation && storeLocation.includes(locationKey));

        if (shouldBeVisible) {
            card.style.display = '';
            if (marker) {
                marker.addTo(allStoresMapInstance);
                filteredCoordinates.push(marker.getLatLng());
            }
        } else {
            card.style.display = 'none';
        }
    });

    // 4. Adjust Map View based on filter results
    if (filteredCoordinates.length > 0 && locationKey !== 'province') {
        // If stores are found AND a specific location is filtered, prioritize fitting the map exactly to those stores
        const bounds = L.latLngBounds(filteredCoordinates);
        
        setTimeout(() => {
            allStoresMapInstance.fitBounds(bounds, { padding: [50, 50] });
        }, 100);

    } else if (locationKey === 'province' || filteredCoordinates.length === 0) {
        // Zoom out to cover the entire province OR zoom to the selected location's default view
        const targetData = BATANGAS_LOCATIONS_MAP[locationKey] || BATANGAS_LOCATIONS_MAP['province'];
        allStoresMapInstance.setView(targetData.center, targetData.zoom);
    }
}
document.addEventListener('DOMContentLoaded', () => {
    const profileBtn = document.getElementById('profileBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');

    const notificationBtn = document.getElementById('notificationBtn');
    const notificationModal = document.getElementById('notificationModal');

    // Toggle profile dropdown
    if (profileBtn && dropdownMenu) {
        profileBtn.setAttribute('aria-haspopup', 'true');
        profileBtn.setAttribute('aria-expanded', 'false');

        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const opened = dropdownMenu.style.display === 'block';
            dropdownMenu.style.display = opened ? 'none' : 'block';
            profileBtn.setAttribute('aria-expanded', String(!opened));
        });
    }

    // Toggle notifications
    if (notificationBtn && notificationModal) {
        notificationBtn.setAttribute('aria-haspopup', 'true');
        notificationBtn.setAttribute('aria-expanded', 'false');

        notificationBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const opened = notificationModal.style.display === 'block';
            notificationModal.style.display = opened ? 'none' : 'block';
            notificationBtn.setAttribute('aria-expanded', String(!opened));
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (dropdownMenu && profileBtn && !dropdownMenu.contains(e.target) && !profileBtn.contains(e.target)) {
            dropdownMenu.style.display = 'none';
            profileBtn.setAttribute('aria-expanded', 'false');
        }
        if (notificationModal && notificationBtn && !notificationModal.contains(e.target) && !notificationBtn.contains(e.target)) {
            notificationModal.style.display = 'none';
            notificationBtn.setAttribute('aria-expanded', 'false');
        }
    });

    // Close on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (dropdownMenu) {
                dropdownMenu.style.display = 'none';
                if (profileBtn) profileBtn.setAttribute('aria-expanded', 'false');
            }
            if (notificationModal) {
                notificationModal.style.display = 'none';
                if (notificationBtn) notificationBtn.setAttribute('aria-expanded', 'false');
            }
        }
    });
});
