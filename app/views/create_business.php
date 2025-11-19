<?php
if (!isset($user)) {
    header("Location: index.php?action=login");
    exit;
}

// NOTE: You MUST define these variables in your controller before including this view.
// Example in Controller:
// $application_status = $vendor_account_data['status'] ?? null;
// $submitted_data = $vendor_account_data ?? [];

$is_submitted = ($application_status === 'Pending' || $application_status === 'Approved' || $application_status === 'Rejected');
$is_pending = ($application_status === 'Pending');

$business_name = $submitted_data['business_name'] ?? '';
$business_address = $submitted_data['business_address'] ?? '';
$business_description = $submitted_data['business_description'] ?? '';
$latitude = $submitted_data['latitude'] ?? '';
$longitude = $submitted_data['longitude'] ?? '';

// Check for the success flag, which your controller should set and redirect with.
// Example: header("Location: index.php?action=create_business&success=1");
$show_success_modal = isset($_GET['success']) && $_GET['success'] == 1;

// Using disabled_attr for fields that should be non-typable/unclickable when submitted
$readonly_attr = $is_submitted ? 'readonly' : '';
$disabled_attr = $is_submitted ? 'disabled' : '';
$input_display_style = 'style="display:block;"';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Start Selling - Liyag Batangan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/assets/css/account_management.css">
    <link rel="stylesheet" href="public/assets/css/home.css">
    <link rel="stylesheet" href="public/assets/css/business.css">
    <link rel="stylesheet" href="public/assets/css/notification.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <link rel="icon" type="image/png" href="public/assets/default/icon/logo.png">
</head>
<body>
<header class="header">
    <div class="header-top">
        <div class="profile-greeting">
            START SELLING - LIYAG BATANGAN
        </div>
        <nav class="nav-icons">
            <a href="index.php?action=home" title="Home">
                <i class="bi bi-house-door-fill"></i>
            </a>
            <a href="index.php?action=cart" title="Cart">
                <i class="bi bi-cart-fill"></i>
            </a>
            <div class="notification-dropdown" data-user-id="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>">
                <button id="notificationBtn" title="Notifications">
                    <div class="notification-hover-wrapper">
                        <i class="bi bi-bell-fill"></i>
                        <span id="notificationBadge" class="notification-badge" style="display:none;"></span>
                    </div>
                </button>

                <div class="notification-modal" id="notificationModal">
                    <h4>Notifications</h4>
                    <div class="notification-list" id="notificationList">
                        <p class="no-notifications">Loading notifications...</p>
                    </div>
                </div>
            </div>
            <div class="profile-dropdown">
                <button id="profileBtn" title="Profile">
                    <div class="profile-hover-wrapper">
                        <img src="<?php echo $userProfilePicture ? 'uploads/' . basename($userProfilePicture) : 'public/assets/default/default_profile.jpg'; ?>"
                             alt="Profile" class="profile-icon">
                    </div>
                </button>
                <div class="dropdown-content" id="dropdownMenu">
                    <a href="index.php?action=account">Account Management</a>
                    <a href="index.php?action=create_business">Start Selling</a>
                    <form method="POST" action="index.php?action=logout">
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                </div>
            </div>
        </nav>
    </div>
    <div class="header-banner">
        <h1 class="banner-title">Welcome, Future Liyag Batangan Seller!</h1>
        <p class="banner-subtitle">Complete your business details to start selling your products.</p>
    </div>
