<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liyag Batangan Register</title>
    <link rel="stylesheet" href="public/assets/css/register_style.css">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <link rel="icon" type="image/png" href="public/assets/default/icon/account.png">

</head>
<body>
    <div class="register-page">
        <div class="branding-container">
            <div class="animated-bg"></div>
            
            <h1 class="logo-title">
                <span class="liyag">LIYAG</span>
                <span class="batangan">BATANGAN</span>
            </h1>
            <p class="tagline">Sign up now to start shopping with us!</p>
        </div>

        <div class="form-container">
            <form id="register-form" class="register-form">
                <h2>Create Account</h2>
                
                <div class="input-group">
                    <input type="text" name="name" id="name" placeholder="Full Name" 
                        minlength="8" maxlength="25" required 
                        value="<?= htmlspecialchars($validatedName ?? '', ENT_QUOTES) ?>">
                    <i class="bi bi-person-circle"></i>
                    <p id="name-error" class="error-message">Full Name must be between 8 and 25 characters.</p>
                </div>

                <div class="input-group">
                    <input type="email" name="email" id="email" placeholder="Email Address" required
                        pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$"
                        value="<?= htmlspecialchars($validatedEmail ?? '', ENT_QUOTES) ?>">
                    <i class="bi bi-envelope-fill"></i>
                    <p id="email-error" class="error-message">Please enter a valid email address (e.g., example@domain.com).</p>
                </div>

                <div class="input-group">
                    <input type="tel" name="phone_number" id="phone_number" placeholder="Phone Number (e.g., 9682311233)" required
                        value="<?= htmlspecialchars($_POST['phone_number'] ?? '', ENT_QUOTES) ?>">
                    <i class="bi bi-phone-fill"></i>
                    <p id="phone-error" class="error-message">Phone number must be 11 digits (excluding +63) and follow the format +63 9xx xxx xxxx.</p>
                </div>
                
                <div class="input-group address-group">
                    <textarea name="address" id="address" placeholder="Select Address on Map" required readonly onclick="openMapModal()"><?= htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES) ?></textarea>
                    <i class="bi bi-geo-alt-fill map-icon" onclick="openMapModal()"></i>
                    <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($_POST['latitude'] ?? '', ENT_QUOTES) ?>">
                    <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($_POST['longitude'] ?? '', ENT_QUOTES) ?>">
                </div>

                <div class="input-group password-container">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <i class="bi bi-lock-fill"></i>
                    <i class="bi bi-eye-slash-fill toggle-password" data-target="password"></i>
                    <p id="password-error" class="error-message">Password does not meet minimum security requirements.</p>
                </div>

                <div class="input-group password-container">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                    <i class="bi bi-lock-fill"></i>
                    <i class="bi bi-eye-slash-fill toggle-password" data-target="confirm_password"></i>
                    <p id="confirm-password-error" class="error-message">Passwords do not match.</p>
                </div>
                <div class="terms-row">
                    <input type="checkbox" id="terms_agree" required>
                    <label for="terms_agree">
                        I agree to the <a href="#" id="viewTerms" target="_blank">Terms and Conditions</a>
                    </label>
                </div>

                <button type="submit" class="submit-btn" id="register-submit-btn"><i class="bi bi-person-fill-add"></i> Create Account</button>

                <p class="register-link-text">Already have an account? <a href="index.php?action=login">Login here</a></p>
            </form>
        </div>
    </div>

    <div id="otp-modal" class="modal">
        <div class="modal-content otp-modal-content">
            <h3 class="modal-title">Verify Your Email</h3>
            <p class="otp-message">A 6-digit verification code has been sent to <strong id="otp-email-display"></strong>. The code expires in <span id="otp-timer">5:00</span>.</p>
            
            <form id="otp-form" class="otp-form">
                <input type="hidden" name="email" id="otp-hidden-email">
                <div class="input-group otp-group">
                    <input type="text" name="otp_code" id="otp_code" placeholder="Enter 6-digit Code" required minlength="6" maxlength="6" pattern="\d{6}">
                    <i class="bi bi-shield-lock-fill"></i>
                    <p id="otp-error" class="error-message"></p>
                </div>
                
                <button type="submit" class="submit-btn" id="verify-btn"><i class="bi bi-check2-circle"></i> Verify Code</button>
                <button type="button" class="map-btn cancel-btn" id="resend-otp-btn" disabled>Resend Code (Wait)</button>
            </form>
            <p class="cancel-link-text"><a href="#" onclick="closeOtpModal()">Cancel</a></p>
        </div>
    </div>
    <div id="lottie-modal" class="modal">
        <div class="modal-content">
            <lottie-player
                id="lottie-animation"
                src="public/assets/lotties/check.json"
                background="transparent"
                speed="1"
                style="width: 200px; height: 200px;"
                autoplay>
            </lottie-player>
            <p id="modal-message"></p>
            <button onclick="closeModal()">OK</button>
        </div>
    </div>

    <div id="mapModal" class="modal">
        <div class="modal-content map-modal-content">
            <h3 class="modal-title">Select Your Location</h3>
            
            <p id="address-display" class="current-address-display">Fetching address...</p>

            <div id="map"></div>
            <div id="center-marker"></div> 
            
            <div class="map-modal-buttons">
                <button 
                    onclick="selectLocation()" 
                    id="select-location-btn" 
                    class="map-btn select-btn"
                    disabled>
                    Determining Address...
                </button>
                <button onclick="closeMapModal()" class="map-btn cancel-btn">Cancel</button>
            </div>
        </div>
    </div>
    <div id="termsModal" class="modal">
    <div class="modal-content terms-modal-content">
        <h3 class="modal-title">Liyag Batangan – Terms and Conditions for Customers</h3>
        <div class="terms-content">
            <p>Welcome to Liyag Batangan, a web-based platform designed to showcase and promote Batangas’ finest local products and pasalubong. By logging in and using your account, you agree to comply with the following Terms and Conditions. Please read them carefully before continuing to use the platform.</p>

            <hr>

            <h4>1. Account and User Responsibilities</h4>
            <ol>
                <li>You are responsible for maintaining the confidentiality of your account credentials and ensuring that your login details are secure.</li>
                <li>You agree to provide accurate, updated, and truthful information when creating or using your account.</li>
                <li>Your account is personal and must not be shared or used by others. Any misuse may result in suspension or permanent deactivation.</li>
            </ol>

            <hr>

            <h4>2. Purpose and Proper Use of the Platform</h4>
            <ol>
                <li>Liyag Batangan serves as a digital marketplace connecting customers with local vendors across Batangas Province. The platform’s primary goal is to support local entrepreneurs and promote Batangueño culture through e-commerce.</li>
                <li>As a user, you agree to use the platform responsibly and only for lawful purposes.</li>
                <li>You must not use the platform for any fraudulent, harmful, or misleading activities. Any detected misuse will be subject to review and action by the system administrators.</li>
            </ol>

            <hr>

            <h4>3. Product Information and Transactions</h4>
            <ol>
                <li>All product details, descriptions, and prices are provided by verified vendors registered on the Liyag Batangan platform.</li>
                <li>The platform ensures that listings are authentic and aligned with its mission of promoting Batangas’ local craftsmanship and specialties.</li>
                <li>Liyag Batangan acts as a digital bridge between buyers and local sellers, and all transactions are facilitated through secure and verified channels.</li>
            </ol>

            <hr>

            <h4>4. Privacy and Data Protection</h4>
            <ol>
                <li>Your privacy and trust are important to us. All personal data collected are handled in accordance with our Privacy Policy.</li>
                <li>Information you provide will only be used for legitimate purposes such as processing orders, account management, and improving your experience.</li>
                <li>Liyag Batangan guarantees that no personal data will be shared with third parties without your consent, except when required by law.</li>
            </ol>

            <hr>

            <h4>5. Intellectual Property and Content Ownership</h4>
            <ol>
                <li>All content, including the Liyag Batangan logo, platform design, and system interface, is protected by intellectual property laws.</li>
                <li>You may not copy, modify, or distribute any part of the platform without written permission from the Liyag Batangan development team.</li>
                <li>Vendors retain ownership of their product images and descriptions but grant Liyag Batangan the right to display them on the platform for promotional purposes.</li>
            </ol>

            <hr>

            <h4>6. Platform Reliability and System Maintenance</h4>
            <ol>
                <li>Liyag Batangan is committed to providing a reliable, efficient, and user-friendly platform.</li>
                <li>System updates, maintenance, or improvements may occur periodically to enhance performance and user experience.</li>
                <li>In the event of temporary downtime or technical issues, users will be notified through official communication channels.</li>
            </ol>

            <hr>

            <h4>7. Limitation of Liability</h4>
            <ol>
                <li>While Liyag Batangan strives to ensure smooth operations, it does not guarantee uninterrupted access or error-free service at all times.</li>
                <li>The platform will not be held liable for any indirect or incidental damages resulting from user errors, technical interruptions, or unauthorized access.</li>
                <li>Users are encouraged to report any issues promptly through the platform’s help and support channels.</li>
            </ol>

            <hr>

            <h4>8. Account Suspension and Termination</h4>
            <ol>
                <li>The administration reserves the right to suspend or terminate user accounts that violate these Terms and Conditions.</li>
                <li>Users may request account deactivation at any time by contacting customer support.</li>
                <li>Suspended accounts due to violations may be reinstated only after thorough review and approval by the system administrators.</li>
            </ol>

            <hr>

            <h4>9. Updates to the Terms</h4>
            <ol>
                <li>Liyag Batangan may update or revise these Terms and Conditions to improve system governance and user safety.</li>
                <li>Users will be notified in advance of significant changes. Continued use of the platform implies acceptance of the updated terms.</li>
            </ol>

            <hr>

            <h4>10. Agreement</h4>
            <p>By clicking “I Agree” or logging into your account, you acknowledge that you have read, understood, and accepted these Terms and Conditions, and that you support the platform’s mission of empowering Batangueño entrepreneurs and promoting local products through digital innovation.</p>
        </div>
        <button id="closeTermsModal" class="map-btn cancel-btn">Close</button>
    </div>
</div>

    <script src="app/scripts/register.js"></script>
    
    <?php if (!empty($message)): ?>
        <script>
            showModal("<?= $result === true ? 'success' : 'error' ?>", <?= json_encode($message) ?>);
        </script>
    <?php endif; ?>
</body>
</html>