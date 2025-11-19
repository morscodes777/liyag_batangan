let map, geocoder;
let selectedLat = null;
let selectedLng = null;
let selectedAddress = '';
let addressUpdateTimer;
let geocoderTimeout; // Added for explicit geocoding timeout

// --- NEW VARIABLES FOR OTP FLOW ---
const otpModal = document.getElementById('otp-modal');
const otpForm = document.getElementById('otp-form');
const otpEmailDisplay = document.getElementById('otp-email-display');
const otpHiddenEmail = document.getElementById('otp-hidden-email');
const otpTimerDisplay = document.getElementById('otp-timer');
const resendOtpBtn = document.getElementById('resend-otp-btn');
const otpError = document.getElementById('otp-error');
const registerForm = document.getElementById('register-form');
const registerSubmitBtn = document.getElementById('register-submit-btn');
const verifyBtn = document.getElementById('verify-btn');

let otpTimerInterval;
let formSubmissionData = {}; // To store the form data temporarily
// -----------------------------------

const GoldIcon = new L.Icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-gold.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});


// (Keep showModal and closeModal functions as they are, but update success redirect)
function showModal(type, message) {
    const modal = document.getElementById('lottie-modal');
    const player = document.getElementById('lottie-animation');
    const text = document.getElementById('modal-message');
    const okBtn = modal.querySelector('button');

    // Clear previous timeout/redirect logic if not success
    clearTimeout(window.modalRedirectTimer);
    okBtn.onclick = closeModal; // Default action

    if (type === 'success') {
        player.setAttribute('src', 'public/assets/lotties/reg_success.json');
        
        // Custom logic for final registration success
        if (message.includes('Registration successful')) {
            okBtn.onclick = () => {
                closeModal();
                window.location.href = "index.php?action=login";
            };
            window.modalRedirectTimer = setTimeout(() => {
                closeModal();
                window.location.href = "index.php?action=login";
            }, 3000);
        } else {
            // General success message (like resend OTP)
            okBtn.onclick = closeModal;
        }

    } else {
        player.setAttribute('src', 'public/assets/lotties/reg_error.json');
        // If error, do not redirect
    }

    text.innerText = message;
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('lottie-modal').style.display = 'none';
}

// --- NEW OTP MODAL FUNCTIONS ---

function openOtpModal(email) {
    otpEmailDisplay.textContent = email;
    otpHiddenEmail.value = email;
    otpModal.style.display = 'flex';
    startOtpTimer(300); // 5 minutes (300 seconds)
}

function closeOtpModal() {
    otpModal.style.display = 'none';
    clearInterval(otpTimerInterval);
    otpForm.reset();
    otpError.textContent = '';
    resendOtpBtn.disabled = true;
    resendOtpBtn.textContent = 'Resend Code (Wait)';
}

function startOtpTimer(duration) {
    let timer = duration;
    let minutes, seconds;
    
    clearInterval(otpTimerInterval);
    resendOtpBtn.disabled = true;

    otpTimerInterval = setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        otpTimerDisplay.textContent = minutes + ":" + seconds;

        if (--timer < 0) {
            clearInterval(otpTimerInterval);
            otpTimerDisplay.textContent = "00:00";
            resendOtpBtn.disabled = false;
            resendOtpBtn.textContent = 'Resend Code';
            showModal('error', 'The OTP code has expired. Please resend the code.');
        }
    }, 1000);
}


// --- NEW AJAX SUBMISSION FUNCTION ---

// Function to handle the AJAX requests
async function submitForm(url, data, button) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-arrow-clockwise spinner-border-sm"></i> Loading...';

    try {
        let fetchOptions = { method: 'POST', credentials: 'same-origin' }; // <-- added credentials

        if (data instanceof FormData) {
            fetchOptions.body = data;
        } else {
            fetchOptions.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
            fetchOptions.body = new URLSearchParams(data);
        }

        // Optionally use absolute path if relative fails:
        // const requestUrl = url.startsWith('/') ? url : '/' + url;
        const response = await fetch(url, fetchOptions);

        if (!response.ok) {
            throw new Error('Server responded with an error: ' + response.status);
        }
        return await response.json();
    } catch (error) {
        console.error("AJAX Error:", error);
        return { status: 'error', message: 'Network or server error. Please try again.' };
    } finally {
        button.disabled = false;
        button.innerHTML = originalText;
    }
}
// ------------------------------------


function toggleSelectButton(isReady) {
    const btn = document.getElementById('select-location-btn');
    if (btn) {
        btn.disabled = !isReady;
        btn.textContent = isReady ? 'Select Location' : 'Determining Address...';
    }
}

