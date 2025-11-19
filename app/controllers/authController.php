<?php
// Note: You may need to update this to include the necessary dependencies (like database connection)
// If UserModel handles the database connection, you must ensure it can pass it to the VendorAccountModel.
require_once __DIR__ . '/../models/userModels.php';
// You MUST require the VendorAccountModel or ensure it is autoloaded
require_once __DIR__ . '/../models/vendorAccModels.php'; 

class AuthController {
    private $userModel;
    private $vendorModel;

    // Line 12: The constructor expects PDO $db
    public function __construct(PDO $db) { 
        $this->userModel = new UserModel($db); 
        $this->vendorModel = new VendorAccountModel($db); 
    }

    public function login() {
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;
            
            // Assume $this->userModel->login returns the user data array on success (e.g., ['user_id' => 32, 'user_type' => 'Vendor', ...])
            $user = $this->userModel->login($email, $password); 

            if ($user) {
                
                // --- CRITICAL FIX START ---
                
                // 1. Check if the user is a Vendor
                if ($user['user_type'] === 'Vendor') {
                    
                    // 2. Look up the specific vendor_id from the database
                    $vendorDetails = $this->vendorModel->getStoreDetailsByUserId($user['user_id']);

                    if ($vendorDetails) {
                        // 3. Add vendor-specific keys to the user session array
                        $user['vendor_id'] = $vendorDetails['vendor_id'];
                        $user['vendor_status'] = $vendorDetails['status'];
                        
                        error_log("Vendor Login: Vendor ID " . $user['vendor_id'] . " added to session.");
                        
                        // Set the session and redirect
                        $_SESSION['user'] = $user;
                        $_SESSION['user_id'] = $user['user_id']; // Keep this for existing code that relies on it
                        header('Location: index.php?action=home'); // Redirect to vendor page
                        exit;
                    } else {
                        // Handle case where user is marked 'Vendor' but has no account entry
                        error_log("SECURITY ALERT: User " . $user['user_id'] . " is Vendor type but missing vendor_account entry.");
                        $error = "Vendor account is missing. Please contact support.";
                        // Continue to standard session/redirect, but the vendor features will fail, or log out the user.
                    }
                }
                
                // --- CRITICAL FIX END ---
                
                // STANDARD USER LOGIN (or vendor login if no vendorDetails found)
                $_SESSION['user'] = $user;
                $_SESSION['user_id'] = $user['user_id']; // Set the root user_id for backward compatibility
                
                if ($latitude && $longitude) {
                    $_SESSION['user_location'] = [
                        'latitude' => $latitude,
                        'longitude' => $longitude
                    ];
                }

                header('Location: index.php?action=home');
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        }

        include __DIR__ . '/../views/login.php';
    }

    // ... (register and logout methods remain the same)
    public function register() {
        $message = '';
        $result = null;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $phone_number = trim($_POST['phone_number'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;

            if (empty($name) || empty($email) || empty($password) || empty($phone_number) || empty($address)) {
                $message = "Please fill in all required fields.";
                $result = false;
            } elseif ($password !== $confirm_password) {
                $message = "Passwords do not match.";
                $result = false;
            } else {
                if ($this->userModel->isEmailRegistered($email)) {
                    $message = "This email is already registered.";
                    $result = false;
                } else {
                    $registration_data = [
                        'name' => $name,
                        'email' => $email,
                        'password' => $password,
                        'phone_number' => $phone_number,
                        'address' => $address,
                        'latitude' => $latitude,
                        'longitude' => $longitude
                    ];

                    $_SESSION['registration_data'][$email] = $registration_data;

                    $result = 'otp_required';
                    $message = 'Verification required. Please check your email for the OTP.';
                }
            }
        }

        include __DIR__ . '/../views/register.php';
    }



    public function logout() {
        session_destroy();
        header("Location: index.php?action=login");
        exit;
    }

    
}