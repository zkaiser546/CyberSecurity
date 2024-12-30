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
    <form id="login-form" method="POST" action="users_login.php">
      <!-- Email -->
      <div class="mb-6">
        <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email"
          class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
      </div>
      <!-- Password -->
      <div class="mb-6 relative">
        <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
        <div class="relative">
          <input type="password" id="password" name="password" placeholder="Enter your password"
            class="w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-gray-300 focus:ring-blue-500 focus:border-blue-500 pr-10" required>
          <button type="button" class="absolute inset-y-0 right-2 flex items-center justify-center text-gray-400"
            onclick="togglePasswordVisibility('password')">
            <span id="togglePasswordIcon"><i class="fa-solid fa-eye"></i></span>
          </button>
        </div>
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
    // Add this JavaScript to handle the form submission
    document.getElementById('login-form').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);

      fetch('users_login.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            window.location.href = data.redirect;
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Login Failed',
              text: data.message
            });
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An unexpected error occurred. Please try again.'
          });
        });
    });
  </script>
</body>

</html>