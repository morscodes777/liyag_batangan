let map, geocoder;
    let selectedLat = null;
    let selectedLng = null;
    let selectedAddress = '';
    let addressUpdateTimer;
    let geocoderTimeout;

    const mapModal = document.getElementById('mapModal');
    const closeMapModalBtn = document.getElementById('closeMapModal'); 
    const confirmLocationBtn = document.getElementById('confirmLocationBtn');
    const addressDisplay = document.getElementById('address-display');
    const newFullAddressInput = document.getElementById('new_full_address');
    const newLatitudeInput = document.getElementById('new_latitude');
    const newLongitudeInput = document.getElementById('new_longitude');

    const GoldIcon = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-gold.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    function toggleSelectButton(isReady) {
        if (confirmLocationBtn) {
            confirmLocationBtn.disabled = !isReady;
            confirmLocationBtn.textContent = isReady ? 'Select Location' : 'Determining Address...';
        }
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
            .then(response => response.ok ? response.json() : Promise.reject(`Proxy error: Status ${response.status}`))
            .then(data => {
                clearTimeout(geocoderTimeout);
                if (didTimeout) return;

                if (data && data.display_name) {
                    selectedAddress = data.display_name;
                    if (addressDisplay) addressDisplay.textContent = selectedAddress;
                } else if (data && data.address) {
                    const a = data.address;
                    const parts = [
                        a.house_number, a.road, a.neighbourhood, 
                        a.suburb || a.village, a.city || a.town || a.municipality,
                        a.state, a.postcode
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
                selectedAddress = `Lat: ${selectedLat}, Lng: ${selectedLng} (Address lookup failed)`;
                if (addressDisplay) addressDisplay.textContent = 'Error fetching address.';
                toggleSelectButton(true);
            });

        }, 300);
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

    function selectLocation() {
        if (selectedLat && selectedLng && selectedAddress) {
            let finalAddress = selectedAddress;
            if (finalAddress.includes('failed') || finalAddress.includes('not found')) {
                finalAddress = `Location selected by coordinates: Lat ${selectedLat}, Lng ${selectedLng}`;
            }
            
            newFullAddressInput.value = finalAddress.trim();
            newLatitudeInput.value = selectedLat;
            newLongitudeInput.value = selectedLng;
            
            closeMapModal();
            const checkAddressValidity = window.checkAddressValidity || (() => {});
            checkAddressValidity();
        } else {
            alert("Location data is missing. Please ensure the map is loaded and try again.");
        }
    }

    // --- NEW FUNCTION TO UPDATE STEP 3 PAYMENT REVIEW ---
    function updatePaymentReview() {
        const checkoutForm = document.getElementById('checkout-form');
        const paymentReviewDiv = document.getElementById('payment-method-review');
        const selectedPayment = checkoutForm.querySelector('input[name="payment_method"]:checked');
        
        if (!paymentReviewDiv) return;

        if (selectedPayment) {
            let label = '';
            let details = '';
            let icon = '';

            if (selectedPayment.value === 'COD') {
                label = 'Cash on Delivery (COD)';
                details = 'Pay with cash upon delivery.';
                icon = '<i class="bi bi-cash-stack me-2"></i>';
            } else if (selectedPayment.value === 'GCash') {
                label = 'GCash (Online)';
                details = 'Payment will be made using GCash. You will be redirected.';
                icon = '<i class="bi bi-phone-fill me-2"></i>';
            }

            paymentReviewDiv.innerHTML = `
                <div class="payment-summary-box">
                    <p class="selected-method-label">${icon}<strong>${label}</strong></p>
                    <p class="selected-method-details">${details}</p>
                </div>
            `;
        } else {
            paymentReviewDiv.innerHTML = '<p class="no-selection-msg">Please select a payment method in Step 2.</p>';
        }
    }
    // --- END NEW FUNCTION ---


    document.addEventListener('DOMContentLoaded', function() {
        const steps = document.querySelectorAll('.step-card');
        const nextButtons = document.querySelectorAll('.next-step-btn');
        const prevButtons = document.querySelectorAll('.prev-step-btn');
        
        const addressSelect = document.getElementById('delivery_address_id');
        const firstNextBtn = document.querySelector('.step-card[data-step="1"] .next-step-btn');
        const newAddressFormContainer = document.getElementById('new-address-form-container');
        
        const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
        const secondNextBtn = document.querySelector('.step-card[data-step="2"] .next-step-btn');
        
        let currentStep = 1;

        const updateSteps = () => {
            steps.forEach(step => {
                const stepNum = parseInt(step.dataset.step);
                step.classList.remove('active');
                if (stepNum === currentStep) {
                    step.classList.add('active');
                } else if (stepNum < currentStep) {
                    step.classList.add('completed');
                } else {
                    step.classList.remove('completed');
                }
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };

        const checkAddressValidity = () => {
            if (!addressSelect || !firstNextBtn) return;

            const selectedValue = addressSelect.value;
            let isValid = false;

            if (selectedValue === 'new_address') {
                isValid = newFullAddressInput.value.trim() !== '' && 
                            newLatitudeInput.value !== '' && 
                            newLongitudeInput.value !== '';
            } else if (selectedValue !== '') {
                isValid = true;
            }

            firstNextBtn.disabled = !isValid;
        };
        
        window.checkAddressValidity = checkAddressValidity; 

        updateSteps();
        checkAddressValidity();

        if (addressSelect) {
            addressSelect.addEventListener('change', function() {
                if (this.value === 'new_address') {
                    newAddressFormContainer.style.display = 'block';
                } else {
                    newAddressFormContainer.style.display = 'none';
                }
                checkAddressValidity();
            });

            if (addressSelect.value === 'new_address') {
                newAddressFormContainer.style.display = 'block';
            } else {
                newAddressFormContainer.style.display = 'none';
            }
        }

        if (newFullAddressInput) {
            newFullAddressInput.addEventListener('click', openMapModal);
            newFullAddressInput.addEventListener('keydown', (e) => e.preventDefault());
            newFullAddressInput.addEventListener('input', checkAddressValidity);
        }

        if (closeMapModalBtn) closeMapModalBtn.addEventListener('click', closeMapModal);
        if (confirmLocationBtn) confirmLocationBtn.addEventListener('click', selectLocation);
        window.addEventListener('click', (event) => {
            if (event.target === mapModal) closeMapModal();
        });

        paymentOptions.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    secondNextBtn.disabled = false;
                    // Optional: Update review immediately on change, even if not moving to next step
                    // updatePaymentReview(); 
                }
            });
        });

        nextButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const nextStep = parseInt(btn.dataset.next);
                
                // --- MODIFIED LOGIC FOR STEP 2 -> STEP 3 TRANSITION ---
                if (currentStep === 2 && nextStep === 3 && !btn.disabled) {
                    updatePaymentReview();
                }
                // --- END MODIFIED LOGIC ---

                if (!btn.disabled && nextStep > currentStep) {
                    currentStep = nextStep;
                    updateSteps();
                }
            });
        });

        prevButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const prevStep = parseInt(btn.dataset.prev);
                if (prevStep < currentStep) {
                    currentStep = prevStep;
                    updateSteps();
                }
            });
        });
    });

    const placeOrderBtn = document.getElementById("placeOrderBtn");

    if (placeOrderBtn) {
        placeOrderBtn.addEventListener("click", function(event) {
            event.preventDefault(); // Stop the default form submission (important!)
            console.log("🟡 Place Order button clicked — preparing request...");

            const checkoutForm = document.getElementById("checkout-form");
            const selectedPaymentMethod = checkoutForm.querySelector('input[name="payment_method"]:checked')?.value;
            
            if (!checkoutForm || !selectedPaymentMethod) {
                console.error("❌ Cannot find checkout form or payment method is not selected.");
                alert("Please complete all steps and select a payment method.");
                return;
            }

            const formData = new FormData(checkoutForm);
            formData.append('final_checkout', '1'); // Ensure this flag is always sent

            // Disable button and change text while processing
            placeOrderBtn.disabled = true;
            let originalText = placeOrderBtn.innerHTML;

            if (selectedPaymentMethod === 'GCash') {
                placeOrderBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Redirecting to GCash...';
            } else if (selectedPaymentMethod === 'COD') {
                placeOrderBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Placing COD Order...';
            }

            fetch("app/controllers/placeOrderController.php", {
                method: "POST",
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server returned error status: ' + response.status);
                }
                return response.json(); 
            })
            .then(data => {
                console.log("✅ Response from server:", data);
                
                if (data.success && data.redirect_url) {
                    // This handles successful COD placement OR successful PayMongo initiation
                    window.location.href = data.redirect_url;
                } else {
                    alert(data.message || "Order placement failed on the server.");
                    // Re-enable button on failure
                    placeOrderBtn.disabled = false;
                    placeOrderBtn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error("❌ Place order failed:", err);
                alert("An unexpected error occurred. Please try again. Check console for details.");
                // Re-enable button on failure
                placeOrderBtn.disabled = false;
                placeOrderBtn.innerHTML = originalText;
            });
        });
    }