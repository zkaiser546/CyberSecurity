<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require './vendor/autoload.php';
include '../CyberSecurity/database/dbConnect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './vendor/phpmailer/phpmailer/src/Exception.php';
require './vendor/phpmailer/phpmailer/src/PHPMailer.php';
require './vendor/phpmailer/phpmailer/src/SMTP.php';

session_start();

// Function to encrypt email using AES-256
function encryptEmail($email, $key) {
    // Generate a random IV
    $iv = openssl_random_pseudo_bytes(16);
    
    // Encrypt the email
    $encrypted = openssl_encrypt(
        $email,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    // Combine IV and encrypted data
    $combined = $iv . $encrypted;
    
    // Return base64 encoded string
    return base64_encode($combined);
}

// Function to decrypt email
function decryptEmail($encryptedData, $key) {
    try {
        // Decode from base64
        $combined = base64_decode($encryptedData);
        
        // Check if we have enough data
        if (strlen($combined) <= 16) {
            error_log("Decryption error: Invalid encrypted data length");
            return false;
        }
        
        // Extract IV and encrypted data
        $iv = substr($combined, 0, 16);
        $encrypted = substr($combined, 16);
        
        // Decrypt the email
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($decrypted === false) {
            error_log("Decryption error: openssl_decrypt failed");
            return false;
        }
        
        return $decrypted;
    } catch (Exception $e) {
        error_log("Decryption error: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    ob_start();
    
    try {
        $encryptionKey = 'SecureFeedback250';
        $email = trim($_POST['email'] ?? '');
        $password = hash('sha3-512', $_POST['password'] ?? '');
        $userFound = false;

        // Check Super Admin first
        $supadminStmt = $conn->prepare("SELECT spAd_ID, username, password, email FROM supAdmin");
        if (!$supadminStmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        $supadminStmt->execute();
        $supadminResult = $supadminStmt->get_result();

        while ($supadminRow = $supadminResult->fetch_assoc()) {
            $decrypted_email = decryptEmail($supadminRow['email'], $encryptionKey);
            if ($decrypted_email !== false && $decrypted_email === $email) {
                if ($password === $supadminRow['password']) {
                    $user_id = $supadminRow['spAd_ID'];
                    $username = $supadminRow['username'];
                    $encrypted_email = $supadminRow['email'];
                    $userFound = true;
                    break;
                }
            }
        }
        $supadminStmt->close();

        // Check Admin if not found
        if (!$userFound) {
            $adminStmt = $conn->prepare("SELECT admin_ID, username, password, email FROM admin");
            if (!$adminStmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }

            $adminStmt->execute();
            $adminResult = $adminStmt->get_result();

            while ($adminRow = $adminResult->fetch_assoc()) {
                $decrypted_email = decryptEmail($adminRow['email'], $encryptionKey);
                if ($decrypted_email !== false && $decrypted_email === $email) {
                    if ($password === $adminRow['password']) {
                        $user_id = $adminRow['admin_ID'];
                        $username = $adminRow['username'];
                        $encrypted_email = $adminRow['email'];
                        $userFound = true;
                        break;
                    }
                }
            }
            $adminStmt->close();
        }

        // Finally check regular users if still not found
        if (!$userFound) {
            $userStmt = $conn->prepare("SELECT user_id, username, password, email, manage_users FROM users");
            if (!$userStmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }

            $userStmt->execute();
            $userResult = $userStmt->get_result();

            while ($userRow = $userResult->fetch_assoc()) {
                $decrypted_email = decryptEmail($userRow['email'], $encryptionKey);
                if ($decrypted_email !== false && $decrypted_email === $email) {
                    if ($password === $userRow['password']) {
                        if (isset($userRow['manage_users']) && $userRow['manage_users'] === 'Disabled') {
                            throw new Exception('Your account is currently disabled. Please contact the administrator.');
                        }
                        $user_id = $userRow['user_id'];
                        $username = $userRow['username'];
                        $encrypted_email = $userRow['email'];
                        $userFound = true;
                        break;
                    } else {
                        throw new Exception('Invalid password.');
                    }
                }
            }
            $userStmt->close();
        }

        if (!$userFound) {
            throw new Exception('Invalid email or password.');
        }

        // Generate OTP
        $verification_code = rand(100000, 999999);

        // Delete previous OTP
        $delete_stmt = $conn->prepare("DELETE FROM verification_codes WHERE email = ?");
        if (!$delete_stmt) {
            throw new Exception("Failed to prepare deletion statement: " . $conn->error);
        }
        $delete_stmt->bind_param("s", $email); // Use original email for verification codes
        $delete_stmt->execute();
        $delete_stmt->close();

        // Insert new OTP
        $stmt = $conn->prepare("INSERT INTO verification_codes (email, code, created_at) VALUES (?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Failed to prepare verification code storage: " . $conn->error);
        }
        $stmt->bind_param("ss", $email, $verification_code);
        if (!$stmt->execute()) {
            throw new Exception("Failed to store verification code: " . $stmt->error);
        }
        $stmt->close();

        // Store login info in session
        if ($userFound) {
            if (isset($supadminRow)) {
                $_SESSION['spAd_ID'] = $user_id;
            } else if (isset($adminRow)) {
                $_SESSION['admin_ID'] = $user_id;
            } else {
                $_SESSION['user_ID'] = $user_id;
            }
            $_SESSION['email'] = $encrypted_email;
            $_SESSION['username'] = $username;
        }

        // Send OTP via email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'howardclintforwork@gmail.com';
            $mail->Password = 'ubek rjec dmwv tdje';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('no-reply@yourdomain.com', 'Secure Login');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Login Verification Code';
            $mail->Body = "
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
                        <p>Here is your verification code:</p>
                        <p class='otp-code'>$verification_code</p>
                        <p>This code will expire in 10 minutes.</p>
                        <p class='warning'>Do not share this code with anyone.</p>
                    </div>
                </body>
                </html>
            ";

            if (!$mail->send()) {
                throw new Exception("Failed to send verification email: " . $mail->ErrorInfo);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Verification code sent successfully!',
                'redirect' => '../CyberSecurity/reg_otp/login_otp.php'
            ]);
        } catch (Exception $e) {
            throw new Exception("Failed to send verification email: " . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    ob_end_flush();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="icon" href="Logo/Feedback_Logo.png" type="image/x-icon">
    <style>
        body {
            background: linear-gradient(135deg, #1c1f26, #2b303b);
            color: white;
            font-family: 'Inter', sans-serif;
        }

        .glass {
            backdrop-filter: blur(10px);
            background: rgba(40, 44, 52, 0.9);
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.3);
            border-radius: 8px;
        }

        .button:hover {
            transform: translateY(-2px);
            background: linear-gradient(to right, #4a90e2, #50e3c2);
        }
    </style>
</head>

<body class="flex items-center justify-center h-screen">
    <div class="glass w-full max-w-md p-8">
        <h1 class="text-3xl font-bold text-white mb-6 text-center uppercase">Welcome Back</h1>
        <div id="error-message" class="mb-4"></div>
        <form id="login-form" method="POST">
            <!-- Email -->
            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email"
                    class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500"
                    required>
            </div>
            <!-- Password -->
            <div class="mb-6 relative">
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" placeholder="Enter your password"
                        class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500 pr-10"
                        required>
                    <button type="button" class="absolute inset-y-0 right-2 flex items-center justify-center text-gray-400"
                        onclick="togglePasswordVisibility('password')">
                        <span id="togglePasswordIcon"><i class="fa-solid fa-eye"></i></span>
                    </button>
                </div>
            </div>
            <!-- Login Button -->
            <button type="submit" class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg hover:bg-blue-600 transition button">
                Login
            </button>
        </form>
        <!-- Sign-Up Redirect -->
        <div class="mt-6 text-center">
            <p class="text-gray-400">Don't have an account?
                <a href="signup.php" class="text-blue-400 hover:underline">Sign Up</a>
            </p>
        </div>
    </div>

    <script>
         function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector("i");
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const form = document.querySelector('#login-form');

        form.addEventListener('submit', async function(event) {
            event.preventDefault();

            try {
                const formData = new FormData(form);
                formData.append('ajax', 'true');

                // Store credentials in sessionStorage for OTP page
                sessionStorage.setItem('loginEmail', formData.get('email'));
                sessionStorage.setItem('loginPassword', formData.get('password'));

                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });

                const rawResponse = await response.text();
                console.log('Raw response:', rawResponse);

                let result;
                try {
                    result = JSON.parse(rawResponse);
                } catch (e) {
                    throw new Error(`Invalid JSON response: ${rawResponse}`);
                }

                if (result.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: result.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        background: '#2a2f3b',
                        color: '#ffffff',
                        confirmButtonColor: '#4a90e2',
                    }).then(() => {
                        window.location.href = result.redirect;
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: result.message,
                        icon: 'error',
                        confirmButtonText: 'OK',
                        background: '#2a2f3b',
                        color: '#ffffff',
                        confirmButtonColor: '#4a90e2',
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'An unexpected error occurred',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    background: '#2a2f3b',
                    color: '#ffffff',
                    confirmButtonColor: '#4a90e2',
                });
            }
        });
    });
    </script>
</body>

</html>