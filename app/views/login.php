<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liyag Batangan Login</title>
    <link rel="stylesheet" href="public/assets/css/login_style.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="public/assets/default/icon/account.png">
</head>
<body>
    <div class="login-page">
        <div class="branding-container">
            <div class="animated-bg"></div> 
            
            <h1 class="logo-title">
                <span class="liyag">LIYAG</span>
                <span class="batangan">BATANGAN</span>
            </h1>
            <p class="tagline">Sign in to your account to continue shopping</p>
        </div>

        <div class="form-container">
            <form method="POST" class="login-form">
                <h2>Account Sign In</h2>

                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class="bi bi-person-fill"></i>
                </div>
                
                <div class="input-group password-container">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <i class="bi bi-lock-fill"></i>
                    <i class="bi bi-eye-slash-fill toggle-password" id="togglePassword"></i>
                </div>

                <a href="#" data-modal-target="#forgotPasswordModal" class="forgot-password-link">Forgot Password?</a>

                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                <button type="submit" class="submit-btn">
                    Sign In <i class="bi bi-arrow-right-short"></i>
                </button>
                
                <?php if (!empty($error)) echo "<p class='error-message'>$error</p>"; ?>
                
                <p class="register-link-text">
                    No account? <a href="index.php?action=register">Register here</a>
                </p>
            </form>
        </div>
    </div>
    <div id="forgotPasswordModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div class="modal-header">
                <i class="bi bi-shield-lock-fill" style="color: var(--color-gold);"></i>
                <h3 id="modal-title">Reset Your Password</h3>
            </div>
            
            <div class="modal-body">
                <form id="step1-email-form" class="modal-form" style="display: block;">
                    <p>Enter the email address associated with your account. We'll send you a verification code.</p>
                    <div class="input-group">
                        <input type="email" id="reset-email" name="email" placeholder="Your Email Address" required>
                        <i class="bi bi-envelope-fill"></i>
                    </div>
                    <button type="submit" class="submit-btn">Send Verification Code</button>
                    <p class="modal-message-area"></p>
                </form>

                <form id="step2-otp-form" class="modal-form" style="display: none;">
                    <p id="otp-instruction">A 6-digit code has been sent to your email. Enter it below along with your new password.</p>
                    
                    <div class="input-group">
                        <input type="text" id="reset-otp" name="otp_code" placeholder="6-digit Code" required maxlength="6">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                    
                    <div class="input-group password-container">
                        <input type="password" id="new-password" name="new_password" placeholder="New Password" required>
                        <i class="bi bi-lock-fill"></i>
                        <i class="bi bi-eye-slash-fill toggle-password" data-target="new-password"></i>
                    </div>

                    <div class="input-group password-container">
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm New Password" required>
                        <i class="bi bi-lock-fill"></i>
                        <i class="bi bi-eye-slash-fill toggle-password" data-target="confirm-password"></i>
                    </div>

                    <button type="submit" class="submit-btn">Reset Password</button>
                    <p class="modal-message-area"></p>
                </form>

                <div id="step3-success" style="display: none; text-align: center;">
                    <p class="success-icon">🎉</p>
                    <p style="font-size: 1.1rem; color: #4CAF50; font-weight: 600;">Password has been successfully reset!</p>
                    <p style="color: #555; margin-top: 15px;">You can now use your new password to sign in.</p>
                    <button type="button" class="submit-btn" id="modal-close-button" style="margin-top: 20px;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="app/scripts/login.js"></script>

</body>
</html>