</header>
<main>
    <div class="account-container">
        <form method="POST" action="index.php?action=submit_business" enctype="multipart/form-data" class="business-form">
            
            <div class="resume-layout">
                
                <div>
                    <div class="main-content">
                        <h3 class="section-header">Business Details</h3>
                        
                        <div class="profile-info">
                            <div class="profile-row">
                                <label class="profile-label" for="business_name">Business Name</label>
                                <input class="edit-mode" type="text" name="business_name" id="business_name" value="<?php echo htmlspecialchars($business_name); ?>" placeholder="Enter your business name" required <?php echo $disabled_attr; ?> <?php echo $input_display_style; ?>>
                            </div>
                            <div class="profile-row address-field-group">
                                <label class="profile-label" for="business_address">Business Address</label>
                                    <input 
                                        class="edit-mode" 
                                        type="text" 
                                        name="business_address" 
                                        id="business_address" 
                                        value="<?php echo htmlspecialchars($business_address); ?>"
                                        placeholder="<?php echo $is_submitted ? htmlspecialchars($business_address) : 'Select address on map'; ?>" 
                                        required 
                                        readonly 
                                        <?php echo $disabled_attr; // Added disabled to prevent click/focus and typing ?> 
                                        <?php echo $input_display_style; ?>
                                        >
                                <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($latitude); ?>">
                                <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($longitude); ?>">
                            </div>
                            <div class="profile-row">
                                <label class="profile-label" for="business_description">Description</label>
                                <textarea class="edit-mode" name="business_description" id="business_description" placeholder="Business Description" rows="4" required <?php echo $disabled_attr; ?> <?php echo $input_display_style; ?>><?php echo htmlspecialchars($business_description); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="document-section">
                        <h3 class="section-header">Verification Documents</h3>

                        <div class="profile-info">
                            <div class="profile-row">
                                <label class="profile-label" for="logo_url">Business Image</label>
                                <input class="edit-mode" type="file" name="logo_url" id="logo_url" accept="image/*" <?php echo $disabled_attr; ?> <?php echo $input_display_style; ?>>
                            </div>
                            <div class="profile-row">
                                <label class="profile-label" for="verification_document">Verification Document (e.g., DTI, Permit)</label>
                                <input class="edit-mode" type="file" name="verification_document" id="verification_document" accept="application/pdf,image/*" <?php echo $disabled_attr; ?> <?php echo $input_display_style; ?>>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sidebar">
                    <h3 class="section-header" style="border-left: none; padding-left: 0;">Guidelines</h3>
                    <p><strong>1. Business Name:</strong> Should match your legal registration documents for verification purposes.</p>
                    <p><strong>2. Business Address:</strong> Use the map selector to ensure accurate geographical coordinates for delivery zones.</p>
                    <p><strong>3. Image &amp; Documents:</strong> Upload a clear business logo and any required government documents (e.g., DTI, Mayor's Permit) to expedite the review process.</p>
                    <p style="margin-top: 25px;">The Liyag Batangan team will review your application within 2-3 business days.</p>
                </div>
                <div class="profile-actions">
                    <?php if (!$is_submitted): ?>
                        <div class="terms-row">
                            <input type="checkbox" id="terms_agree" required>
                            <label for="terms_agree">
                                I agree to the <a href="#" id="viewTerms" target="_blank">Terms and Conditions</a> for Liyag Batangan Sellers.
                            </label>
                        </div>
                    <?php endif; ?>
                    <button type="submit" id="submitBusinessBtn" class="action-btn save-btn" <?php echo $disabled_attr; ?>>
                        <?php echo $is_pending ? 'Your Application is Being Reviewed' : 'Submit for Review'; ?>
                    </button>
                </div>
            </div>
        </form>
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
                    id="confirmLocationBtn" 
                    class="map-btn select-btn"
                    disabled>
                    Determining Address...
                </button>
                <button id="closeMapModal" class="map-btn cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <div id="termsModal" class="modal">
        <div class="modal-content terms-modal-content">
            <h3 class="modal-title">Vendor Terms and Conditions – Liyag Batangan</h3>
            <div class="terms-content">
                <p>By registering as a Vendor on Liyag Batangan, I agree to the following:</p>
                <ol>
                    <li>I confirm that the business information I provide is true, accurate, and complete.</li>
                    <li>I will only list authentic, safe, and legal products that comply with Philippine laws and regulations.</li>
                    <li>I understand that Liyag Batangan charges a **[Insert % Commission]** per successful sale, which will be deducted from my earnings.</li>
                    <li>I am responsible for preparing, packaging, and delivering customer orders on time.</li>
                    <li>I will not sell counterfeit, illegal, dangerous, or prohibited products.</li>
                    <li>I will not engage in fraud, abusive conduct, or attempt to bypass transactions outside the platform.</li>
                    <li>I acknowledge that Liyag Batangan has the right to suspend, remove, or terminate my account for violations of these Terms.</li>
                    <li>I understand that Liyag Batangan is a marketplace platform and is not liable for my product quality, delivery delays, or disputes with customers.</li>
                    <li>I accept that these Terms may be updated at any time, and continued use of the platform means I agree to the updated Terms.</li>
                </ol>
            </div>
            <button id="closeTermsModal" class="map-btn cancel-btn">Close</button>
        </div>
    </div>

    <div id="successModal" class="modal" style="display: none;">
        <div class="modal-content success-modal-content">
            <lottie-player
                id="lottieSuccess"
                src="public/assets/lotties/check.json"
                background="transparent"
                speed="1"
                style="width: 250px; height: 250px; margin: 0 auto;"
                loop
                autoplay
            ></lottie-player>
            <h3 class="modal-title">Application Submitted!</h3>
            <p style="text-align: center; margin-bottom: 20px;">
                Thank you for submitting your business details. Your application will be processed by the Liyag Batangan team within **2-3 business days**.
            </p>
            <p style="text-align: center;">You will receive a notification once your application is approved or rejected.</p>
            <button id="closeSuccessModal" class="map-btn select-btn">Got It!</button>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="footer-nav">
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Contact</a>
    </div>
    <div class="social-icons">
        <a href="https://www.facebook.com/people/Liyag-Batangan/61583172300285/"><i class="bi bi-facebook"></i></a>
        <a href="https://www.instagram.com/liyag.batangan/"><i class="bi bi-instagram"></i></a>
    </div>
    <p class="copyright">&copy; <?= date('Y') ?> Liyag Batangan. All rights reserved.</p>
</footer>

<script src="app/scripts/home.js"></script>
<script src="app/scripts/business.js"></script>
<script src="app/scripts/notification.js"></script>

<script>
    // JavaScript to handle the success modal display
    document.addEventListener('DOMContentLoaded', function() {
        const successModal = document.getElementById('successModal');
        const closeSuccessModal = document.getElementById('closeSuccessModal');
        const lottiePlayer = document.getElementById('lottieSuccess');
        
        // This PHP variable is set based on the URL parameter check above.
        const showModal = <?php echo $show_success_modal ? 'true' : 'false'; ?>;

        if (showModal) {
            successModal.style.display = 'block';
            // Start or play the Lottie animation
            if (lottiePlayer) {
                lottiePlayer.play();
            }
        }

        closeSuccessModal.onclick = function() {
            successModal.style.display = 'none';
            // Optional: Stop or pause the Lottie animation
            if (lottiePlayer) {
                lottiePlayer.pause();
            }
            
            // Optional: Redirect to clean up the URL parameter
            // window.location.href = 'index.php?action=create_business';
        }

        // Close modal if user clicks outside of it
        window.onclick = function(event) {
            if (event.target == successModal) {
                successModal.style.display = 'none';
                if (lottiePlayer) {
                    lottiePlayer.pause();
                }
            }
        }
    });
</script>

</body>
</html>