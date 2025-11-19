<?php
require_once __DIR__ . '/../models/vendorModel.php';
require_once __DIR__ . '/../models/notificationModel.php';
require_once __DIR__ . '/../models/userModel.php';

// Suppress output of PHP Notices/Warnings which can corrupt JSON
error_reporting(E_ERROR | E_PARSE); 

$vendorModel = new VendorModel();
$notificationModel = new NotificationModel();
$userModel = new UserModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Set JSON header for ALL AJAX POST actions
    header('Content-Type: application/json');
    
    $action = $_POST['action'];

    // ✅ View vendor details (AJAX)
    if ($action === 'view_vendor') {
        $vendor_id = intval($_POST['vendor_id'] ?? 0); 
        if ($vendor_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid vendor ID provided.']);
            exit;
        }

        $vendor = $vendorModel->getVendorDetails($vendor_id);

        if ($vendor) {
            echo json_encode(['success' => true, 'vendor' => $vendor]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Vendor details not found or failed to retrieve.']);
        }
        exit;
    }

    // 🚀 Approve vendor (NEW LOGIC)
    if ($action === 'approve_vendor') {
        $vendor_id = intval($_POST['vendor_id'] ?? 0); 
        
        if ($vendor_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid vendor ID provided.']);
            exit;
        }

        if ($vendorModel->approveVendor($vendor_id)) {
            
            // 1. Get the full vendor record to find the user_id
            $vendor = $vendorModel->getVendorDetails($vendor_id); 
            $user_id = intval($vendor['user_id'] ?? 0); 
            
            if ($user_id <= 0) {
                // Keep approval as success, but report user lookup failure
                echo json_encode(['success' => true, 'message' => 'Vendor approved, but failed to retrieve associated user ID for notification/user update.']);
                exit; // Use exit here as the primary action succeeded
            }

            // 2. Update user_type from 'User' to 'Vendor'
            $user_type_updated = $userModel->updateUserType($user_id, 'Vendor');
            
            if (!$user_type_updated) {
                // Keep approval as success, but report user type failure
                echo json_encode(['success' => true, 'message' => 'Vendor approved, but failed to update user type to Vendor.']);
                exit; // Use exit here as the primary action succeeded
            }

            // 3. Send success notification to the retrieved user_id
            $title = "Store Approved! 🎉";
            $message = "Congratulations! Your store registration has been approved. You can now start selling!";
            $notificationModel->sendNotification($user_id, $title, $message);

            echo json_encode(['success' => true, 'message' => 'Vendor approved, user type updated, and notification sent.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve vendor in the database.']);
        }
        exit;
    }   

    // 🚀 Reject/Suspend vendor (NEW LOGIC)
    if ($action === 'reject_vendor') {
        $vendor_id = intval($_POST['vendor_id'] ?? 0); 
        $reason = trim($_POST['reason'] ?? 'No reason provided.');

        if ($vendor_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid vendor ID provided.']);
            exit;
        }

        // 1. Get the full vendor record, which should contain the user_id
        $vendor = $vendorModel->getVendorDetails($vendor_id); 
        $user_id = intval($vendor['user_id'] ?? 0); // Safely extract user_id

        if ($user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Failed to retrieve associated user ID for vendor.']);
            exit;
        }

        // 2. Proceed with rejection and notification using the retrieved user_id
        if ($vendorModel->rejectVendor($vendor_id, $reason)) {
            // Send rejection notification to the user_id
            $title = "Store Rejected ❌";
            $display_reason = empty($reason) ? 'Please contact administration for details.' : htmlspecialchars($reason);
            $message = "Your store registration has been rejected. Reason: " . $display_reason;
            
            $notificationModel->sendNotification($user_id, $title, $message);

            echo json_encode(['success' => true, 'message' => 'Vendor rejected and notification sent.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject vendor in the database.']);
        }
        exit;
    }
    
    // 🛑 CRITICAL FIX: Catch-all for unknown POST actions
    echo json_encode(['success' => false, 'message' => 'Unknown POST action.']);
    exit;
}

