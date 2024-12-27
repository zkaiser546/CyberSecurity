<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OTP Verification</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
      padding-right: 60px; /* Space for timer */
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
          placeholder="Enter 6-digit OTP"
        />
        <span id="timer" class="timer-text">Resend</span>
      </div>
      <button
        id="submit-otp-btn"
        type="button"
        class="bg-blue-500 text-white w-full px-4 py-2 rounded-lg hover:bg-blue-600 mt-4"
      >
        Submit
      </button>
    </form>
  </div>

  <!-- JavaScript -->
  <script>
    const submitOtpBtn = document.getElementById("submit-otp-btn");
    const timerElement = document.getElementById("timer");
    const otpInput = document.getElementById("otp");

    let countdown;
    const TIMER_KEY = "otp_timer"; // LocalStorage key for timer

    // Initialize Timer on Page Load
    window.onload = () => {
      const remainingTime = getRemainingTime();
      if (remainingTime > 0) {
        startCountdown(Math.ceil(remainingTime / 60), true);
      } else {
        sendOtp(); // Send OTP if not yet generated
      }
    };

    // Mock OTP Validation Logic
    submitOtpBtn.addEventListener("click", () => {
      const otpValue = otpInput.value.trim();

      if (otpValue === "") {
        Swal.fire({
          icon: "error",
          title: "Empty OTP",
          text: "Please enter the OTP!",
        });
        return;
      }

      // Send OTP validation request to the backend
      fetch("validate_otp.php", {
        method: "POST",
        body: JSON.stringify({ otp: otpValue }),
        headers: {
          "Content-Type": "application/json",
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            Swal.fire({
              icon: "success",
              title: "OTP Verified",
              text: "Redirecting to the dashboard...",
              timer: 2000,
              timerProgressBar: true,
              showConfirmButton: false,
            }).then(() => {
              localStorage.removeItem(TIMER_KEY); // Clear timer on success
              window.location.href = "admin_dashboard.html"; // Redirect to dashboard
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Invalid OTP",
              text: "Please try again!",
              showConfirmButton: true,
            });
          }
        })
        .catch((error) => console.error("Error validating OTP:", error));
    });

    // Resend OTP and Start Timer
    timerElement.addEventListener("click", () => {
      if (timerElement.classList.contains("timer-disabled")) return;

      sendOtp(); // Resend OTP

      startCountdown(5); // Start 5-minute countdown
    });

    // Function to send OTP
    function sendOtp() {
      fetch("send_otp.php", {
        method: "POST",
        body: JSON.stringify({ email: "user@example.com" }), // Replace with actual email
        headers: {
          "Content-Type": "application/json",
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            Swal.fire({
              icon: "info",
              title: "OTP Resent",
              text: "A new OTP has been sent to your email!",
              timer: 2000,
              timerProgressBar: true,
              showConfirmButton: false,
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Error Resending OTP",
              text: "Please try again later.",
            });
          }
        })
        .catch((error) => console.error("Error sending OTP:", error));
    }

    // Countdown Timer
    function startCountdown(minutes, fromReload = false) {
      let time = minutes * 60;

      // If from reload, retrieve remaining time
      if (fromReload) {
        time = getRemainingTime();
      } else {
        // Store the end time in localStorage
        const endTime = Date.now() + time * 1000;
        localStorage.setItem(TIMER_KEY, endTime);
      }

      timerElement.classList.add("timer-disabled");
      timerElement.textContent = formatTime(time);

      countdown = setInterval(() => {
        time--;
        if (time <= 0) {
          clearInterval(countdown);
          timerElement.classList.remove("timer-disabled");
          timerElement.textContent = "Resend";
          localStorage.removeItem(TIMER_KEY); // Clear timer when done
        } else {
          timerElement.textContent = formatTime(time);
        }
      }, 1000);
    }

    // Format Time for Countdown
    function formatTime(seconds) {
      const minutes = Math.floor(seconds / 60);
      const secs = seconds % 60;
      return `${minutes}:${secs.toString().padStart(2, "0")}`;
    }

    // Get Remaining Time from LocalStorage
    function getRemainingTime() {
      const endTime = localStorage.getItem(TIMER_KEY);
      if (!endTime) return 0;
      const remainingTime = Math.floor((endTime - Date.now()) / 1000);
      return Math.max(remainingTime, 0); // Return 0 if time is negative
    }
  </script>
</body>
</html>
