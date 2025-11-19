<?php
// api/send_password_otp.php

header('Content-Type: application/json');


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Adjust path as needed
require __DIR__ . '/../../vendor/autoload.php'; 
require_once __DIR__ . '/../config/liyab_batangan_db_pdo.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

try {
    $database = new Database(); 
    $pdo = $database->connect(); 
    
    // 1. Check if the user is registered
    $stmt = $pdo->prepare("SELECT name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Return a vague message for security (don't confirm if email exists)
        echo json_encode(['status' => 'error', 'message' => 'If the email is registered, a code has been sent.']);
        exit;
    }

    $user_name = $user['name'] ?? 'Customer';
    
    // 2. Generate and Save OTP to the existing 'users' table
    $otp_code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', time() + 60 * 5); // 5 minutes expiry

    // Update OTP fields in the 'users' table
    $stmt = $pdo->prepare("UPDATE users 
                           SET otp_code = :otp_code, otp_expiry = :otp_expiry
                           WHERE email = :email");

    $stmt->execute([
        'email' => $email,
        'otp_code' => $otp_code,
        'otp_expiry' => $otp_expiry
    ]);

    // 3. Send Email using PHPMailer
    $mail = new PHPMailer(true);

    // --- SMTP CONFIGURATION ---
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true; 
    $mail->Username   = 'liyagbatangan@gmail.com'; 
    $mail->Password   = 'lkcx awgt tbro eqhc'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port       = 587; 

    // FIX for SSL certificate error
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    // Recipients
    $mail->setFrom('no-reply@liyagbatangan.com', 'Liyag Batangan Password Reset');
    $mail->addAddress($email, $user_name);

    // Content (Gold-themed HTML)
    $mail->isHTML(true);
    $mail->Subject = 'Your Liyag Batangan Password Reset Code';
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #FFD700; padding: 20px; border-radius: 8px;'>
            <h1 style='color: #FFD700; border-bottom: 2px solid #B8860B; padding-bottom: 10px;'>Password Reset Request</h1>
            
            <p>Hello {$user_name},</p>
            
            <p>We received a request to reset your password. Please use the **One-Time Password (OTP)** below to verify your identity:</p>
            
            <div style='text-align: center; margin: 30px 0; padding: 20px; background-color: #fffaf0; border: 1px solid #FFD700; border-radius: 5px;'>
                <p style='margin: 0; font-size: 16px; color: #555;'>Your Reset Code is:</p>
                <h2 style='color: #B8860B; font-size: 36px; letter-spacing: 5px; margin: 10px 0;'>{$otp_code}</h2>
            </div>
            
            <p style='font-size: 14px; color: #777;'>
                This code is valid for the next **5 minutes**. If you did not request a password reset, you can safely ignore this email.
            </p>
        </div>";
    $mail->AltBody = "Your Liyag Batangan password reset code is: {$otp_code}. This code is valid for 5 minutes.";

    $mail->send();
    
    // Use the vague message even on success for security (prevents enumeration)
    echo json_encode(['status' => 'success', 'message' => 'If the email is registered, a code has been sent.']);

} catch (Exception $e) {
    error_log("Password Reset Mailer Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => "Failed to process your request. Please try again later."]);
}