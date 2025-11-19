let map2, geocoder2;
let selectedLat2 = null;
let selectedLng2 = null;
let selectedAddress2 = '';
let addressUpdateTimer2;
let geocoderTimeout2;

const mapModal2 = document.getElementById('mapModal2');
const closeMapModalBtn2 = document.getElementById('closeMapModal2'); 
const confirmLocationBtn2 = document.getElementById('confirmLocationBtn2');
const addressDisplay2 = document.getElementById('address-display-2');

// You need to define these new inputs in your HTML/PHP form
const newFullAddressInput2 = document.getElementById('new_full_address_2');
const newLatitudeInput2 = document.getElementById('new_latitude_2');
const newLongitudeInput2 = document.getElementById('new_longitude_2');

// Icon remains the same as it's a shared resource (no need to redefine, but kept for completeness)
const GoldIcon = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-gold.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

function toggleSelectButton2(isReady) {
    if (confirmLocationBtn2) {
        confirmLocationBtn2.disabled = !isReady;
        confirmLocationBtn2.textContent = isReady ? 'Select Location' : 'Determining Address...';
    }
}

function updateAddress2(lat, lng) {
    clearTimeout(addressUpdateTimer2);
    clearTimeout(geocoderTimeout2);

    toggleSelectButton2(false);
    
    if (addressDisplay2) addressDisplay2.textContent = 'Fetching address...';

    addressUpdateTimer2 = setTimeout(() => {
        selectedLat2 = lat.toFixed(8);
        selectedLng2 = lng.toFixed(8);

        const proxyUrl = `public/api/nominatim_proxy.php?lat=${selectedLat2}&lon=${selectedLng2}`;

        let didTimeout = false;
        geocoderTimeout2 = setTimeout(() => {
            didTimeout = true;
            selectedAddress2 = `Lat: ${selectedLat2}, Lng: ${selectedLng2} (Address lookup failed/timed out)`;
            if (addressDisplay2) addressDisplay2.textContent = 'Address lookup failed/timed out.';
            toggleSelectButton2(true);
        }, 6000);

        fetch(proxyUrl)
        .then(response => response.ok ? response.json() : Promise.reject(`Proxy error: Status ${response.status}`))
        .then(data => {
            clearTimeout(geocoderTimeout2);
            if (didTimeout) return;

            if (data && data.display_name) {
                selectedAddress2 = data.display_name;
                if (addressDisplay2) addressDisplay2.textContent = selectedAddress2;
            } else if (data && data.address) {
                const a = data.address;
                const parts = [
                    a.house_number, a.road, a.neighbourhood, 
                    a.suburb || a.village, a.city || a.town || a.municipality,
                    a.state, a.postcode
                ].filter(Boolean);
                selectedAddress2 = parts.join(', ') || `Lat: ${selectedLat2}, Lng: ${selectedLng2}`;
                if (addressDisplay2) addressDisplay2.textContent = selectedAddress2;
            } else {
                selectedAddress2 = `Lat: ${selectedLat2}, Lng: ${selectedLng2} (Address not found)`;
                if (addressDisplay2) addressDisplay2.textContent = 'Address not found.';
            }

            toggleSelectButton2(true);
        })
        .catch(err => {
            clearTimeout(geocoderTimeout2);
            selectedAddress2 = `Lat: ${selectedLat2}, Lng: ${selectedLng2} (Address lookup failed)`;
            if (addressDisplay2) addressDisplay2.textContent = 'Error fetching address.';
            toggleSelectButton2(true);
        });

    }, 300);
}

