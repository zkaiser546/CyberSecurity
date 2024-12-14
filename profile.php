<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="Logo/Feedback_Logo.png" type="image/x-icon">
</head>
<body class="bg-gray-100 p-6">
  <h1 class="text-2xl font-bold text-gray-800 mb-4">Setup Profile</h1>
  <form class="bg-white p-6 rounded shadow">
    <div class="mb-4">
      <label for="profile-pic" class="block text-sm font-medium text-gray-700">Profile Picture</label>
      <input type="file" id="profile-pic" class="block w-full mt-1 px-4 py-2 border rounded">
    </div>
    <div class="mb-4">
      <label for="old-password" class="block text-sm font-medium text-gray-700">Old Password</label>
      <input type="password" id="old-password" class="block w-full mt-1 px-4 py-2 border rounded">
    </div>
    <div class="mb-4">
      <label for="new-password" class="block text-sm font-medium text-gray-700">New Password</label>
      <input type="password" id="new-password" class="block w-full mt-1 px-4 py-2 border rounded">
    </div>
    <div class="mb-4">
      <label for="confirm-password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
      <input type="password" id="confirm-password" class="block w-full mt-1 px-4 py-2 border rounded">
    </div>
    <button class="bg-blue-500 text-white px-4 py-2 rounded">Save Changes</button>
  </form>
</body>
</html>
