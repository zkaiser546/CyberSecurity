<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session and set headers first
session_start();
header('Content-Type: application/json');

require './vendor/autoload.php';
include "../database/dbConnect.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Check if email exists in session
    if (!isset($_SESSION['user_info']) || !isset($_SESSION['user_info']['email'])) {
        throw new Exception('No email found in session');
    }

    $email = $_SESSION['user_info']['email'];
    
    // Generate new OTP
    $verification_code = rand(100000, 999999);
    
    // Database operations
    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    $stmt = $conn->prepare("INSERT INTO verification_codes (email, code, created_at) VALUES (?, ?, NOW()) 
                           ON DUPLICATE KEY UPDATE code = ?, created_at = NOW()");
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    $stmt->bind_param("sss", $email, $verification_code, $verification_code);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to store verification code: " . $stmt->error);
    }

    $stmt->close();

    // Email setup
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'howardclintforwork@gmail.com';
    $mail->Password = 'ubek rjec dmwv tdje';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    // Disable debug output
    $mail->SMTPDebug = 0;
    
    $mail->setFrom('no-reply@yourdomain.com', 'Your Company');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'New OTP Verification Code';
    $mail->Body = 'Your new verification code is: <b>' . $verification_code . '</b>';

    if (!$mail->send()) {
        throw new Exception("Failed to send verification email: " . $mail->ErrorInfo);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'New OTP sent successfully'
    ]);

} catch (Exception $e) {
    error_log("OTP Resend Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}