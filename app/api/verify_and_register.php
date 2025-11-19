<?php
// api/verify_and_register.php

header('Content-Type: application/json');
session_start(); 




require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$otp_code = filter_input(INPUT_POST, 'otp_code', FILTER_DEFAULT);

if (!$email || !$otp_code) {
    echo json_encode(['status' => 'error', 'message' => 'Missing email or OTP.']);
    exit;
}

try {
    $database = new Database(); 
    $pdo = $database->connect(); 
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Verify OTP and Expiry
    $stmt = $pdo->prepare("SELECT otp_code, otp_expiry FROM email_verification WHERE email = ?");
    $stmt->execute([$email]);
    $verification_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification_data || $verification_data['otp_code'] !== $otp_code) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid OTP code.']);
        exit;
    }

    if (strtotime($verification_data['otp_expiry']) < time()) {
        $pdo->prepare("DELETE FROM email_verification WHERE email = ?")->execute([$email]);
        echo json_encode(['status' => 'error', 'message' => 'OTP code has expired.']);
        exit;
    }

    // --- OTP is Valid and Not Expired ---

    // 2. Retrieve the original registration data from session
    $full_form_data = $_SESSION['registration_data'][$email] ?? null;
    
    if (!$full_form_data) {
          echo json_encode(['status' => 'error', 'message' => 'Registration data not found. Please resubmit the form.']);
          exit;
    }
    
    // --- NEW: START DATABASE TRANSACTION ---
    $pdo->beginTransaction();

    // 3. SECURELY Create the User Account
    
    // WARNING: Changed to SHA-256 to match legacy schema, but password_hash() is strongly recommended.
    $password_hash = hash('sha256', $full_form_data['password']);
    
    // Check if email is ALREADY registered 
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $check_stmt->execute([$email]);
    if ($check_stmt->fetchColumn() > 0) {
        $pdo->rollBack(); 
        echo json_encode(['status' => 'error', 'message' => 'Email is already registered.']);
        unset($_SESSION['registration_data'][$email]);
        exit;
    }

    // Insert new user into the 'users' table
    $user_stmt = $pdo->prepare("
        INSERT INTO users 
        (name, email, password, phone_number, address, latitude, longitude) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $user_stmt->execute([
        $full_form_data['name'],
        $email,
        $password_hash, // Use the SHA-256 hashed password
        $full_form_data['phone_number'],
        $full_form_data['address'],
        $full_form_data['latitude'],
        $full_form_data['longitude']
    ]);
    
    if (!$success) {
        throw new Exception("Database insertion failed."); 
    }

    // 4. Clean up the OTP record and session data
    $pdo->prepare("DELETE FROM email_verification WHERE email = ?")->execute([$email]);
    
    // --- NEW: COMMIT TRANSACTION ---
    $pdo->commit();

    // Clean up session data last
    unset($_SESSION['registration_data'][$email]);

    echo json_encode(['status' => 'success', 'message' => 'Registration successful! Redirecting to login...']);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("PDO Registration Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred during account creation.']);
} catch (Exception $e) {
    error_log("Secure Registration Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A system error prevented account creation.']);
}