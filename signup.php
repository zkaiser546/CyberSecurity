<?php
require './vendor/autoload.php';
// Include database connection
include '../CyberSecurity/database/dbConnect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './vendor/phpmailer/phpmailer/src/Exception.php';
require './vendor/phpmailer/phpmailer/src/PHPMailer.php';
require './vendor/phpmailer/phpmailer/src/SMTP.php';

session_start();

// Validation functions
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign-up'])) {
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  // Check if all fields are filled
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
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count == 0) {
              // Generate verification code
              $verification_code = rand(100000, 999999);

              // Store verification code in OTP table
              $stmt = $conn->prepare("INSERT INTO password_resets (email, code, created_at) VALUES (?, ?, NOW())");
              $stmt->bind_param("ss", $email, $verification_code);

              if ($stmt->execute()) {
                $stmt->close();

                // Send verification email
                $mail = new PHPMailer(true);
                try {
                  $mail->isSMTP();
                  $mail->Host = 'smtp.gmail.com';
                  $mail->SMTPAuth = true;
                  $mail->Username = 'howardclintforwork@gmail.com';
                  $mail->Password = 'helloworld12345';
                  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                  $mail->Port = 587;

                  $mail->setFrom('no-reply@yourdomain.com', 'Your Website Name');
                  $mail->addAddress($email);
                  $mail->isHTML(true);
                  $mail->Subject = 'Email Verification Code';
                  $mail->Body = 'Your verification code is: <b>' . $verification_code . '</b>';
                  $mail->send();

                  // Store session data
                  $_SESSION['email'] = $email;
                  $_SESSION['username'] = $username;
                  $_SESSION['password'] = password_hash($password, PASSWORD_BCRYPT);
                } catch (Exception $e) {
                  $error = "Failed to send verification code. Please try again.";
                }
              } else {
                $error = 'Failed to process your request. Please try again.';
              }
            } else {
              $error = 'Email or username already exists.';
            }
          } else {
            $error = 'Password must meet complexity requirements.';
          }
        } else {
          $error = 'Passwords do not match.';
        }
      } else {
        $error = 'Invalid email format.';
      }
    } else {
      $error = 'Invalid username format.';
    }
  } else {
    $error = 'All fields are required.';
  }

  // Show error message with custom SweetAlert
  if (!empty($error)) {
    echo "<script>
            Swal.fire({
              title: 'Error!',
              text: '" . addslashes($error) . "',
              icon: 'error',
              confirmButtonText: 'Try Again',
              background: '#2a2f3b',
              color: '#ffffff',
              confirmButtonColor: '#4a90e2',
              showConfirmButton: true
            });
        </script>";
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
      <div class="mb-6">
        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
        <input type="password" id="password" name="password" placeholder="Create a password"
          class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
      </div>
      <div class="mb-6">
        <label for="confirm-password" class="block text-sm font-medium text-gray-300 mb-2">Confirm Password</label>
        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm your password"
          class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
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
    // Wait for the document to be fully loaded
    document.addEventListener("DOMContentLoaded", function () {
      const form = document.querySelector('#signUpForm');
      
      form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting immediately
        
        // Show SweetAlert message
        Swal.fire({
          title: 'Success!',
          text: 'A verification code has been sent to your email.',
          icon: 'success',
          confirmButtonText: 'Proceed to OTP',
          background: '#2a2f3b',
          color: '#ffffff',
          confirmButtonColor: '#4a90e2',
        }).then(function() {
          // After SweetAlert is confirmed, submit the form and redirect to otp.php
          form.submit(); // This will submit the form after SweetAlert
          window.location = '../CyberSecurity/reg_otp/otp.php'; // Redirect to OTP page
        });
      });
    });
  </script>
</body>

</html>  