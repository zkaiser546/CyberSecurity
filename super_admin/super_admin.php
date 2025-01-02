<?php
session_start();

include '../database/dbConnect.php';
$sql = "SELECT SpAd_ID, Email, Password, Image, Status, Role FROM SupAdmin ";
$result = $conn->query($sql);



$sql1 = "SELECT user_ID, username, email, status
        FROM users 
        ORDER BY user_ID DESC";
        $result1 = $conn->query($sql1);
     
     
$sql2 = "SELECT 
     username,   
    l.action, 
    l.timestamp
FROM admin_logs l
JOIN admin a ON l.admin_id = a.admin_id
ORDER BY l.timestamp DESC;";
      $result2 = $conn->query($sql2);
  
$currentMonth = date('m'); // Month (01-12)
$currentYear = date('Y'); // Year

// Query to calculate the total feedbacks and average stars for the current month
$sql3 = "SELECT 
            COUNT(*) AS total_feedbacks, 
            ROUND(AVG(stars), 2) AS average_stars 
        FROM feedback 
        WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?";

$stmt = $conn->prepare($sql3);
$stmt->bind_param('ii', $currentMonth, $currentYear);
$stmt->execute();
$result4 = $stmt->get_result();

// Fetch the data
$data = $result4->fetch_assoc();
$totalFeedbacks = $data['total_feedbacks'] ?? 0;
$averageStars = $data['average_stars'] ?? 0;

?>
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
  <!-- Edit User Modal -->

<!-- Edit User Modal -->



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
        window.location.href = "../login.php";
      });


      


      // Manage Users Tab
    document.addEventListener("click", (event) => {
  if (event.target.id === "manage-users-btn") {
    // Manage Users Tab
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
            <?php
            if ($result1->num_rows > 0) {
              while ($row = $result1->fetch_assoc()) {
                $fullName = htmlspecialchars($row['username']);
                $email = htmlspecialchars($row['email']);
                $status = htmlspecialchars($row['status']);
                $userId = htmlspecialchars($row['user_ID']);
                echo "
                  <tr>
                    <td>{$userId}</td>
                    <td>{$fullName}</td>
                    <td>{$email}</td>
                    <td>{$status}</td>
                    <td><button class='text-blue-400 edit-btn' data-user-id='{$userId}'>Edit</button></td>
                  </tr>
                ";
              }
            } else {
              echo "<tr><td colspan='5'>No users found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    `;
  }

  if (event.target.id === "view-logs-btn") {
    // View Logs Tab
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
            <?php
            if ($result2->num_rows > 0) {
              while ($row = $result2->fetch_assoc()) {
                $name = htmlspecialchars($row['username']);
                $action = htmlspecialchars($row['action']);
                $time = htmlspecialchars($row['timestamp']);
                echo "
                  <tr>
                    <td>{$time}</td>
                    <td>{$name}</td>
                    <td>{$action}</td>
                  </tr>
                ";
              }
            } else {
              echo "<tr><td colspan='3'>No logs found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    `;
  }

  if (event.target.id === "view-reports-btn") {
    // View Reports Tab
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
              <td><?php echo date('F'); // Current month in full (e.g., "December") ?></td>
              <td><?php echo $totalFeedbacks; ?></td>
              <td><?php echo $averageStars; ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    `;
  }
});

//edit user
/*document.addEventListener("DOMContentLoaded", () => {
  const editModal = document.getElementById("edit-modal");
  const cancelEdit = document.getElementById("cancel-edit");

  // Open the modal when an Edit button is clicked (already handled in your script)
  document.addEventListener("click", (event) => {
    if (event.target.classList.contains("edit-btn")) {
      const userId = event.target.getAttribute("data-user-id");

      // Fetch the user's data (AJAX or fetch API)
      fetch(`getUserDetails.php?user_ID=${userId}`)
        .then((response) => response.json())
        .then((data) => {
          document.getElementById("edit-user-id").value = data.user_ID;
          document.getElementById("edit-firstname").value = data.firstname;
          document.getElementById("edit-lastname").value = data.lastname;
          document.getElementById("edit-email").value = data.email;
          document.getElementById("edit-status").value = data.status;

          // Show the modal
          editModal.classList.remove("hidden");
        })
        .catch((error) => console.error("Error fetching user details:", error));
    }
  });

  // Close the modal when the Cancel button is clicked
  cancelEdit.addEventListener("click", () => {
    editModal.classList.add("hidden");
  });

  // Handle the form submission
  document.getElementById("edit-user-form").addEventListener("submit", (e) => {
    e.preventDefault();

    // Collect form data
    const formData = new FormData(e.target);

    // Send updated data to the server
    fetch("updateUser.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("User updated successfully!");
          editModal.classList.add("hidden");
          // Optionally, refresh the user table or update it dynamically
        } else {
          alert("Error updating user: " + data.message);
        }
      })
      .catch((error) => console.error("Error updating user:", error));
  });
});*/


     

    });
  </script>
</body>
</html>
