<?php
// resend_otp.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'email_errors.log');

require '../vendor/autoload.php'; // Add PHPMailer autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

include '../database/dbConnect.php';

session_start();
header('Content-Type: application/json');

function generateOTP() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sendOTPEmail($email, $otp) {
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;  // Enable verbose debug output if needed
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Replace with your SMTP host
        $mail->SMTPAuth   = true;
        $mail->Username = 'howardclintforwork@gmail.com';
        $mail->Password = 'ubek rjec dmwv tdje';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('your-email@gmail.com', 'Secure Feedback');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your New OTP Verification Code';
        $mail->Body    = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { padding: 20px; }
                    .otp-code { 
                        font-size: 24px; 
                        font-weight: bold;
                        color: #4a90e2;
                        letter-spacing: 2px;
                    }
                    .warning {
                        color: #e74c3c;
                        font-size: 14px;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Your Verification Code</h2>
                    <p>Here is your new verification code:</p>
                    <p class='otp-code'>$otp</p>
                    <p>This code will expire in 10 minutes.</p>
                    <p class='warning'>Do not share this code with anyone.</p>
                </div>
            </body>
            </html>
        ";

        $mail->send();
        error_log("Email successfully sent to $email");
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    // Check if this is an AJAX request
    if (!isset($_POST['ajax'])) {
        throw new Exception('Invalid request method');
    }

    // Get email from session
    $email = $_SESSION['user_info']['email'] ?? null;
    
    if (!$email) {
        throw new Exception('Email not found in session');
    }

    // Generate new OTP
    $newOTP = generateOTP();

    // Update database with new OTP
    $stmt = $conn->prepare("
        UPDATE verification_codes 
        SET code = ?, 
            created_at = CURRENT_TIMESTAMP,
            expires_at = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 10 MINUTE),
            attempts = 0
        WHERE email = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    $stmt->bind_param("ss", $newOTP, $email);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        $stmt->close();
        
        $stmt = $conn->prepare("
            INSERT INTO verification_codes (email, code, created_at, expires_at, attempts)
            VALUES (?, ?, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 10 MINUTE), 0)
        ");
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        $stmt->bind_param("ss", $email, $newOTP);
        $stmt->execute();
    }
    $stmt->close();

    // Send email with new OTP
    if (sendOTPEmail($email, $newOTP)) {
        echo json_encode([
            'success' => true,
            'message' => 'New OTP has been sent to your email'
        ]);
    } else {
        throw new Exception('Failed to send OTP email. Please try again.');
    }

} catch (Exception $e) {
    error_log("Resend OTP Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>