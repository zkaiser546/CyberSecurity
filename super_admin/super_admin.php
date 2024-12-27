<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Super Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="Logo/Feedback_Logo.png" type="image/x-icon">
  <style>
    body {
      background: linear-gradient(135deg, #1c1f26, #2b303b);
      color: white;
      font-family: 'Inter', sans-serif;
    }

    .nav-bar {
      background: #1a1d23;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.3);
    }

    .sidebar {
      background: #1a1d23;
      box-shadow: 2px 0px 6px rgba(0, 0, 0, 0.5);
    }

    .content-card {
      background: #2a2f3b;
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.4);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 12px 16px;
      text-align: left;
      border-bottom: 1px solid #444;
    }

    th {
      background-color: #1f2733;
      font-weight: 600;
      color: white;
    }

    tr:hover td {
      background-color: #2a3443;
    }

    td {
      color: #d1d5db;
    }

    /* Modal Styles */
    .modal {
      position: fixed;
      inset: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 50;
    }

    .modal-content {
      background-color: #2a2f3b;
      border-radius: 8px;
      padding: 2rem;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    }
  </style>
</head>
<body class="flex h-screen">

  <!-- Sidebar -->
  <aside class="sidebar w-64 hidden lg:flex flex-col justify-between">
    <div class="p-6">
      <h1 class="text-3xl font-extrabold text-white uppercase tracking-wide">Super Admin</h1>
      <nav class="mt-8">
        <button id="manage-users-btn" class="block w-full text-left px-6 py-3 text-white hover:bg-gray-700 transition">
          Manage Users
        </button>
        <button id="view-logs-btn" class="block w-full text-left px-6 py-3 text-white hover:bg-gray-700 transition">
          Admin & Super Admin Logs
        </button>
        <button id="view-reports-btn" class="block w-full text-left px-6 py-3 text-white hover:bg-gray-700 transition">
          View Reports
        </button>
      </nav>
    </div>
    <footer class="text-center py-4 text-gray-400">
      <p>© 2024 Feedback System</p>
    </footer>
  </aside>

  <!-- Main Content Area -->
  <div class="flex-1 flex flex-col">
    <!-- Top Navbar -->
    <nav class="nav-bar w-full px-6 py-4 flex justify-between items-center shadow-md">
      <h1 class="text-xl font-bold tracking-wide uppercase text-white">Super Admin Dashboard</h1>
      <div class="relative">
        <button id="profile-dropdown-btn" class="flex items-center space-x-3 px-4 py-2 rounded-lg text-white hover:bg-gray-800 transition">
          <img src="https://via.placeholder.com/40" alt="Profile" class="w-10 h-10">
          <span>Super Admin</span>
        </button>
        <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-md">
          <button id="setup-profile-btn" class="block w-full px-4 py-2 text-left text-white hover:bg-gray-700">Setup Profile</button>
          <button id="logout-btn" class="block w-full px-4 py-2 text-left text-white hover:bg-gray-700">Logout</button>
        </div>
      </div>
    </nav>

    <!-- Content Area -->
    <main id="content-area" class="flex-1 p-10">
      <div class="content-card p-8 rounded-lg">
        <h2 class="text-3xl font-extrabold text-white tracking-tight">Welcome to the Super Admin Dashboard</h2>
        <p class="text-gray-300 mt-2">Use the sidebar to manage users, view logs, and generate reports.</p>
      </div>
    </main>
  </div>

  <!-- JavaScript -->
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const contentArea = document.getElementById("content-area");

      // Profile Dropdown Toggle
      const profileDropdownBtn = document.getElementById("profile-dropdown-btn");
      const profileDropdown = document.getElementById("profile-dropdown");
      profileDropdownBtn.addEventListener("click", () => {
        profileDropdown.classList.toggle("hidden");
      });

      // Setup Profile Functionality
      document.getElementById("setup-profile-btn").addEventListener("click", () => {
        contentArea.innerHTML = `
          <div class="content-card p-8">
            <h2 class="text-3xl font-bold text-white mb-4">Setup Profile</h2>
            <form id="setup-profile-form">
              <div class="mb-4">
                <label for="profile-pic" class="block text-sm font-medium text-gray-300">Profile Picture</label>
                <input type="file" id="profile-pic" class="block w-full mt-1 px-4 py-2 bg-gray-800 text-gray-300 rounded-md">
              </div>
              <div class="mb-4">
                <label for="old-password" class="block text-sm font-medium text-gray-300">Old Password</label>
                <input type="password" id="old-password" class="block w-full mt-1 px-4 py-2 bg-gray-800 text-gray-300 rounded-md">
              </div>
              <div class="mb-4">
                <label for="new-password" class="block text-sm font-medium text-gray-300">New Password</label>
                <input type="password" id="new-password" class="block w-full mt-1 px-4 py-2 bg-gray-800 text-gray-300 rounded-md">
              </div>
              <div class="mb-4">
                <label for="confirm-password" class="block text-sm font-medium text-gray-300">Confirm Password</label>
                <input type="password" id="confirm-password" class="block w-full mt-1 px-4 py-2 bg-gray-800 text-gray-300 rounded-md">
              </div>
              <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Save Changes</button>
            </form>
          </div>
        `;
      });

      // Logout Functionality
      document.getElementById("logout-btn").addEventListener("click", () => {
        window.location.href = "login.html";
      });

      // Manage Users Tab
      document.getElementById("manage-users-btn").addEventListener("click", () => {
        contentArea.innerHTML = `
          <div class="content-card p-8">
            <h2 class="text-3xl font-bold text-white mb-4">Manage Users</h2>
            <table class="w-full bg-gray-900 rounded-lg">
              <thead class="bg-gray-800">
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>1</td>
                  <td>John Doe</td>
                  <td>john@example.com</td>
                  <td>Active</td>
                  <td><button class="text-blue-400">Edit</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        `;
      });

      // Admin & Super Admin Logs Tab
      document.getElementById("view-logs-btn").addEventListener("click", () => {
        contentArea.innerHTML = `
          <div class="content-card p-8">
            <h2 class="text-3xl font-bold text-white mb-4">Admin & Super Admin Logs</h2>
            <table class="w-full bg-gray-900 rounded-lg">
              <thead class="bg-gray-800">
                <tr>
                  <th>Date</th>
                  <th>User</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>2024-11-18</td>
                  <td>Admin John</td>
                  <td>Updated Feedback</td>
                </tr>
              </tbody>
            </table>
          </div>
        `;
      });

      // View Reports Tab
      document.getElementById("view-reports-btn").addEventListener("click", () => {
        contentArea.innerHTML = `
          <div class="content-card p-8">
            <h2 class="text-3xl font-bold text-white mb-4">Reports</h2>
            <table class="w-full bg-gray-900 rounded-lg">
              <thead class="bg-gray-800">
                <tr>
                  <th>Month</th>
                  <th>Feedbacks</th>
                  <th>Average Stars</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>February</td>
                  <td>50</td>
                  <td>4.5</td>
                </tr>
              </tbody>
            </table>
          </div>
        `;
      });
    });
  </script>
</body>
</html>
