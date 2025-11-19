<?php
// api/send_otp.php

header('Content-Type: application/json');
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php'; 

require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// 1. Collect and Validate Data
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$form_data = [
    'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
    'email' => $email,
    'phone_number' => filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING),
    'address' => filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING),
    'latitude' => filter_input(INPUT_POST, 'latitude', FILTER_SANITIZE_STRING),
    'longitude' => filter_input(INPUT_POST, 'longitude', FILTER_SANITIZE_STRING),
    'password' => filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING), 
];

if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}

// 2. Generate and Save OTP
$otp_code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
$otp_expiry = date('Y-m-d H:i:s', time() + 60 * 5); 

try {
    $database = new Database(); 
    $pdo = $database->connect(); 

    // Save OTP to Database (email_verification table)
    $stmt = $pdo->prepare("INSERT INTO email_verification (email, otp_code, otp_expiry) 
                           VALUES (:email, :otp_code, :otp_expiry)
                           ON DUPLICATE KEY UPDATE otp_code = :otp_code_up, otp_expiry = :otp_expiry_up");

    $stmt->execute([
        'email' => $email,
        'otp_code' => $otp_code,
        'otp_expiry' => $otp_expiry,
        'otp_code_up' => $otp_code,
        'otp_expiry_up' => $otp_expiry,
    ]);

    // Save Full Form Data to Session for Final Registration Step
    $_SESSION['registration_data'][$email] = $form_data;


    // 3. Send Email using PHPMailer
    $mail = new PHPMailer(true);

    // --- CONFIGURATION REQUIRED HERE ---
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true; 
    $mail->Username   = 'liyagbatangan@gmail.com'; 
    $mail->Password   = 'lkcx awgt tbro eqhc'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port       = 587; 

    // --- START: ADDED FIX FOR SSL CERTIFICATE VERIFICATION ERROR ---
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    // --- END: ADDED FIX ---

    // Recipients
    $mail->setFrom('no-reply@liyagbatangan.com', 'Liyag Batangan Verification');
    $mail->addAddress($email, $form_data['name']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your Liyag Batangan Verification Code';
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #FFD700; padding: 20px; border-radius: 8px;'>
            <h1 style='color: #FFD700; border-bottom: 2px solid #B8860B; padding-bottom: 10px;'>Account Verification Required</h1>
            
            <p>Hello {$form_data['name']},</p>
            
            <p>Thank you for registering with Liyag Batangan. Please use the **One-Time Password (OTP)** below to verify your email and complete your account creation:</p>
            
            <div style='text-align: center; margin: 30px 0; padding: 20px; background-color: #fffaf0; border: 1px solid #FFD700; border-radius: 5px;'>
                <p style='margin: 0; font-size: 16px; color: #555;'>Your Verification Code is:</p>
                <h2 style='color: #B8860B; font-size: 36px; letter-spacing: 5px; margin: 10px 0;'>{$otp_code}</h2>
            </div>
            
            <p style='font-size: 14px; color: #777;'>
                This code is valid for the next **5 minutes**. Please do not share this code with anyone.
            </p>
            
            <p style='font-size: 14px; margin-top: 30px;'>
                If you did not attempt to register, you can safely ignore this email.
            </p>
        </div>";
    $mail->AltBody = "Your Liyag Batangan verification code is: {$otp_code}. This code is valid for 5 minutes.";

    $mail->send();
    
    echo json_encode(['status' => 'success', 'message' => 'Verification code sent to email.']);

} catch (Exception $e) {
    // If mail fails, clean up the database and session
    if (isset($pdo)) {
        $pdo->prepare("DELETE FROM email_verification WHERE email = ?")->execute([$email]);
    }
    if (isset($_SESSION['registration_data'][$email])) {
        unset($_SESSION['registration_data'][$email]);
    }
    
    error_log("OTP mail error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => "Failed to send email. Mailer Error: {$e->getMessage()}"]);
}