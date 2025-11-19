<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
class AuthController {
    
    // Hardcoded Admin Credentials (SHA-256 hash of 'liyabbatangan01')
    private const ADMIN_USERNAME = 'admin'; 
    private const ADMIN_PASSWORD_HASH = 'ce83ee26d525312ecb5def15c9804247de47a8539304cc48c6b5ffb1003a0dfd'; 
    
    public function __construct() {
        // No Model dependency needed
    }

    /**
     * Handles the display and processing of the static Admin login form.
     */
    public function adminLogin() {
        $error = '';
        $username_input = '';

        // Redirect if already logged in
        if (isset($_SESSION['is_admin_logged_in']) && $_SESSION['is_admin_logged_in'] === true) {
            header("Location: index.php?action=admin_dashboard");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $input_field = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $username_input = strtolower(trim($input_field)); 
            
            // Hash the submitted password for comparison
            $submitted_hash = hash('sha256', $password);
            
            // Static credential check
            if ($username_input === self::ADMIN_USERNAME && $submitted_hash === self::ADMIN_PASSWORD_HASH) {
                // SUCCESS: Set session variables
                $_SESSION['user_id'] = 1; 
                $_SESSION['is_admin_logged_in'] = true; 
                $_SESSION['name'] = 'System Admin';
                
                header("Location: index.php?action=admin_dashboard");
                exit;
            } else {
                $error = "Invalid administrator username or password."; 
            }
        }

        // Load the Admin login view
        require_once __DIR__ . '/../views/admin_login.php';
    }

    /**
     * Handles the logout process.
     */
    public function logout() {
        // 1. Clear session data
        session_unset();
        session_destroy();
        
        // 2. Add security headers to prevent caching of previous pages 
        //    (Crucial for preventing the "back" button from showing a logged-in page)
        header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
        header("Pragma: no-cache"); // HTTP 1.0
        header("Expires: 0"); // Proxies
        
        // 3. Redirect the user to the login page
        header("Location: index.php?action=admin_login");
        
        // 4. Optionally, add JavaScript to manipulate the browser history 
        //    This clears the history entry for the logout page, making the back button skip it.
        //    NOTE: This must be done carefully, as the headers are already sent.
        //    A simple redirect is usually sufficient due to the anti-caching headers.
        
        exit;
    }
}