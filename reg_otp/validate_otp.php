<?php
// validate_otp.php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$otpEntered = $data['otp'];

// Check if OTP matches the session value
if ($_SESSION['otp'] == $otpEntered) {
    echo json_encode(['status' => 'success', 'message' => 'OTP validated']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
}
?>
