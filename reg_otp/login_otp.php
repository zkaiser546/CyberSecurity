<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering and session
ob_start();
session_start();

// Include database connection
require_once('../database/dbConnect.php');

// Function to send JSON response
function sendJsonResponse(bool $success, string $message, string $redirect = ''): void {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'redirect' => $redirect
    ]);
    exit;
}

// Handle AJAX POST requests only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    try {
        // Validate required fields
        $requiredFields = ['email', 'password', 'otp'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("Missing required field: $field");
            }
        }

        // Sanitize inputs
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        $password = hash('sha3-512', $_POST['password']);
        $otp = preg_replace('/[^0-9]/', '', trim($_POST['otp']));

        if (strlen($otp) !== 6) {
            throw new Exception("Invalid OTP format");
        }

        // Verify OTP first
        $stmt = $conn->prepare("
            SELECT id, attempts, created_at 
            FROM verification_codes 
            WHERE email = ? 
            AND code = ? 
            AND created_at >= NOW() - INTERVAL 10 MINUTE
        ");

        if (!$stmt) {
            error_log("Database Error: " . $conn->error);
            throw new Exception("System error occurred. Please try again.");
        }

        $stmt->bind_param("ss", $email, $otp);
        
        if (!$stmt->execute()) {
            error_log("Execute Error: " . $stmt->error);
            throw new Exception("Failed to verify code. Please try again.");
        }
        
        $result = $stmt->get_result();
        $otpData = $result->fetch_assoc();

        if (!$otpData) {
            $attemptsStmt = $conn->prepare("
                SELECT attempts, created_at 
                FROM verification_codes 
                WHERE email = ?
            ");
            
            if (!$attemptsStmt) {
                throw new Exception("System error occurred. Please try again.");
            }

            $attemptsStmt->bind_param("s", $email);
            $attemptsStmt->execute();
            $attemptsResult = $attemptsStmt->get_result();
            $attemptData = $attemptsResult->fetch_assoc();

            if ($attemptData) {
                if ($attemptData['attempts'] >= 3) {
                    throw new Exception('Too many incorrect attempts. Please request a new code.');
                }

                // Check if OTP has expired
                $createdTime = strtotime($attemptData['created_at']);
                if ((time() - $createdTime) > 600) { // 10 minutes
                    throw new Exception('Verification code has expired. Please request a new one.');
                }

                // Increment attempts
                $updateStmt = $conn->prepare("
                UPDATE {$info['table']} 
                SET status = 'Active' 
                WHERE {$info['id_column']} = ?
            ");
                $updateStmt->bind_param("s", $email);
                $updateStmt->execute();
            }

            throw new Exception('Invalid verification code.');
        }

     
        $userTypes = [
          'supAdmin' => [
              'table' => 'supAdmin', 
              'id_column' => 'spAd_ID', 
              'redirect' => '../super_admin/super_admin.php'
          ],
          'admin' => [
              'table' => 'admin', 
              'id_column' => 'admin_id', 
              'redirect' => '../admin/admin.php'
          ],
          'users' => [
              'table' => 'users', 
              'id_column' => 'user_id', 
              'redirect' => '../user/user.php'
          ]
      ];
      
      // Inside your try block:
      $userFound = false;
      $redirect = '';
      $userType = '';  // Add this to track user type
      
      foreach ($userTypes as $type => $info) {
          $stmt = $conn->prepare("
              SELECT {$info['id_column']}, password, status
              FROM {$info['table']} 
              WHERE email = ?
          ");
          
          if (!$stmt) {
              continue;
          }
      
          $stmt->bind_param("s", $email);
          $stmt->execute();
          $result = $stmt->get_result();
      
          if ($userData = $result->fetch_assoc()) {
              if ($password === $userData['password']) {
                  // Check if account is blocked/inactive
                  if ($userData['status'] === 'Blocked') {
                      throw new Exception('This account has been blocked. Please contact support.');
                  }
      
                  // Store multiple session variables for better tracking
                  $_SESSION['user_ID'] = $userData[$info['id_column']];
                  $_SESSION['user_type'] = $type;  // Store the user type
                  $_SESSION['user_email'] = $email;
                  
                  $userFound = true;
                  $redirect = $info['redirect'];
                  
                  // Update user status
                  $updateStmt = $conn->prepare("
                      UPDATE {$info['table']} 
                      SET status = 'Active' 
                      WHERE {$info['id_column']} = ?
                  ");
                  
                  if (!$updateStmt) {
                      throw new Exception("Failed to prepare status update statement.");
                  }
                  
                  $updateStmt->bind_param("s", $userData[$info['id_column']]); 
                  
                  if (!$updateStmt->execute()) {
                      throw new Exception("Failed to update user status.");
                  }
                  
                  break;
              }
          }
      }
      
      if (!$userFound) {
          throw new Exception('Invalid credentials.');
      }

        // Delete used OTP
        $deleteStmt = $conn->prepare("DELETE FROM verification_codes WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();

        // Send success response
        sendJsonResponse(true, 'Login successful!', $redirect);

    } catch (Exception $e) {
        error_log("Login OTP Error: " . $e->getMessage());
        sendJsonResponse(false, $e->getMessage());
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
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

        .input-focus {
            transition: all 0.3s ease;
        }

        .input-focus:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen p-4">
    <div class="glass w-full max-w-md p-8">
        <h1 class="text-3xl font-bold text-white mb-6 text-center uppercase">Verify OTP</h1>
        <p class="text-gray-300 text-center mb-8">Please enter the verification code sent to your email</p>
        
        <form id="otp-form" method="POST" class="space-y-6">
            <div class="mb-6">
                <label for="otp" class="block text-sm font-medium text-gray-300 mb-2">Verification Code</label>
                <input type="text" 
                       maxlength="6" 
                       id="otp" 
                       name="otp"
                       class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 input-focus text-center tracking-widest"
                       placeholder="Enter 6-digit code" 
                       required
                       pattern="\d{6}"
                       title="Please enter a 6-digit code"
                       autocomplete="off">
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg hover:bg-blue-600 transition button">
                Verify
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-400">
                Code expires in: <span id="countdown" class="font-medium">10:00</span>
            </p>
        </div>
    </div>

    <script>
    function startTimer(duration, display) {
        let timer = duration;
        const countdown = setInterval(() => {
            const minutes = Math.floor(timer / 60);
            const seconds = timer % 60;

            display.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            if (--timer < 0) {
                clearInterval(countdown);
                display.textContent = "Expired";
                Swal.fire({
                    title: 'Code Expired',
                    text: 'Please return to login and request a new code',
                    icon: 'warning',
                    confirmButtonText: 'Return to Login',
                    background: '#2a2f3b',
                    color: '#ffffff',
                    confirmButtonColor: '#4a90e2',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = 'login.php';
                });
            }
        }, 1000);

        return countdown;
    }

    document.addEventListener("DOMContentLoaded", function() {
        const countdown = startTimer(600, document.querySelector('#countdown'));
        const form = document.querySelector('#otp-form');
        
        // Format OTP input
        const otpInput = document.querySelector('#otp');
        otpInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
        });

        form.addEventListener('submit', async function(event) {
            event.preventDefault();

            try {
                const formData = new FormData(form);
                formData.append('ajax', 'true');
                
                const storedEmail = sessionStorage.getItem('loginEmail');
                const storedPassword = sessionStorage.getItem('loginPassword');

                if (!storedEmail || !storedPassword) {
                    throw new Error('Session expired. Please login again.');
                }

                formData.append('email', storedEmail);
                formData.append('password', storedPassword);

                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Verifying...';

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    clearInterval(countdown);
                    sessionStorage.removeItem('loginEmail');
                    sessionStorage.removeItem('loginPassword');
                    
                    await Swal.fire({
                        title: 'Success!',
                        text: result.message,
                        icon: 'success',
                        confirmButtonText: 'Continue',
                        background: '#2a2f3b',
                        color: '#ffffff',
                        confirmButtonColor: '#4a90e2',
                        allowOutsideClick: false
                    });
                    
                    window.location.href = result.redirect;
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                
                Swal.fire({
                    title: 'Error',
                    text: error.message,
                    icon: 'error',
                    confirmButtonText: 'Try Again',
                    background: '#2a2f3b',
                    color: '#ffffff',
                    confirmButtonColor: '#4a90e2'
                });
            } finally {
                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = false;
                submitButton.textContent = 'Verify';
            }
        });
    });
    </script>
</body>
</html>