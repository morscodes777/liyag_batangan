let map, geocoder;
let selectedLat = null;
let selectedLng = null;
let selectedAddress = '';
let addressUpdateTimer;
let geocoderTimeout;
let originalValues = {};
let isNameValid = true; 
let isPhoneValid = true;

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
                setTimeout(() => { if (map) map.invalidateSize(true); if (confirmBtn) confirmBtn.focus(); }, 300);
            },
            () => {
                initializeMap(L.latLng(initialLatLng[0], initialLatLng[1]));
                setTimeout(() => { if (map) map.invalidateSize(true); if (confirmBtn) confirmBtn.focus(); }, 300);
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    } else {
        initializeMap(L.latLng(initialLatLng[0], initialLatLng[1]));
        setTimeout(() => { if (map) map.invalidateSize(true); if (confirmBtn) confirmBtn.focus(); }, 300);
    }
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
    const addressDisplay = document.getElementById('address-display');
    if (addressDisplay) addressDisplay.textContent = 'Fetching address...';

    addressUpdateTimer = setTimeout(() => {
        selectedLat = lat.toFixed(6);
        selectedLng = lng.toFixed(6);

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
        document.getElementById("latitude").value = selectedLat;
        document.getElementById("longitude").value = selectedLng;
        
        let finalAddress = selectedAddress;
        if (finalAddress.includes('(Address lookup failed/timed out)') || finalAddress.includes('(Address not found)') || finalAddress.includes('(Address lookup failed)')) {
            finalAddress = `Location selected by coordinates: Lat ${selectedLat}, Lng ${selectedLng}`;
        }
        
        document.getElementById("address").value = finalAddress.trim();
        
        const viewAddressElement = document.querySelector('.profile-value.view-mode[data-field="address"]');
        if (viewAddressElement) {
            viewAddressElement.textContent = finalAddress.trim();
        }

        originalValues['address'] = finalAddress.trim();
        document.getElementById("latitude").value = selectedLat;
        originalValues['latitude'] = selectedLat;
        document.getElementById("longitude").value = selectedLng;
        originalValues['longitude'] = selectedLng;
        
        closeMapModal();
    } else {
        alert("Location data is missing. Please ensure the map is loaded and try again.");
    }
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("closeMapModal").addEventListener("click", closeMapModal);
    document.getElementById("confirmLocationBtn").addEventListener("click", selectLocation); 
    window.openMapModal = openMapModal;

    const profileBtn = document.getElementById("profileBtn");
    const dropdownMenu = document.getElementById("dropdownMenu");

    if (profileBtn && dropdownMenu) {
        profileBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
        });
    }
