<?php

require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php';
require_once __DIR__ . '/../models/businessModels.php';

class VendorController {
    private $db;
    private $vendor_account;
    
    // Base upload directory, relative to where your main index.php is executed
    private const BASE_UPLOAD_DIR = __DIR__ . '/../../'; 
    private const LOGO_DIR = 'uploads/business_logo/';
    private const DOCUMENT_DIR = 'uploads/business_documents/';

    // Define allowed types and maximum size (e.g., 5MB)
    private const ALLOWED_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif'];
    private const ALLOWED_DOC_EXT = ['pdf', 'jpg', 'jpeg', 'png'];
    private const MAX_FILE_SIZE = 5242880; // 5MB

    public function __construct($db) {
        $this->db = $db;
        $this->vendor_account = new VendorAccount($this->db);

        // Set up the specific upload directories if they don't exist
        if (!is_dir(self::BASE_UPLOAD_DIR . self::LOGO_DIR)) {
            mkdir(self::BASE_UPLOAD_DIR . self::LOGO_DIR, 0777, true);
        }
        if (!is_dir(self::BASE_UPLOAD_DIR . self::DOCUMENT_DIR)) {
            mkdir(self::BASE_UPLOAD_DIR . self::DOCUMENT_DIR, 0777, true);
        }
    }

    public function submitBusiness() {
        // We are removing header('Content-Type: application/json'); 
        // because we will use a redirect for success/error handling.

        // Check 1: HTTP Method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=create_business");
            exit;
        }

        // 1. Session and Input Validation
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        
        // Basic check for required POST data
        if (empty($_POST['business_name']) || empty($_POST['business_address'])) {
            $_SESSION['error_message'] = "Business Name and Address are required.";
            header("Location: index.php?action=create_business");
            exit;
        }

        // Check if application already exists (to prevent re-submission)
        if ($this->vendor_account->readByUserId($user_id)) {
            $_SESSION['error_message'] = "You have already submitted an application and it is currently being reviewed or finalized.";
            header("Location: index.php?action=create_business");
            exit;
        }
        
        // Initialize variables to track uploaded files for cleanup if DB fails
        $logo_filename = false;
        $document_filename = false;

        // 2. Handle File Uploads (Uses internal error handling and sets $_SESSION['file_error'])
        $logo_filename = $this->handleFileUpload('logo_url', $user_id . '_logo', self::LOGO_DIR, self::ALLOWED_IMAGE_EXT);
        $document_filename = $this->handleFileUpload('verification_document', $user_id . '_doc', self::DOCUMENT_DIR, self::ALLOWED_DOC_EXT);

        // Check 2: File Upload Status
        if (!$logo_filename || !$document_filename) {
            // Cleanup in case one file uploaded successfully but the other failed
            if ($logo_filename) {
                @unlink(self::BASE_UPLOAD_DIR . self::LOGO_DIR . $logo_filename);
            }
            if ($document_filename) {
                @unlink(self::BASE_UPLOAD_DIR . self::DOCUMENT_DIR . $document_filename);
            }

            // Error message is set inside handleFileUpload for specific details
            $_SESSION['error_message'] = $_SESSION['file_error'] ?? "File upload failed. Please check both files.";
            unset($_SESSION['file_error']); // Clean up temporary file error
            
            header("Location: index.php?action=create_business");
            exit;
        }

