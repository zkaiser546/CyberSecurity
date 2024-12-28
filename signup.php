<?php
require './vendor/autoload.php';
include '../CyberSecurity/database/dbConnect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './vendor/phpmailer/phpmailer/src/Exception.php';
require './vendor/phpmailer/phpmailer/src/PHPMailer.php';
require './vendor/phpmailer/phpmailer/src/SMTP.php';

session_start();

// Validation functions remain the same
function validateUsername($username)
{
  return preg_match('/^(?=.*[a-zA-Z])(?=.*\d)[a-zA-Z\d]{6,}$/', $username);
}

function validateEmail($email)
{
  return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Initialize variables
$username = '';
$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
  try {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($username) && !empty($email) && !empty($password) && !empty($confirm_password)) {
      if (validateUsername($username)) {
        if (validateEmail($email)) {
          if ($password === $confirm_password) {
            if (
              strlen($password) >= 8 &&
              preg_match('/[A-Z]/', $password) &&
              preg_match('/[a-z]/', $password) &&
              preg_match('/\d/', $password) &&
              preg_match('/[\W_]/', $password)
            ) {
              // Check if email or username already exists
              $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
              if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
              }

              $stmt->bind_param("ss", $email, $username);
              if (!$stmt->execute()) {
                throw new Exception("Database execute error: " . $stmt->error);
              }

              $stmt->bind_result($count);
              $stmt->fetch();
              $stmt->close();

              if ($count == 0) {
                // Hash the password using SHA3-512
                if (!function_exists('hash') || !in_array('sha3-512', hash_algos())) {
                  throw new Exception("SHA3-512 hashing not available");
                }

                $hashed_password = hash('sha3-512', $password);

                // Store user data in session
                $_SESSION['user_info'] = [
                  'username' => $username,
                  'email' => $email,
                  'password' => $hashed_password
                ];

                // Generate verification code
                $verification_code = rand(100000, 999999);

                // Store verification code
                $stmt = $conn->prepare("INSERT INTO verification_codes (email, code, created_at) VALUES (?, ?, NOW())");
                if (!$stmt) {
                  throw new Exception("Database prepare error: " . $conn->error);
                }

                $stmt->bind_param("ss", $email, $verification_code);

                if (!$stmt->execute()) {
                  throw new Exception("Failed to store verification code: " . $stmt->error);
                }

                $stmt->close();

                // Send verification email
                $mail = new PHPMailer(true);

                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'howardclintforwork@gmail.com';
                $mail->Password = 'ubek rjec dmwv tdje';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('no-reply@yourdomain.com', 'Your Company');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Email Verification Code';
                $mail->Body = 'Your verification code is: <b>' . $verification_code . '</b>';

                if (!$mail->send()) {
                  throw new Exception("Failed to send verification email: " . $mail->ErrorInfo);
                }

                echo json_encode(['success' => true, 'message' => 'Verification email sent successfully!']);
                exit;
              } else {
                echo json_encode(['success' => false, 'message' => 'Email or username already exists.']);
                exit;
              }
            } else {
              echo json_encode(['success' => false, 'message' => 'Password must meet complexity requirements.']);
              exit;
            }
          } else {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
          }
        } else {
          echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
          exit;
        }
      } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username format.']);
        exit;
      }
    } else {
      echo json_encode(['success' => false, 'message' => 'All fields are required.']);
      exit;
    }
  } catch (Exception $e) {
    // Log the error for debugging
    error_log("Signup Error: " . $e->getMessage());

    // Send a more specific error message to the client
    echo json_encode([
      'success' => false,
      'message' => 'An error occurred during signup: ' . $e->getMessage()
    ]);
    exit;
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
    <h1 class="text-3xl font-bold text-white mb-6 text-center uppercase">Create Account</h1>
    <form method="POST" id="signUpForm">
      <div class="mb-6">
        <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Username</label>
        <input type="text" id="username" name="username" placeholder="Choose a username"
          class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
      </div>
      <div class="mb-6">
        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email"
          class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
      </div>
      <div class="mb-6 relative">
        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
        <div class="relative">
          <input type="password" id="password" name="password" placeholder="Create a password"
            class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500 pr-10" required>
          <button type="button" class="absolute inset-y-0 right-2 flex items-center justify-center text-gray-400"
            onclick="togglePasswordVisibility('password')">
            <span id="togglePasswordIcon"><i class="fa-solid fa-eye"></i></span>
          </button>
        </div>
      </div>
      <div class="mb-6 relative">
        <label for="confirm-password" class="block text-sm font-medium text-gray-300 mb-2">Confirm Password</label>
        <div class="relative">
          <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm your password"
            class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500 pr-10" required>
          <button type="button" class="absolute inset-y-0 right-2 flex items-center justify-center text-gray-400"
            onclick="togglePasswordVisibility('confirm-password')">
            <span id="toggleConfirmPasswordIcon"><i class="fa-solid fa-eye"></i></span>
          </button>
        </div>
      </div>
      <button type="submit"
        class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg hover:bg-blue-600 transition button" name="sign-up">
        Sign Up
      </button>
    </form>
    <div class="mt-6 text-center">
      <p class="text-gray-400">Already have an account?
        <a href="login.php" class="text-blue-400 hover:underline">Login</a>
      </p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
      const form = document.querySelector('#signUpForm');

      form.addEventListener('submit', async function(event) {
        event.preventDefault();

        try {
          const formData = new FormData(form);
          formData.append('ajax', 'true');

          const response = await fetch('signup.php', {
            method: 'POST',
            body: formData,
          });

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const result = await response.json();

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
              window.location.href = "../CyberSecurity/reg_otp/otp.php";
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
            text: 'Server error: ' + error.message,
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