let map, geocoder;
let selectedLat = null;
let selectedLng = null;
let selectedAddress = '';
let addressUpdateTimer;
let geocoderTimeout;
let originalValues = {};
let isNameValid = true; 
let isPhoneValid = true;

const mapModal = document.getElementById('mapModal');
const closeMapModalBtn = document.getElementById('closeMapModal'); 
const confirmLocationBtn = document.getElementById('confirmLocationBtn');
const businessAddressInput = document.getElementById('business_address');
const latitudeInput = document.getElementById('latitude');
const longitudeInput = document.getElementById('longitude');
const addressDisplay = document.getElementById('address-display');
const termsModal = document.getElementById('termsModal');
const closeTermsBtn = document.getElementById('closeTermsModal');

const GoldIcon = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-gold.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

function setError(inputElement, errorId, isValid) {
    const errorElement = document.getElementById(errorId);
    if (isValid) {
        inputElement.classList.remove('input-error');
        errorElement.style.display = 'none';
        errorElement.innerText = '';
    } else {
        inputElement.classList.add('input-error');
        errorElement.style.display = 'block';
    }
    
    if (errorId === 'name-error') {
        isNameValid = isValid;
    } else if (errorId === 'phone-error') {
        isPhoneValid = isValid;
    }
}

function toggleSelectButton(isReady) {
    const btn = document.getElementById('confirmLocationBtn');
    if (btn) {
        btn.disabled = !isReady;
        btn.textContent = isReady ? 'Select Location' : 'Determining Address...';
    }
}

function initializeMap(centerLatLng) {
    const mapElement = document.getElementById('map');
    if (!mapElement) return;

    if (map) {
        map.flyTo(centerLatLng, 16);
        map.invalidateSize(true); 
        updateAddress(centerLatLng.lat, centerLatLng.lng);
        return;
    }

    map = L.map('map').setView(centerLatLng, 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    if (typeof L.Control.Geocoder !== 'undefined') {
        geocoder = L.Control.Geocoder.nominatim();

        L.Control.geocoder({
            geocoder: geocoder,
            defaultMarkGeocode: false, 
            placeholder: "Search for address...",
            collapsed: true, 
            position: 'topright'
        })
        .on('markgeocode', function(e) {
            const center = e.geocode.center;
            map.flyTo(center, 16);
            updateAddress(center.lat, center.lng);
        })
        .addTo(map);
    } else {
        console.error("Leaflet Control Geocoder not loaded. Map search will not function.");
    }
    
    map.on('moveend', function() {
        const center = map.getCenter();
        updateAddress(center.lat, center.lng);
    });

    setTimeout(() => {
        if (map) {
            map.invalidateSize(true); 
            updateAddress(centerLatLng.lat, centerLatLng.lng);
        }
    }, 200); 
}

function openMapModal() {
    const mapModal = document.getElementById("mapModal");
    const centerPin = document.getElementById('center-marker');
    const confirmBtn = document.getElementById('confirmLocationBtn');

    if (!mapModal) return;

    mapModal.classList.add('open');
    mapModal.style.display = 'flex';
    document.body.classList.add('modal-open');

    mapModal.setAttribute('tabindex', '-1');
    mapModal.setAttribute('aria-hidden', 'false');
    mapModal.focus();

    if (centerPin) centerPin.style.display = 'block';

    const defaultLatLng = [13.7565, 121.0583]; 
    let initialLatLng = defaultLatLng;

    const latEl = document.getElementById('latitude');
    const lngEl = document.getElementById('longitude');
    const existingLat = latEl ? latEl.value : '';
    const existingLng = lngEl ? lngEl.value : '';

    if (existingLat && existingLng) {
        initialLatLng = [parseFloat(existingLat), parseFloat(existingLng)];
    }

    if (!map && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userLatLng = [position.coords.latitude, position.coords.longitude];
                initializeMap(L.latLng(userLatLng[0], userLatLng[1]));
            },
            () => {
                initializeMap(L.latLng(initialLatLng[0], initialLatLng[1]));
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    } else {
        initializeMap(L.latLng(initialLatLng[0], initialLatLng[1]));
    }
    
    setTimeout(() => { 
        if (map) {
            map.invalidateSize(true); 
        }
        if (confirmBtn) confirmBtn.focus();
    }, 10);
}

function closeMapModal() {
    const mapModal = document.getElementById("mapModal");
    if (!mapModal) return;

    mapModal.classList.remove('open');
    mapModal.style.display = 'none';
    mapModal.setAttribute('aria-hidden', 'true');
    mapModal.removeAttribute('tabindex');

    const centerPin = document.getElementById('center-marker');
    if (centerPin) centerPin.style.display = 'none';

    document.body.classList.remove('modal-open');

    const editBtn = document.getElementById('editBtn');
    if (editBtn) editBtn.focus();
}

function updateAddress(lat, lng) {
    clearTimeout(addressUpdateTimer);
    clearTimeout(geocoderTimeout);

    toggleSelectButton(false);
    
    if (addressDisplay) addressDisplay.textContent = 'Fetching address...';

    addressUpdateTimer = setTimeout(() => {
        selectedLat = lat.toFixed(8);
        selectedLng = lng.toFixed(8);

        const proxyUrl = `public/api/nominatim_proxy.php?lat=${selectedLat}&lon=${selectedLng}`;

        let didTimeout = false;
        geocoderTimeout = setTimeout(() => {
            didTimeout = true;
            selectedAddress = `Lat: ${selectedLat}, Lng: ${selectedLng} (Address lookup failed/timed out)`;
            if (addressDisplay) addressDisplay.textContent = 'Address lookup failed/timed out.';
            toggleSelectButton(true);
        }, 6000);

        fetch(proxyUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Proxy error: Status ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            clearTimeout(geocoderTimeout);
            if (didTimeout) return;

            if (data && data.display_name) {
                selectedAddress = data.display_name;
                if (addressDisplay) addressDisplay.textContent = selectedAddress;
            } else if (data && data.address) {
                const a = data.address;
                const parts = [
                    a.house_number,
                    a.road,
                    a.neighbourhood,
                    a.suburb || a.village,
                    a.city || a.town || a.municipality,
                    a.state,
                    a.postcode
                ].filter(Boolean);
                selectedAddress = parts.join(', ') || `Lat: ${selectedLat}, Lng: ${selectedLng}`;
                if (addressDisplay) addressDisplay.textContent = selectedAddress;
            } else {
                selectedAddress = `Lat: ${selectedLat}, Lng: ${selectedLng} (Address not found)`;
                if (addressDisplay) addressDisplay.textContent = 'Address not found.';
            }

            toggleSelectButton(true);
        })
        .catch(err => {
            clearTimeout(geocoderTimeout);
            console.error('Geocoding error:', err);
            selectedAddress = `Lat: ${selectedLat}, Lng: ${selectedLng} (Address lookup failed)`;
            if (addressDisplay) addressDisplay.textContent = 'Error fetching address.';
            toggleSelectButton(true);
        });

    }, 300);
}