// --- UPDATED initializeMap (No changes needed, map logic is intact) ---
function initializeMap(centerLatLng) {
    if (map) {
        map.setView(centerLatLng, 16);
        map.invalidateSize();
        setTimeout(() => updateAddress(centerLatLng.lat, centerLatLng.lng), 300);
        return;
    }

    map = L.map('map').setView(centerLatLng, 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    geocoder = L.Control.Geocoder.nominatim();
    
    L.Control.geocoder({
        geocoder: geocoder,
        defaultMarkGeocode: false, 
        placeholder: "Search for address...",
        collapsed: true, 
        position: 'bottomleft' 
    })
    .on('markgeocode', function(e) {
        const center = e.geocode.center;
        map.flyTo(center, 16); 
    })
    .addTo(map);

    map.on('moveend', function() {
        const center = map.getCenter();
        updateAddress(center.lat, center.lng);
    });

    setTimeout(() => {
        map.invalidateSize();
        updateAddress(centerLatLng.lat, centerLatLng.lng);
    }, 300);
}

function openMapModal() {
    document.getElementById("mapModal").style.display = "flex";
    
    const centerPin = document.getElementById('center-marker');
    if (centerPin) {
        centerPin.style.display = 'block';
    }
    
    const defaultLatLng = [13.7565, 121.0583]; 
    let initialLatLng = defaultLatLng;
    
    const existingLat = document.getElementById('latitude').value;
    const existingLng = document.getElementById('longitude').value;

    if (existingLat && existingLng) {
        initialLatLng = [parseFloat(existingLat), parseFloat(existingLng)];
    }
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userLatLng = [position.coords.latitude, position.coords.longitude];
                initializeMap(L.latLng(userLatLng[0], userLatLng[1]));
            },
            (error) => {
                initializeMap(L.latLng(initialLatLng[0], initialLatLng[1]));
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    } else {
        initializeMap(L.latLng(initialLatLng[0], initialLatLng[1]));
    }
}

function closeMapModal() {
    document.getElementById("mapModal").style.display = "none";
    const centerPin = document.getElementById('center-marker');
    if (centerPin) {
        centerPin.style.display = 'none';
    }
}