const editBtn = document.getElementById("editBtn");
    const cancelBtn = document.getElementById("cancelBtn");
    const saveBtn = document.getElementById("saveBtn");
    const cancelModal = document.getElementById("cancelModal");
    const confirmCancel = document.getElementById("confirmCancel");
    const closeCancelModal = document.getElementById("closeCancelModal");

    const viewFields = document.querySelectorAll(".profile-info .view-mode");
    const editFields = document.querySelectorAll(".profile-info .edit-mode");

    const nameInput = document.getElementById('name');
    const phoneInput = document.getElementById('phone_number');

    if (nameInput) {
        setError(nameInput, 'name-error', true);

        nameInput.addEventListener('input', function() {
            nameInput.value = nameInput.value.replace(/\d/g, ''); 
            
            let nameValue = nameInput.value.trim();
            const isLengthValid = nameValue.length >= 8 && nameValue.length <= 25;
            const containsNumbers = /\d/.test(nameValue);
            const isValid = isLengthValid && !containsNumbers;

            const errorElement = document.getElementById('name-error');
            
            if (!isValid) {
                 if (!isLengthValid) {
                    errorElement.innerText = 'Full Name must be between 8 and 25 characters.';
                } else if (containsNumbers) {
                    errorElement.innerText = 'Full Name cannot contain numbers.';
                }
            }
            
            setError(nameInput, 'name-error', isValid);
        });
    }

    if (phoneInput) {
        setError(phoneInput, 'phone-error', true);

        phoneInput.addEventListener('input', function() {
            let value = phoneInput.value.replace(/\D/g, '');

            if (value.length === 0) {
                phoneInput.value = '';
                setError(phoneInput, 'phone-error', true);
                return;
            }
            
            // Clean up existing country codes
            if (value.startsWith('63')) {
                value = value.substring(2);
            }
            if (value.startsWith('0')) {
                value = value.substring(1);
            }

            // CRITICAL FIX: Ensure value starts with '9' (Philippine mobile prefix)
            if (!value.startsWith('9') && value.length > 0) {
                value = '9' + value.substring(0); 
            }

            value = value.substring(0, 10);
            
            let formattedValue = '+63 ';

            if (value.length > 3) {
                formattedValue += value.substring(0, 3) + ' ';
                if (value.length > 6) {
                    formattedValue += value.substring(3, 6) + ' ';
                    formattedValue += value.substring(6, 10);
                } else {
                    formattedValue += value.substring(3);
                }
            } else {
                formattedValue += value;
            }

            phoneInput.value = formattedValue.trim();
            
            const isLengthValid = value.length === 10;
            const isMobilePrefixValid = value.startsWith('9');
            
            // CRITICAL FIX: Re-calculate isValid and call setError here
    
        });
    }

    editBtn.addEventListener("click", () => {
        editFields.forEach(input => {
            originalValues[input.name] = input.value;
        });

        viewFields.forEach(v => {
            if (v.hasAttribute('data-field')) {
                const fieldName = v.getAttribute('data-field');
                originalValues[fieldName] = v.textContent.trim();
            }
            v.style.display = "none";
        });

        editFields.forEach(e => e.style.display = "block");
        editBtn.style.display = "none";
        cancelBtn.style.display = "inline-block";
        saveBtn.style.display = "inline-block";

        const profilePicInput = document.getElementById('profile_picture');
        if (profilePicInput) {
             profilePicInput.style.display = 'block';
        }
    });

    cancelBtn.addEventListener("click", () => {
        let hasChanges = false;
        editFields.forEach(input => {
            if (input.type !== 'hidden' && input.value !== originalValues[input.name]) {
                hasChanges = true;
            }
        });

        if (document.getElementById('address').value !== originalValues['address'] || 
            document.getElementById('latitude').value !== originalValues['latitude'] ||
            document.getElementById('longitude').value !== originalValues['longitude']) {
                hasChanges = true;
        }

        if (hasChanges) {
            cancelModal.style.display = "flex";
        } else {
            toggleToViewMode();
        }
    });

    confirmCancel.addEventListener("click", () => {
        editFields.forEach(input => {
            input.value = originalValues[input.name] || '';
        });
        
        viewFields.forEach(viewElement => {
            const fieldName = viewElement.getAttribute('data-field');
            if (fieldName) {
                 viewElement.textContent = originalValues[fieldName] || '';
            }
        });

        const currentProfilePicSrc = document.getElementById('previewImage').getAttribute('data-original-src');
        document.getElementById('previewImage').src = currentProfilePicSrc;

        cancelModal.style.display = "none";
        toggleToViewMode();

        if (nameInput) setError(nameInput, 'name-error', true);
        if (phoneInput) setError(phoneInput, 'phone-error', true);
    });

    closeCancelModal.addEventListener("click", () => {
        cancelModal.style.display = "none";
    });

    saveBtn.addEventListener("click", (e) => {
        e.preventDefault();
        
        nameInput.dispatchEvent(new Event('input')); 
        phoneInput.dispatchEvent(new Event('input')); 

        if (!isNameValid || !isPhoneValid) {
            alert("Please fix the errors in your profile before saving.");
            return;
        }

        const formData = new FormData(document.getElementById('profileForm'));

        const submittedPhone = formData.get('phone_number');
        if (submittedPhone) {
            const cleanedPhone = submittedPhone.replace(/\D/g, ''); 
            formData.set('phone_number', cleanedPhone);
        }

        fetch("index.php?action=update_profile", {
            method: "POST",
            body: formData
        }).then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || "Update failed. Please check server logs.");
            }
        }).catch(() => {
            alert("Network error: Could not connect to the server.");
        });
    });

    function toggleToViewMode() {
        viewFields.forEach(v => v.style.display = "block");
        editFields.forEach(e => e.style.display = "none");
        editBtn.style.display = "inline-block";
        cancelBtn.style.display = "none";
        saveBtn.style.display = "none";
        
        const profilePicInput = document.getElementById('profile_picture');
        if (profilePicInput) {
             profilePicInput.style.display = 'none';
        }
    }

    document.getElementById('profile_picture').addEventListener('change', function(event) {
        const reader = new FileReader();
        reader.onload = function() {
            document.getElementById('previewImage').src = reader.result;
        };
        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    });

    const previewImage = document.getElementById('previewImage');
    if (previewImage) {
        previewImage.setAttribute('data-original-src', previewImage.src);
    }

    const notificationBtn = document.getElementById('notificationBtn');
    const notificationModal = document.getElementById('notificationModal');

    if (notificationBtn && notificationModal) {
        notificationBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationModal.style.display = 
                notificationModal.style.display === 'block' ? 'none' : 'block';
        });
    }

    document.addEventListener("click", (e) => {
        if (dropdownMenu && profileBtn && !dropdownMenu.contains(e.target) && !profileBtn.contains(e.target)) {
            dropdownMenu.style.display = "none";
        }

        if (notificationModal && notificationBtn && !notificationModal.contains(e.target) && !notificationBtn.contains(e.target)) {
            notificationModal.style.display = 'none';
        }
        
        const mapModal = document.getElementById('mapModal');
        const mapModalContent = document.querySelector('.map-modal-content');
        if (mapModal && mapModal.style.display === 'flex' && mapModalContent && !mapModalContent.contains(e.target) && e.target === mapModal) {
            closeMapModal();
        }
    });
});