<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OTP Verification</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="icon" href="../Logo/Feedback_Logo.png" type="image/x-icon">
  <style>
    body {
      background: linear-gradient(135deg, #1c1f26, #2b303b);
      color: white;
      font-family: 'Inter', sans-serif;
    }

    .content-card {
      background: #2a2f3b;
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.4);
    }

    .input-field {
      background-color: #2b303b;
      border: 1px solid #3d4450;
      color: white;
      padding-right: 60px;
      /* Space for timer */
      position: relative;
    }

    .timer-text {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      color: #4a90e2;
      font-size: 14px;
      font-weight: bold;
    }

    .timer-disabled {
      color: #6b7280;
    }
  </style>
</head>

<body class="flex justify-center items-center min-h-screen">
  <!-- OTP Verification Form -->
  <div class="content-card p-8 rounded-lg w-96">
    <h2 class="text-3xl font-bold text-white mb-4">Enter OTP</h2>
    <p class="text-gray-300 mb-6">We sent a one-time password (OTP) to your registered email. Please enter it below to proceed.</p>
    <form id="otp-form">
      <label for="otp" class="block text-sm font-medium text-gray-300 mb-2">OTP</label>
      <div class="relative">
        <input
          type="text"
          id="otp"
          maxlength="6"
          class="w-full px-4 py-2 rounded-md input-field"
          placeholder="Enter 6-digit OTP" />
        <span id="timer" class="timer-text">Resend</span>
      </div>
      <button
        id="submit-otp-btn"
        type="button"
        class="bg-blue-500 text-white w-full px-4 py-2 rounded-lg hover:bg-blue-600 mt-4">
        Submit
      </button>
    </form>
  </div>

  <!-- JavaScript -->
  <script>
    // Constants and element selection (keep existing code)
    const TIMER_KEY = "otpTimerEnd";
    const submitOtpBtn = document.getElementById("submit-otp-btn");
    const timerElement = document.getElementById("timer");
    const otpInput = document.getElementById("otp");
    let countdown;

    // Add this initialization code
    document.addEventListener('DOMContentLoaded', () => {
      // Check if there's an existing timer
      const remainingTime = getRemainingTime();
      if (remainingTime > 0) {
        // Start countdown with existing time
        startCountdown(10, true);
      } else {
        // No timer running, show Resend button
        timerElement.classList.remove("timer-disabled");
        timerElement.textContent = "Resend";
      }
    });

    // Input validation - only allow numbers
    otpInput.addEventListener('input', (e) => {
      e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });

    // Submit OTP handler
    submitOtpBtn.addEventListener("click", () => {
      const otpValue = otpInput.value.trim();

      if (otpValue === "") {
        Swal.fire({
          icon: "error",
          title: "Empty OTP",
          text: "Please enter the OTP!",
          background: '#2a2f3b',
          color: '#ffffff',
          confirmButtonColor: '#4a90e2',
        });
        return;
      }

      // Set button to loading state
      submitOtpBtn.disabled = true;
      submitOtpBtn.textContent = "Verifying...";

      // Send OTP validation request
      fetch("validate_otp.php", {
          method: "POST",
          body: JSON.stringify({
            otp: otpValue
          }),
          headers: {
            "Content-Type": "application/json",
          },
          credentials: 'include' // Important for session handling
        })
        .then(response => {
          if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
          }
          return response.json();
        })
        .then((data) => {
          if (data.status === "success") {
            Swal.fire({
              icon: "success",
              title: "OTP Verified",
              text: "Redirecting to the Login...",
              timer: 2000,
              timerProgressBar: true,
              showConfirmButton: false,
              background: '#2a2f3b',
              color: '#ffffff',
              confirmButtonColor: '#4a90e2',
            }).then(() => {
              localStorage.removeItem(TIMER_KEY);
              localStorage.removeItem("otpSent");
              window.location.href = "../login.php";
            });
          } else {
            throw new Error(data.message || "Invalid OTP");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire({
            icon: "error",
            title: "Verification Failed",
            text: error.message || "Failed to verify OTP. Please try again.",
            background: '#2a2f3b',
            color: '#ffffff',
            confirmButtonColor: '#4a90e2',
          });
        })
        .finally(() => {
          submitOtpBtn.disabled = false;
          submitOtpBtn.textContent = "Submit";
        });
    });
    // Update the startCountdown function
    function startCountdown(minutes, fromReload = false) {
      clearInterval(countdown); // Clear any existing countdown
      let time;

      if (fromReload) {
        time = getRemainingTime();
        if (time <= 0) {
          timerElement.classList.remove("timer-disabled");
          timerElement.textContent = "Resend";
          localStorage.removeItem(TIMER_KEY);
          return;
        }
      } else {
        time = minutes * 60;
        const endTime = Date.now() + time * 1000;
        localStorage.setItem(TIMER_KEY, endTime.toString());
      }

      timerElement.classList.add("timer-disabled");
      updateTimerDisplay(time);

      countdown = setInterval(() => {
        time--;
        if (time <= 0) {
          clearInterval(countdown);
          timerElement.classList.remove("timer-disabled");
          timerElement.textContent = "Resend";
          localStorage.removeItem(TIMER_KEY);
        } else {
          updateTimerDisplay(time);
        }
      }, 1000);
    }

    // Keep the existing updateTimerDisplay function
    function updateTimerDisplay(time) {
      const minutes = Math.floor(time / 60);
      const seconds = time % 60;
      timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, "0")}`;
    }

    // Update the getRemainingTime function to be more precise
    function getRemainingTime() {
      const endTime = localStorage.getItem(TIMER_KEY);
      if (!endTime) return 0;

      const remainingTime = Math.floor((parseInt(endTime) - Date.now()) / 1000);
      return Math.max(remainingTime, 0);
    }

    // Resend OTP handler
    timerElement.addEventListener("click", async function() {
      // Only proceed if the timer is not running
      if (!timerElement.classList.contains("timer-disabled")) {
        try {
          // Set loading state
          timerElement.textContent = "Sending...";
          timerElement.classList.add("timer-disabled");

          const formData = new FormData();
          formData.append('ajax', '1');

          // First, check if the session is active
          const response = await fetch("resend_otp.php", {
            method: "POST",
            body: formData,
            credentials: 'include'
          });

          // Get the raw text first
          const responseText = await response.text();

          // Try to parse as JSON
          let data;
          try {
            data = JSON.parse(responseText);
          } catch (e) {
            console.error('Failed to parse JSON response:', responseText);
            throw new Error('Server returned invalid response format');
          }

          if (data.success) {
            // Start new countdown
            startCountdown(10); // 10 minutes countdown

            Swal.fire({
              icon: "success",
              title: "OTP Resent",
              text: data.message,
              background: '#2a2f3b',
              color: '#ffffff',
              confirmButtonColor: '#4a90e2',
            });
          } else {
            throw new Error(data.message || "Failed to resend OTP");
          }
        } catch (error) {
          console.error("Error details:", error);

          timerElement.classList.remove("timer-disabled");
          timerElement.textContent = "Resend";

          Swal.fire({
            icon: "error",
            title: "Failed to Resend OTP",
            text: error.message || "Please try again later",
            background: '#2a2f3b',
            color: '#ffffff',
            confirmButtonColor: '#4a90e2',
          });
        }
      }
    });
  </script>
</body>

</html>