function selectLocation() {
    if (selectedLat && selectedLng && selectedAddress) {
        
        let finalAddress = selectedAddress;
        if (finalAddress.includes('(Address lookup failed/timed out)') || finalAddress.includes('(Address not found)') || finalAddress.includes('(Address lookup failed)')) {
            finalAddress = `Location selected by coordinates: Lat ${selectedLat}, Lng ${selectedLng}`;
        }
        
        businessAddressInput.value = finalAddress.trim();
        latitudeInput.value = selectedLat;
        longitudeInput.value = selectedLng;
        
        closeMapModal();
    } else {
        alert("Location data is missing. Please ensure the map is loaded and try again.");
    }
}


businessAddressInput.addEventListener('click', openMapModal);

closeMapModalBtn.addEventListener('click', closeMapModal);

confirmLocationBtn.addEventListener('click', selectLocation);

window.addEventListener('click', (event) => {
    if (event.target === mapModal) {
        closeMapModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const termsCheckbox = document.getElementById('terms_agree');
    const submitButton = document.getElementById('submitBusinessBtn');
    const viewTermsLink = document.getElementById('viewTerms');

    // Function to handle closing the terms modal (called by button and outside click)
    const handleCloseTermsModal = () => {
        if (termsModal) {
            termsModal.style.display = 'none';
        }
        if (termsCheckbox) {
            termsCheckbox.checked = true;
            termsCheckbox.classList.add('checked-gold');
            submitButton.disabled = false; // Enable submit button since the box is checked
        }
    };

    if (viewTermsLink && termsModal && closeTermsBtn) {
        viewTermsLink.addEventListener('click', function(event) {
            event.preventDefault();
            termsModal.style.display = 'flex';
        });

        // Use the handler function for both close actions
        closeTermsBtn.addEventListener('click', handleCloseTermsModal);

        window.addEventListener('click', function(event) {
            if (event.target === termsModal) {
                handleCloseTermsModal();
            }
        });
    }

    if (termsCheckbox && submitButton) {
        // Initial state
        if (!termsCheckbox.checked) {
            submitButton.disabled = true;
        } else {
             termsCheckbox.classList.add('checked-gold');
        }

        // Listener for manual check/uncheck
        termsCheckbox.addEventListener('change', function() {
            submitButton.disabled = !this.checked;
            if (this.checked) {
                this.classList.add('checked-gold');
            } else {
                this.classList.remove('checked-gold');
            }
        });
    }
});