function initializeMap2(centerLatLng) {
    const mapElement = document.getElementById('map2');
    if (!mapElement) return;

    if (map2) {
        map2.setView(centerLatLng, 16);
        map2.invalidateSize(true);
        updateAddress2(centerLatLng.lat, centerLatLng.lng);
        return;
    }

    map2 = L.map(mapElement, {
        center: centerLatLng,
        zoom: 16,
        zoomControl: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map2);

    if (typeof L.Control.Geocoder !== 'undefined') {
        geocoder2 = L.Control.Geocoder.nominatim();
        L.Control.geocoder({
            geocoder: geocoder2,
            defaultMarkGeocode: false,
            placeholder: "Search for address...",
            collapsed: true,
            position: 'topright'
        })
        .on('markgeocode', function (e) {
            const center = e.geocode.center;
            map2.flyTo(center, 16);
            updateAddress2(center.lat, center.lng);
        })
        .addTo(map2);
    } else {
        console.error("Leaflet Control Geocoder not loaded for Map 2.");
    }

    map2.on('moveend', function () {
        const center = map2.getCenter();
        updateAddress2(center.lat, center.lng);
    });

    map2.whenReady(() => {
        map2.invalidateSize(true);
        updateAddress2(centerLatLng.lat, centerLatLng.lng);
    });
}

function openMapModal2() {
    const centerPin = document.getElementById('center-marker-2');
    const mapEl = document.getElementById('map2');
    
    if (!mapModal2 || !mapEl) return;

    // Show modal and lock aria
    mapModal2.style.display = 'flex';
    mapModal2.classList.add('open');
    mapModal2.removeAttribute('aria-hidden');
    mapModal2.setAttribute('aria-modal', 'true');
    
    // CRITICAL JS REFLOW FIX
    mapModal2.offsetWidth; 

    if (centerPin) centerPin.style.display = 'block';

    // Ensure the map container has an explicit height
    mapEl.style.minHeight = '420px';
    mapEl.style.height = '60vh';

    const defaultLatLng = [13.9442, 121.1565];
    let initialLatLng = defaultLatLng;

    const existingLat = newLatitudeInput2?.value;
    const existingLng = newLongitudeInput2?.value;

    if (existingLat && existingLng) {
        initialLatLng = [parseFloat(existingLat), parseFloat(existingLng)];
    }
    
    // Wait for modal layout to stabilize
    setTimeout(() => {
        const initialMapCenter = L.latLng(initialLatLng[0], initialLatLng[1]);

        if (map2) {
            // Map exists: update view and force reflow
            try { map2.invalidateSize(true); } catch(e){/*ignore*/ }
            map2.setView(initialMapCenter, 16);
            updateAddress2(initialMapCenter.lat, initialMapCenter.lng);
        } else {
            // Map does not exist: initialize it (geolocation or fallback)
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLatLng = [position.coords.latitude, position.coords.longitude];
                        initializeMap2(L.latLng(userLatLng[0], userLatLng[1]));
                    },
                    () => {
                        initializeMap2(L.latLng(initialLatLng[0], initialLatLng[1]));
                    },
                    { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                );
            } else {
                initializeMap2(L.latLng(initialLatLng[0], initialLatLng[1]));
            }
        }

        // After map is ready, invalidate size again 
        setTimeout(() => { if (map2) { try { map2.invalidateSize(true); } catch(e){} } }, 250);
        if (confirmLocationBtn2) confirmLocationBtn2.focus();
    }, 40); 
}

function closeMapModal2() {
    if (!mapModal2) return;

    mapModal2.style.display = 'none';
    mapModal2.classList.remove('open');
    mapModal2.setAttribute('aria-hidden', 'true');
    mapModal2.removeAttribute('aria-modal');

    const centerPin = document.getElementById('center-marker-2');
    if (centerPin) centerPin.style.display = 'none';
    
    if (newFullAddressInput2) newFullAddressInput2.focus(); 
}

function selectLocation2() {
    if (selectedLat2 && selectedLng2 && selectedAddress2) {
        let finalAddress = selectedAddress2;
        if (finalAddress.includes('failed') || finalAddress.includes('not found')) {
            finalAddress = `Location selected by coordinates: Lat ${selectedLat2}, Lng ${selectedLng2}`;
        }
        
        newFullAddressInput2.value = finalAddress.trim();
        newLatitudeInput2.value = selectedLat2;
        newLongitudeInput2.value = selectedLng2;
        
        closeMapModal2();
        // If you have a second validation function, call it here
        // window.checkAddressValidity2?.(); 
    } else {
        alert("Location data is missing for the second address. Please ensure the map is loaded and try again.");
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (newFullAddressInput2) {
        newFullAddressInput2.addEventListener('click', openMapModal2);
        newFullAddressInput2.addEventListener('keydown', (e) => e.preventDefault());
    }

    if (closeMapModalBtn2) closeMapModalBtn2.addEventListener('click', closeMapModal2);
    if (confirmLocationBtn2) confirmLocationBtn2.addEventListener('click', selectLocation2);
    window.addEventListener('click', (event) => {
        if (event.target === mapModal2) closeMapModal2();
    });
});