        // 3. Set VendorAccount properties
        $this->vendor_account->user_id = $user_id;
        $this->vendor_account->business_name = htmlspecialchars($_POST['business_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $this->vendor_account->business_address = htmlspecialchars($_POST['business_address'] ?? '', ENT_QUOTES, 'UTF-8');
        $this->vendor_account->business_description = htmlspecialchars($_POST['business_description'] ?? '', ENT_QUOTES, 'UTF-8');
        
        $this->vendor_account->latitude = is_numeric($_POST['latitude'] ?? 0.0) ? (float)$_POST['latitude'] : 0.0;
        $this->vendor_account->longitude = is_numeric($_POST['longitude'] ?? 0.0) ? (float)$_POST['longitude'] : 0.0;
        
        // The logo_url and verification_document paths are stored relative to the BASE_UPLOAD_DIR
        $this->vendor_account->logo_url = self::LOGO_DIR . $logo_filename;
        $this->vendor_account->verification_document = self::DOCUMENT_DIR . $document_filename;

        // 4. Create the vendor account in the database
        if ($this->vendor_account->create()) {
            // SUCCESS: Redirect with the success flag to trigger the modal
            header("Location: index.php?action=create_business&success=1");
            exit;
        } else {
            // Database insertion failed, delete uploaded files
            $_SESSION['error_message'] = "Error submitting business details to the database. Please try again.";
            
            // Delete uploaded files using the filename variables
            if ($logo_filename) {
                @unlink(self::BASE_UPLOAD_DIR . self::LOGO_DIR . $logo_filename);
            }
            if ($document_filename) {
                @unlink(self::BASE_UPLOAD_DIR . self::DOCUMENT_DIR . $document_filename);
            }
            
            header("Location: index.php?action=create_business");
            exit;
        }
    }

    /**
     * Handles the file upload process for a single file.
     * Uses session only to temporarily store specific file error, which is cleared in submitBusiness.
     */
    private function handleFileUpload($file_key, $prefix, $target_subdir, array $allowed_extensions) {
        if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
            // Set temporary error in session to be picked up by submitBusiness()
            $_SESSION['file_error'] = "File upload error for " . $file_key . ". Code: " . ($_FILES[$file_key]['error'] ?? 'N/A');
            return false;
        }

        $file = $_FILES[$file_key];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check 1: File Extension Validation
        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['file_error'] = "Invalid file type for " . $file_key . ". Allowed types: " . implode(', ', $allowed_extensions);
            return false;
        }
        
        // Check 2: File Size Validation
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $_SESSION['file_error'] = "File size for " . $file_key . " exceeds the " . (self::MAX_FILE_SIZE / 1024 / 1024) . "MB limit.";
            return false;
        }
        
        // Check 3: MIME Type Validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($target_subdir === self::LOGO_DIR && strpos($mime_type, 'image/') !== 0) {
            $_SESSION['file_error'] = "Security check failed: Uploaded logo file is not a valid image.";
            return false;
        }
        if ($target_subdir === self::DOCUMENT_DIR && !in_array($mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'])) {
            $_SESSION['file_error'] = "Security check failed: Uploaded document is not a valid type.";
            return false;
        }

        $new_filename = $prefix . '_' . time() . '.' . $file_extension;
        $target_file = self::BASE_UPLOAD_DIR . $target_subdir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $new_filename; 
        }
        
        $_SESSION['file_error'] = "A server error occurred while saving " . $file_key . ". Check permissions.";
        return false;
    }

    public function createBusinessView() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $user = $_SESSION['user'];
        $user_id = $user['user_id'] ?? null;
        $userProfilePicture = $user['profile_picture'] ?? null;

        $submitted_data = [];
        $application_status = null;
        
        if ($user_id) {
            // 1. Attempt to read the existing vendor account for this user
            $vendor_account_data = $this->vendor_account->readByUserId($user_id);
            
            if ($vendor_account_data) {
                // Application exists
                $submitted_data = $vendor_account_data;
                $application_status = $vendor_account_data['status'];
            }
        }

        // 2. Variables passed to the view
        $is_submitted = ($application_status === 'Pending' || $application_status === 'Approved' || $application_status === 'Rejected');
        
        // Pass the necessary variables to the view file
        extract(compact('user', 'userProfilePicture', 'submitted_data', 'application_status', 'is_submitted'));

        require_once __DIR__ . '/../views/create_business.php'; // Ensure this path is correct
    }
}