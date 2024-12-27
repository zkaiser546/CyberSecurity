<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <form id="login-form" method="POST" action="users_login.php">
      <!-- Email -->
      <div class="mb-6">
        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email"
          class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
      </div>
      <!-- Password -->
      <div class="mb-6">
        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password"
          class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
      </div>
      <!-- Login Button -->
      <button type="submit"
        class="w-full bg-blue-500 text-white py-3 px-4 rounded-lg hover:bg-blue-600 transition button">
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

 <!--<script>
  document.getElementById("login-form").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent form submission

    // Get email and password values
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    // Send AJAX request to the server
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "user_login.php", true); // Change to the PHP script handling login
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    // Prepare data to send to the server
    const data = `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`;

    xhr.onload = function() {
      if (xhr.status === 200) {
        const response = JSON.parse(xhr.responseText); // Parse the JSON response

        if (response.success) {
          // SweetAlert2 Success Alert
          Swal.fire({
            icon: "success",
            title: "Login Successful",
            text: "Redirecting to the dashboard...",
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false,
          }).then(() => {
            window.location.href = response.redirectUrl; // Redirect based on server's response
          });
        } else {
          // SweetAlert2 Error Alert
          Swal.fire({
            icon: "error",
            title: "Invalid Credentials",
            text: response.message, // Show error message returned by the server
            showConfirmButton: true,
          });
        }
      } else {
        // Handle error from the server
        Swal.fire({
          icon: "error",
          title: "Server Error",
          text: "There was an error processing your request. Please try again later.",
          showConfirmButton: true,
        });
      }
    };

    // Send the request
    xhr.send(data);
  });
</script> -->

</body>
</html>
