<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
    <form action="authenticate_user.php" method="POST">
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

</body>
</html>
