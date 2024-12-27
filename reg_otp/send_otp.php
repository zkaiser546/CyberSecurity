<?php
// send_otp.php
session_start();
header('Content-Type: application/json');

// Mock email (replace with actual email from the form or session)
$email = json_decode(file_get_contents("php://input"), true)['email'];

// Generate OTP
$otp = rand(100000, 999999); // 6-digit OTP
$_SESSION['otp'] = $otp; // Store OTP in session

// Send OTP via email (using PHP's mail function)
$subject = "Your OTP Code";
$message = "Your OTP code is: $otp";
$headers = "From: no-reply@example.com"; // Replace with your email

if (mail($email, $subject, $message, $headers)) {
    echo json_encode(['status' => 'success', 'message' => 'OTP sent']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP']);
}
?>
