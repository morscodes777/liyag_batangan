<?php
// api/verify_password_otp.php

header('Content-Type: application/json');

// NOTE: Ensure your autoloader/config includes necessary security and hashing components.
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Clean and validate inputs
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$otp_code = filter_input(INPUT_POST, 'otp_code', FILTER_DEFAULT);
$new_password = filter_input(INPUT_POST, 'new_password', FILTER_UNSAFE_RAW);

if (!$email || !$otp_code || !$new_password) {
    echo json_encode(['status' => 'error', 'message' => 'Missing email, OTP, or password.']);
    exit;
}

// Basic password strength check
if (strlen($new_password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long.']);
    exit;
}


try {
    $database = new Database(); 
    $pdo = $database->connect(); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Verify OTP and Expiry from the 'users' table
    $stmt = $pdo->prepare("SELECT otp_code, otp_expiry FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $verification_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists, or if OTP fields are missing/null
    if (!$verification_data || empty($verification_data['otp_code'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request. Please ensure you sent the verification code.']);
        exit;
    }

    // Check if the provided OTP matches the stored OTP
    if ($verification_data['otp_code'] !== $otp_code) {
        error_log("Failed OTP attempt for email: " . $email);
        echo json_encode(['status' => 'error', 'message' => 'Invalid verification code.']);
        exit;
    }

    // Check if OTP is expired
    if (strtotime($verification_data['otp_expiry']) < time()) {
        // Clean up expired OTP fields
        $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expiry = NULL WHERE email = ?")->execute([$email]);
        echo json_encode(['status' => 'error', 'message' => 'OTP code has expired. Please resend the request.']);
        exit;
    }

    // --- OTP is Valid and Not Expired ---
    
    $pdo->beginTransaction();

    // 2. Hash the new password using SHA-256 (as requested)
    $password_hash = hash('sha256', $new_password);
    
    // 3. Update the user's password and clear the OTP fields
    $user_stmt = $pdo->prepare("
        UPDATE users 
        SET password = ?, otp_code = NULL, otp_expiry = NULL 
        WHERE email = ?
    ");
    
    $success = $user_stmt->execute([
        $password_hash,
        $email
    ]);
    
    if (!$success) {
        throw new Exception("Database password update failed."); 
    }
    
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Password reset successful!']);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("PDO Password Reset Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred during the password reset.']);
} catch (Exception $e) {
    error_log("Password Reset Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A system error prevented the password reset.']);
}