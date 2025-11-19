document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.querySelector('.login-form');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const openMapModalButton = document.getElementById('openMapModal');
    const locationModal = document.getElementById('locationModal');
    const closeMapModalButton = document.getElementById('closeMapModal');
    const confirmLocationButton = document.getElementById('confirmLocation');
    const mapContainer = document.getElementById('mapContainer');
    const modalOverlay = document.getElementById('forgotPasswordModal');
    const openModalLink = document.querySelector('[data-modal-target="#forgotPasswordModal"]');
    const closeModalBtn = modalOverlay ? modalOverlay.querySelector('.close-btn') : null;
    const closeSuccessBtn = document.getElementById('modal-close-button');
    const step1Form = document.getElementById('step1-email-form');
    const step2Form = document.getElementById('step2-otp-form');
    const step3Success = document.getElementById('step3-success');
    const messageArea1 = step1Form ? step1Form.querySelector('.modal-message-area') : null;
    const messageArea2 = step2Form ? step2Form.querySelector('.modal-message-area') : null;
    let map = null;
    let marker = null;
    let selectedLat = null;
    let selectedLng = null;
    let resetEmail = '';

    // New element selectors for step 2 form
    const resetPasswordInput = document.getElementById('new-password');
    const resetConfirmPasswordInput = document.getElementById('confirm-password');

    const handleTogglePassword = (e) => {
        const targetId = e.target.getAttribute('data-target') || 'password';
        const input = document.getElementById(targetId);
        if (!input) return; 
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        e.target.classList.toggle('bi-eye-fill');
        e.target.classList.toggle('bi-eye-slash-fill');
    };
    if (togglePassword) {
        togglePassword.addEventListener('click', handleTogglePassword);
    }
    document.querySelectorAll('.password-container i.toggle-password').forEach(icon => {
        icon.addEventListener('click', handleTogglePassword);
    });

    // New password validation function for step 2
    function validatePasswordResetFields() {
        const passwordValue = resetPasswordInput.value;
        const confirmPasswordValue = resetConfirmPasswordInput.value;

        const minLength = 8;
        const specialCharRegex = /[!@#$%^&*()_+={}\[\]|:;"'<>,.?/\\]/;
        const numberRegex = /\d/;
        
        const isLengthValid = passwordValue.length >= minLength;
        const hasNumber = numberRegex.test(passwordValue);
        const hasSpecialChar = specialCharRegex.test(passwordValue);
        
        const isComplexValid = isLengthValid && hasNumber && hasSpecialChar;
        const passwordsMatch = passwordValue === confirmPasswordValue;

        if (!passwordsMatch) {
            return { isValid: false, message: 'New passwords do not match.' };
        }

        if (!isComplexValid) {
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
            
            return { isValid: false, message: message.replace(/,$/, '.') };
        }

        return { isValid: true, message: '' };
    }


    function initializeMap(initialLat = 14.00, initialLng = 121.00) { 
        if (map) {
            map.remove();
        }
        map = L.map('mapContainer').setView([initialLat, initialLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        marker = L.marker([initialLat, initialLng]).addTo(map);
        selectedLat = initialLat;
        selectedLng = initialLng;
        L.Control.geocoder({
            defaultMarkGeocode: false
        })
        .on('markgeocode', function(e) {
            const bbox = e.geocode.bbox;
            const poly = L.polygon([
                bbox.getSouthEast(),
                bbox.getNorthEast(),
                bbox.getNorthWest(),
                bbox.getNorthWest()
            ]).addTo(map);
            map.fitBounds(poly.getBounds());
            marker.setLatLng(e.geocode.center);
            selectedLat = e.geocode.center.lat;
            selectedLng = e.geocode.center.lng;
            map.removeLayer(poly);
        })
        .addTo(map);
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            selectedLat = e.latlng.lat;
            selectedLng = e.latlng.lng;
        });
    }

    if (openMapModalButton) {
        openMapModalButton.addEventListener('click', () => {
            locationModal.style.display = 'block';
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        initializeMap(position.coords.latitude, position.coords.longitude);
                        map.invalidateSize();
                    },
                    (error) => {
                        console.warn("Geolocation denied, initializing map with default location.");
                        initializeMap();
                        map.invalidateSize();
                    }
                );
            } else {
                initializeMap();
                map.invalidateSize();
            }
        });
    }

    if (closeMapModalButton) {
        closeMapModalButton.addEventListener('click', () => {
            locationModal.style.display = 'none';
        });
    }

    if (confirmLocationButton) {
        confirmLocationButton.addEventListener('click', () => {
            if (selectedLat !== null && selectedLng !== null) {
                latitudeInput.value = selectedLat;
                longitudeInput.value = selectedLng;
                locationModal.style.display = 'none';
                openMapModalButton.innerHTML = `Location Set! <i class="bi bi-check-circle-fill"></i>`;
                openMapModalButton.style.backgroundColor = '#4CAF50';
                openMapModalButton.style.color = 'white';
            }
        });
    }

    if (locationModal) {
        window.addEventListener('click', (event) => {
            if (event.target == locationModal) {
                locationModal.style.display = 'none';
            }
        });
    }

    if ("geolocation" in navigator && latitudeInput && longitudeInput) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                latitudeInput.value = position.coords.latitude;
                longitudeInput.value = position.coords.longitude;
                if (openMapModalButton) {
                    openMapModalButton.innerHTML = `Location Auto-Set <i class="bi bi-check-circle-fill"></i>`;
                    openMapModalButton.style.backgroundColor = '#4CAF50';
                    openMapModalButton.style.color = 'white';
                }
            },
            (error) => {
                console.warn("Geolocation was not granted/failed during initial load.");
            }
        );
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (e.target.classList.contains('login-form')) {
                e.preventDefault();
                if ("geolocation" in navigator) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            latitudeInput.value = position.coords.latitude;
                            longitudeInput.value = position.coords.longitude;
                            loginForm.submit();
                        },
                        (error) => {
                            console.error("Geolocation Error:", error.message);
                            loginForm.submit();
                        },
                        { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                    );
                } else {
                    console.warn("Geolocation is not supported by this browser.");
                    loginForm.submit();
                }
            }
        });
    }

    if (modalOverlay) {
        const openModal = () => {
            modalOverlay.classList.add('active');
            if (step1Form) step1Form.style.display = 'block';
            if (step2Form) step2Form.style.display = 'none';
            if (step3Success) step3Success.style.display = 'none';
            if (messageArea1) messageArea1.textContent = '';
            if (messageArea2) messageArea2.textContent = '';
        };

        const closeModal = () => {
            modalOverlay.classList.remove('active');
            if (step1Form) step1Form.reset();
            if (step2Form) step2Form.reset();
        };

        if (openModalLink) {
            openModalLink.addEventListener('click', (e) => {
                e.preventDefault();
                openModal();
            });
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeModal);
        }

        if (closeSuccessBtn) {
            closeSuccessBtn.addEventListener('click', closeModal);
        }

        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });

        if (step1Form && messageArea1) {
            step1Form.addEventListener('submit', async (e) => {
                e.preventDefault();
                messageArea1.textContent = 'Sending code...';
                messageArea1.style.color = '#007bff';
                const emailInput = document.getElementById('reset-email');
                resetEmail = emailInput.value.trim();

                if (!resetEmail) {
                    messageArea1.textContent = 'Please enter a valid email.';
                    messageArea1.style.color = 'red';
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('email', resetEmail);
                    const response = await fetch('index.php?action=send_password_otp', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        messageArea1.textContent = 'Verification code sent! Proceed to next step.';
                        messageArea1.style.color = '#4CAF50';
                        if (step1Form) step1Form.style.display = 'none';
                        if (step2Form) step2Form.style.display = 'block';
                        const otpInstruction = document.getElementById('otp-instruction');
                        if (otpInstruction) otpInstruction.textContent = `A 6-digit code has been sent to ${resetEmail}. Enter it below along with your new password.`;
                    } else {
                        messageArea1.textContent = result.message || 'Error sending code.';
                        messageArea1.style.color = 'red';
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    messageArea1.textContent = 'A network error occurred. Please try again.';
                    messageArea1.style.color = 'red';
                }
            });
        }

        if (step2Form && messageArea2) {
            step2Form.addEventListener('submit', async (e) => {
                e.preventDefault();
                messageArea2.textContent = 'Verifying and resetting...';
                messageArea2.style.color = '#007bff';

                const validation = validatePasswordResetFields();

                if (!validation.isValid) {
                    messageArea2.textContent = validation.message;
                    messageArea2.style.color = 'red';
                    return;
                }

                const otp = document.getElementById('reset-otp').value.trim();
                const newPassword = resetPasswordInput.value;

                try {
                    const formData = new FormData();
                    formData.append('email', resetEmail);
                    formData.append('otp_code', otp);
                    formData.append('new_password', newPassword);
                    
                    const response = await fetch('index.php?action=verify_password_otp', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        messageArea2.textContent = '';
                        if (step2Form) step2Form.style.display = 'none';
                        if (step3Success) step3Success.style.display = 'block';
                    } else {
                        messageArea2.textContent = result.message || 'Error resetting password. Invalid OTP or password.';
                        messageArea2.style.color = 'red';
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    messageArea2.textContent = 'A network error occurred. Please try again.';
                    messageArea2.style.color = 'red';
                }
            });
        }
    }
});