function updateAddress(lat, lng) {
    clearTimeout(addressUpdateTimer);
    clearTimeout(geocoderTimeout);

    toggleSelectButton(false);
    document.getElementById('address-display').textContent = 'Fetching address...';

    addressUpdateTimer = setTimeout(() => {
        selectedLat = lat.toFixed(6);
        selectedLng = lng.toFixed(6);

        // --- CHANGE 1: Use the local PHP proxy URL ---
        // IMPORTANT: Replace 'api/nominatim_proxy.php' with the actual path to your PHP file
        const proxyUrl = `public/api/nominatim_proxy.php?lat=${selectedLat}&lon=${selectedLng}`;
        // ---------------------------------------------

        let didTimeout = false;
        geocoderTimeout = setTimeout(() => {
            didTimeout = true;
            selectedAddress = `Lat: ${selectedLat}, Lng: ${selectedLng} (Address lookup failed/timed out)`;
            document.getElementById('address-display').textContent = 'Address lookup failed/timed out.';
            toggleSelectButton(true);
        }, 6000);

        // --- CHANGE 2: Call the proxy instead of the Nominatim API directly ---
        fetch(proxyUrl)
        .then(response => {
            if (!response.ok) {
                 // The proxy handles the error status, but we check here too.
                 throw new Error(`Proxy error: Status ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            clearTimeout(geocoderTimeout);
            if (didTimeout) return;

            // The rest of the success logic remains the same
            if (data && data.display_name) {
                selectedAddress = data.display_name;
                document.getElementById('address-display').textContent = selectedAddress;
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
                document.getElementById('address-display').textContent = selectedAddress;
            } else {
                selectedAddress = `Lat: ${selectedLat}, Lng: ${selectedLng} (Address not found)`;
                document.getElementById('address-display').textContent = 'Address not found.';
            }

            toggleSelectButton(true);
        })
        .catch(err => {
            clearTimeout(geocoderTimeout);
            console.error('Geocoding error:', err);
            selectedAddress = `Lat: ${selectedLat}, Lng: ${selectedLng} (Address lookup failed)`;
            document.getElementById('address-display').textContent = 'Error fetching address.';
            toggleSelectButton(true);
        });
        // --------------------------------------------------------------------------

    }, 300);
}

function selectLocation() {
    if (selectedLat && selectedLng && selectedAddress) {
        document.getElementById("latitude").value = selectedLat;
        document.getElementById("longitude").value = selectedLng;
        
        let finalAddress = selectedAddress;
        if (finalAddress.includes('(Address lookup failed/timed out)') || finalAddress.includes('(Address not found)')) {
            finalAddress = `Location selected by coordinates: Lat ${selectedLat}, Lng ${selectedLng}`;
        }
        
        document.getElementById("address").value = finalAddress.trim();
        closeMapModal();
    } else {
        alert("Location data is missing. Please ensure the map is loaded and try again.");
    }
}

// --- DOMContentLoaded Logic ---
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone_number');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const addressTextarea = document.getElementById('address');


    const setError = (input, messageId, isValid) => {
        const errorElement = document.getElementById(messageId);
        if (isValid) {
            errorElement.style.display = 'none';
            input.setCustomValidity('');
        } else {
            errorElement.style.display = 'block';
            input.setCustomValidity('Invalid');
        }
    };
    
    // --- Validation Functions (Original Logic) ---
    function validateForm() {
        nameInput.dispatchEvent(new Event('input'));
        emailInput.dispatchEvent(new Event('input'));
        phoneInput.dispatchEvent(new Event('input'));
        validatePasswords();
        
        // Manual check for address/coordinates
        if (!document.getElementById("latitude").value || !addressTextarea.value) {
            alert('Please select your address on the map before creating an account.');
            openMapModal();
            return false;
        }

        // Check if all inputs are valid
        return registerForm.checkValidity();
    }


    nameInput.addEventListener('input', function() {
        let nameValue = nameInput.value.trim();
        
        const isLengthValid = nameValue.length >= 8 && nameValue.length <= 25;
        const containsNumbers = /\d/.test(nameValue);
        const isValid = isLengthValid && !containsNumbers;

        setError(nameInput, 'name-error', isValid);
        
        nameInput.value = nameInput.value.replace(/\d/g, ''); 
        
        const errorElement = document.getElementById('name-error');
        if (!isLengthValid) {
            errorElement.innerText = 'Full Name must be between 8 and 25 characters.';
        } else if (containsNumbers) {
            errorElement.innerText = 'Full Name cannot contain numbers.';
        }
    });
        
    emailInput.addEventListener('input', function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; 
        const isValid = emailRegex.test(emailInput.value);
        setError(emailInput, 'email-error', isValid);
    });

    phoneInput.addEventListener('input', function() {
        let value = phoneInput.value.replace(/\D/g, ''); 

        if (value.length === 0) {
            phoneInput.value = '';
            setError(phoneInput, 'phone-error', false);
            return;
        }
        
        if (value.startsWith('63')) {
            value = value.substring(2);
        }
        
        if (value.startsWith('0')) {
            value = value.substring(1);
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
        
        const isValid = isLengthValid && isMobilePrefixValid;
        
        setError(phoneInput, 'phone-error', isValid);
    });

    function validatePasswords() {
        const passwordValue = passwordInput.value;
        const confirmPasswordValue = confirmPasswordInput.value;

        const minLength = 8;
        const specialCharRegex = /[!@#$%^&*()_+={}\[\]|:;"'<>,.?/\\]/;
        const numberRegex = /\d/;
        
        const isLengthValid = passwordValue.length >= minLength;
        const hasNumber = numberRegex.test(passwordValue);
        const hasSpecialChar = specialCharRegex.test(passwordValue);
        
        const isComplexValid = isLengthValid && hasNumber && hasSpecialChar;
        const passwordsMatch = passwordValue === confirmPasswordValue;

        const passwordErrorElement = document.getElementById('password-error');
        if (!isComplexValid) {
            passwordErrorElement.style.display = 'block';
            passwordInput.setCustomValidity('Invalid');
            
            let message = 'Password must:';
            if (!isLengthValid) {
                message += ` be at least ${minLength} characters,`;
            }
            if (!hasNumber) {
                message += ' include 1 number,';
            }
            if (!hasSpecialChar) {
                message += ' include 1 special character,';
            }
            
            passwordErrorElement.innerText = message.replace(/,$/, '.');

        } else {
            passwordErrorElement.style.display = 'none';
            passwordInput.setCustomValidity('');
        }

        const confirmErrorElement = document.getElementById('confirm-password-error');
        if (confirmPasswordValue && !passwordsMatch) {
            confirmErrorElement.style.display = 'block';
            confirmErrorElement.innerText = 'Passwords do not match.';
            confirmPasswordInput.setCustomValidity('Invalid');
        } else if (isComplexValid) {
            confirmErrorElement.style.display = 'none';
            confirmPasswordInput.setCustomValidity('');
        }
    }

    passwordInput.addEventListener('input', validatePasswords);
    confirmPasswordInput.addEventListener('input', validatePasswords);

    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            
            const type = targetInput.getAttribute('type') === 'password' ? 'text' : 'password';
            targetInput.setAttribute('type', type);
            
            this.classList.toggle('bi-eye-fill');
            this.classList.toggle('bi-eye-slash-fill');
        });
    });
    
    // --- UPDATED: Initial Registration Form Submission (Sends OTP) ---
    registerForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        
        if (!validateForm()) { 
            // The validation function will show alerts/errors
            return; 
        }

        const formData = new FormData(this);
        // Store form data globally before clearing sensitive fields (optional but safe)
        formSubmissionData = formData; 

        registerSubmitBtn.innerHTML = '<i class="bi bi-arrow-clockwise spinner-border-sm"></i> Sending Code...';
        registerSubmitBtn.disabled = true;

        const result = await submitForm('app/api/send_otp.php', formData, registerSubmitBtn);

        if (result.status === 'success') {
            closeModal(); // Close any prior generic modal
            openOtpModal(formData.get('email'));
            
            // Re-enable and reset button text
            registerSubmitBtn.disabled = false;
            registerSubmitBtn.innerHTML = '<i class="bi bi-person-fill-add"></i> Create Account';

        } else {
            // Error: Show error modal
            showModal('error', result.message);
        }
    });

    // --- NEW: OTP Verification Form Submission (Final Registration) ---
   otpForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    
    const otpCode = document.getElementById('otp_code').value;
    if (otpCode.length !== 6 || !/^\d+$/.test(otpCode)) {
        otpError.textContent = 'Please enter a valid 6-digit code.';
        return;
    }
    otpError.textContent = ''; 

    // Reverting to the minimal data submission, as the PHP script gets the rest from $_SESSION.
    const verificationData = new FormData();
    verificationData.append('email', otpHiddenEmail.value);
    verificationData.append('otp_code', otpCode);

    verifyBtn.innerHTML = '<i class="bi bi-arrow-clockwise spinner-border-sm"></i> Verifying...';
    verifyBtn.disabled = true;

    // Submitting only email and OTP.
    const result = await submitForm('app/api/verify_and_register.php', verificationData, verifyBtn);

    if (result.status === 'success') {
        closeOtpModal();
        showModal('success', result.message); 
        
    } else {
        verifyBtn.disabled = false;
        verifyBtn.innerHTML = 'Verify Code';
        otpError.textContent = result.message;
    }
});

    // --- NEW: Resend OTP button listener ---
    resendOtpBtn.addEventListener('click', async function() {
        resendOtpBtn.disabled = true;
        resendOtpBtn.textContent = 'Sending...';
        
        // We resend the original form data to api/send_otp.php 
        // since the server needs all of it to regenerate the session data.
        const result = await submitForm('app/api/send_otp.php', formSubmissionData, resendOtpBtn); 
        
        if (result.status === 'success') {
            clearInterval(otpTimerInterval);
            startOtpTimer(300); // Restart 5-minute timer
            resendOtpBtn.textContent = 'Resend Code (Wait)';
            showModal('success', 'New verification code sent!');
        } else {
            // Re-enable the button if it failed to send
            resendOtpBtn.disabled = false;
            resendOtpBtn.textContent = 'Resend Code Failed';
            showModal('error', result.message);
        }
    });
    
    // --- Original Window Listeners ---
    window.addEventListener('click', (event) => {
        const mapModal = document.getElementById('mapModal');
        if (event.target == mapModal) {
            closeMapModal();
        }
    });

});

document.addEventListener('DOMContentLoaded', function() {
    const termsModal = document.getElementById('termsModal');
    const termsCheckbox = document.getElementById('terms_agree');
    const submitButton = document.getElementById('register-submit-btn');
    const viewTermsLink = document.getElementById('viewTerms');
    const closeTermsBtn = document.getElementById('closeTermsModal');

    const handleCloseTermsModal = () => {
        if (termsModal) {
            termsModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        if (termsCheckbox && submitButton) {
            termsCheckbox.checked = true;
            termsCheckbox.classList.add('checked-gold');
            submitButton.disabled = false;
        }
    };

    if (viewTermsLink && termsModal && closeTermsBtn) {
        viewTermsLink.addEventListener('click', function(event) {
            event.preventDefault();
            termsModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });

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