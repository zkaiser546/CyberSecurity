<?php
session_start();
include '../database/dbConnect.php';


if (!isset($_SESSION['spAd_ID'])) {
    header("Location: ../login.php");
    exit();
}

$sup_id = $_SESSION['spAd_ID'];
$sql = "SELECT username, image FROM supadmin WHERE spAd_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $sup_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $username = $user['username'];
    $image = $user['image'] ? $user['image'] : 'uploads/default_profile.jpg'; 
} else {
    $username = "Unknown User";
    $image = 'uploads/default_profile.jpg'; 
}



$sql1 = "SELECT user_id, username, email, status
        FROM users 
        ORDER BY user_id DESC";
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

                // Fetch admin data along with their access control status
$sql4 = "SELECT admin.admin_id, admin.username, accessControl.manage_user
         FROM admin 
         LEFT JOIN accessControl ON admin.admin_id = accessControl.admin_id";
         $result5 = $conn->query($sql4);

$sql5 = "SELECT user_id, email, username, manage_users 
         FROM users 
        ";
         $result6 = $conn->query($sql5);         
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Super Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/js-sha3@0.8.0/build/sha3.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link rel="icon" href="../Logo/Feedback_Logo.png" type="image/x-icon">
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

   select.form-control {
    width: 150px;
    padding: 8px;
    background-color: #2d3748;
    color: #fff;
    border: 1px solid #4a5568;
    border-radius: 4px;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

select.form-control:focus {
    background-color: #4a5568;
    border-color: #63b3ed;
    outline: none;
}
select {
    appearance: none; 
    background-color: #1f2937; 
    color: #ffffff; 
    border: 1px solid #374151; 
    border-radius: 0.375rem; 
    padding: 0.5rem 2rem 0.5rem 1rem; 
    width: 100%; 
    font-size: 1rem; 
    font-family: inherit; 
    cursor: pointer; 
    transition: all 0.3s ease; 
  }

  
  select::after {
    content: '▾'; 
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #ffffff;
  }

  
  select:focus {
    outline: none; 
    border-color: #60a5fa; 
    box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.5); 
  }

  
  option {
    background-color: #1f2937; 
    color: #ffffff; 
  }

  
  select:disabled {
    background-color: #374151; 
    cursor: not-allowed; 
    color: #9ca3af; 
  }
  #profile-dropdown-btn span {
  font-size: 0.875rem; 
  font-weight: 600;    
  color: #e5e7eb;     
  text-shadow: 0px 1px 2px rgba(0, 0, 0, 0.5); 
  letter-spacing: 0.5px; 
  text-transform: uppercase; 
}

input,
    textarea {
      background-color: #1c1f26;
      color: white;
      border: 1px solid #444;
    }

    input:focus,
    textarea:focus {
      border-color: #3b82f6;
      outline: none;
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
        <button id="access-control-btn" class="block w-full text-left px-6 py-3 text-white hover:bg-gray-700 transition">
          Access Control
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
          <img src="<?php echo htmlspecialchars($image); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover">
          <span>SUPER ADMIN</span>
        
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
 const setupProfileBtn = document.getElementById("setup-profile-btn");
  const logoutBtn = document.getElementById("logout-btn");

    document.addEventListener("DOMContentLoaded", () => {
      const contentArea = document.getElementById("content-area");

      // Profile Dropdown Toggle
      const profileDropdownBtn = document.getElementById("profile-dropdown-btn");
      const profileDropdown = document.getElementById("profile-dropdown");
      profileDropdownBtn.addEventListener("click", () => {
        profileDropdown.classList.toggle("hidden");
      });

  // Setup Profile Functionality
    setupProfileBtn.addEventListener("click", () => {
      const contentArea = document.getElementById("content-area");
      contentArea.innerHTML = `
    <div class="content-card p-8">
      <h2 class="text-3xl font-bold text-white mb-4">Setup Profile</h2>
      <form id="profile-form">
        <div class="mb-4">
          <label for="profile-pic" class="block text-sm font-medium text-gray-300">Profile Picture</label>
          <input type="file" id="profile-pic" accept="image/*" class="block w-full mt-1 px-4 py-2 border rounded-md">
        </div>
      <div class="mb-6 relative">
          <label for="old-password" class="block text-sm font-medium text-gray-300">Old Password</label>
          <div class="relative">
            <input type="password" id="old-password" class="block w-full mt-1 px-4 py-2 border rounded-md">
            <button type="button" class="absolute inset-y-0 right-2 flex items-center justify-center text-gray-400"
              onclick="togglePasswordVisibility('old-password')">
              <span><i class="fa-solid fa-eye"></i></span>
            </button>
          </div>
        </div>
        <div class="mb-6 relative">
          <label for="new-password" class="block text-sm font-medium text-gray-300">New Password</label>
          <div class="relative">
            <input type="password" id="new-password" class="block w-full mt-1 px-4 py-2 border rounded-md">
            <button type="button" class="absolute inset-y-0 right-2 flex items-center justify-center text-gray-400"
              onclick="togglePasswordVisibility('new-password')">
              <span><i class="fa-solid fa-eye"></i></span>
            </button>
          </div>
        </div>
        <div class="mb-6 relative">
          <label for="confirm-password" class="block text-sm font-medium text-gray-300">Confirm New Password</label>
          <div class="relative">
            <input type="password" id="confirm-password" class="block w-full mt-1 px-4 py-2 border rounded-md">
            <button type="button" class="absolute inset-y-0 right-2 flex items-center justify-center text-gray-400"
              onclick="togglePasswordVisibility('confirm-password')">
              <span><i class="fa-solid fa-eye"></i></span>
            </button>
          </div>
        </div>
        <button type="button" id="save-changes" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Save Changes</button>
      </form>
    </div>
  `;

      const saveChangesBtn = document.getElementById("save-changes");
      saveChangesBtn.addEventListener("click", async () => {
        try {
          const profilePic = document.getElementById("profile-pic").files[0];
          const oldPassword = document.getElementById("old-password").value;
          const newPassword = document.getElementById("new-password").value;
          const confirmPassword = document.getElementById("confirm-password").value;

          // Validate inputs
          if (!validateInputs(oldPassword, newPassword, confirmPassword)) {
            return;
          }

          Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we update your profile.',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Encrypt passwords using SHA3-512
          const hashedOldPassword = sha3_512(oldPassword);
          const hashedNewPassword = sha3_512(newPassword);

          const formData = new FormData();
          if (profilePic) {
            formData.append("profilePic", profilePic);
          }
          formData.append("oldPassword", hashedOldPassword);
          formData.append("newPassword", hashedNewPassword);

          const response = await fetch("changePass.php", {
            method: "POST",
            body: formData,
          });

          // First try to get the response as text
          const responseText = await response.text();

          let result;
          try {
            // Then parse the text as JSON
            result = JSON.parse(responseText);
          } catch (parseError) {
            console.error('JSON Parse Error:', responseText);
            throw new Error('Invalid server response format');
          }

          if (result.success) {
            await Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: result.message,
              showConfirmButton: false,
              timer: 1500
            });
            clearForm();
          } else {
            throw new Error(result.message || 'Unknown error occurred');
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error updating profile. Please try again.'
          });
        }
      });
    });

    function validateInputs(oldPassword, newPassword, confirmPassword) {
      if (!oldPassword) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Please enter your current password.'
        });
        return false;
      }

      if (!newPassword) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Please enter a new password.'
        });
        return false;
      }

      if (newPassword.length < 8) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'New password must be at least 8 characters long.'
        });
        return false;
      }

      if (newPassword !== confirmPassword) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'New passwords do not match!'
        });
        return false;
      }

      return true;
    }

    function clearForm() {
      document.getElementById("profile-pic").value = "";
      document.getElementById("old-password").value = "";
      document.getElementById("new-password").value = "";
      document.getElementById("confirm-password").value = "";
    }
    window.togglePasswordVisibility = (inputId) => {
      const input = document.getElementById(inputId);
      const button = input.nextElementSibling; 
      const icon = button.querySelector("i");

      if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    };
     


      // Logout Functionality
    logoutBtn.addEventListener("click", () => {
      window.location.href = "sa_logout.php";
    });


      // Manage Users Tab
 document.addEventListener("click", async (event) => {
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
          <tbody id="user-table-body">
            <?php
           // Decryption function
function decryptEmail($encryptedData, $key) {
    $combined = base64_decode($encryptedData);
    $iv = substr($combined, 0, 16);
    $encrypted = substr($combined, 16);
    return openssl_decrypt(
        $encrypted,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
}

// Encryption key
$encryptionKey = 'SecureFeedback250';

try {

    if ($result1->num_rows > 0) {
        while ($row = $result1->fetch_assoc()) {
            $fullName = htmlspecialchars($row['username']);
            $email = htmlspecialchars(decryptEmail($row['email'], $encryptionKey)); // Decrypt the email
            $status = htmlspecialchars($row['status']);
            $userId = htmlspecialchars($row['user_id']);
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
} catch (Exception $e) {
    echo "<tr><td colspan='5'>Error fetching users.</td></tr>";
}

            ?>
          </tbody>
        </table>
      </div>
    `;
  }
   // Handle Edit Button Click
  if (event.target.classList.contains("edit-btn")) {
    const userId = event.target.getAttribute("data-user-id");

    // Fetch user details via AJAX or Fetch API
    const response = await fetch(`getUserDetails.php?user_id=${userId}`);
    const user = await response.json();

    if (user) {
      contentArea.innerHTML = `
        <div class="content-card p-8">
          <h2 class="text-3xl font-bold text-white mb-4">Edit User</h2>
          <form id="edit-user-form">
            <input type="hidden" id="edit-user-id" value="${user.user_ID}">
            <div class="mb-4">
              <label for="edit-username" class="block text-sm font-medium text-gray-300">Full Name</label>
              <input type="text" id="edit-username" value="${user.username}" class="block w-full mt-1 px-4 py-2 border rounded-md">
            </div>
            <div class="mb-4">
              <label for="edit-email" class="block text-sm font-medium text-gray-300">Email</label>
              <input type="email" id="edit-email" value="${user.email}" class="block w-full mt-1 px-4 py-2 border rounded-md">
            </div>
            <div class="mb-4">
              <label for="edit-status" class="block text-sm font-medium text-gray-300">Status</label>
              <select id="edit-status" class="block w-full mt-1 px-4 py-2 border rounded-md">
                <option value="Active" ${user.status === "Active" ? "selected" : ""}>Active</option>
                <option value="Inactive" ${user.status === "Inactive" ? "selected" : ""}>Inactive</option>
              </select>
            </div>
            <button type="button" id="save-user-btn" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Save Changes</button>
          </form>
        </div>
      `;
    }
  }
   if (event.target.id === "save-user-btn") {
    const userId = document.getElementById("edit-user-id").value;
    const username = document.getElementById("edit-username").value;
    const status = document.getElementById("edit-status").value;

    // Send updated data to the server via Fetch API
    const response = await fetch("updateUser.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ user_ID: userId, username, status }),
    });

    const result = await response.json();

    if (result.success) {
      Swal.fire({
        icon: "success",
        title: "User updated successfully!",
        showConfirmButton: false,
        timer: 1500,
      });

      // Reload the Manage Users tab
      document.getElementById("manage-users-btn").click();
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: result.message || "Failed to update user.",
      });
    }
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
              <td><?php echo date('F');?></td>
              <td><?php echo $totalFeedbacks; ?></td>
              <td><?php echo $averageStars; ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    `;
  }
 if (event.target.id === "access-control-btn") {
    // View Access Control Tab
    contentArea.innerHTML = `
      <div class="content-card p-8">
        <h2 class="text-3xl font-bold text-white mb-4">User Access Control</h2>
        <form id="accessControlForm" method="POST" action="user_save_access_control.php">
          <table class="w-full bg-gray-900 rounded-lg">
            <thead class="bg-gray-800">
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Manage User</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
             <?php

// Decryption function

// Encryption key
$encryptionKey = 'SecureFeedback250';

try {
    // Loop through the results to display in the table
    while ($row = $result6->fetch_assoc()) {
        $decryptedEmail = htmlspecialchars(decryptEmail($row['email'], $encryptionKey)); // Decrypt the email

        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . $decryptedEmail . "</td>";
        echo "<td>
            <select name='access_control_" . htmlspecialchars($row['user_id']) . "' class='form-control'>
                <option value='Enabled' " . ($row['manage_users'] === 'Enabled' ? 'selected' : '') . ">Enabled</option>
                <option value='Disabled' " . ($row['manage_users'] === 'Disabled' ? 'selected' : '') . ">Disabled</option>
                <option value='Blocked' " . ($row['manage_users'] === 'Blocked' ? 'selected' : '') . ">Blocked</option>
            </select>
        </td>";
        echo "<td>
            <button type='submit' class='text-blue-400 save-btn' name='save_" . htmlspecialchars($row['user_id']) . "' data-user-id='" . htmlspecialchars($row['user_id']) . "'>
                Save
            </button>
        </td>";
        echo "</tr>";
    }
} catch (Exception $e) {
    echo "<tr><td colspan='5'>Error fetching users.</td></tr>";
}

             ?>
            </tbody>
          </table>
        </form>
      </div>

       <div class="content-card p-8">
        <h2 class="text-3xl font-bold text-white mb-4">Admin Access Control</h2>
        <form id="accessControlForm" method="POST" action="save_access_control.php">
          <table class="w-full bg-gray-900 rounded-lg">
            <thead class="bg-gray-800">
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Manage User</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
             <?php


                // Loop through the results to display in the table
                while ($row = $result5->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['admin_id'] . "</td>";
                    echo "<td>" . $row['username'] . "</td>";
                    echo "<td>
                        <select name='access_control_".$row['admin_id']."' class='form-control'>
                            <option value='Enabled' " . ($row['manage_user'] === 'Enabled' ? 'selected' : '') . ">Enabled</option>
                            <option value='Disabled' " . ($row['manage_user'] === 'Disabled' ? 'selected' : '') . ">Disabled</option>
                            
                        </select>
                    </td>";
                     
                    echo "<td>
                        <button type='submit' class='text-blue-400 save-btn' name='save_".$row['admin_id']."' data-user-id='".$row['admin_id']."'>
                            Save
                        </button>
                    </td>";
                    echo "</tr>";
                }
             ?>
            </tbody>
          </table>
        </form>
      </div>
    `;
}

});

    });
  </script>
</body>
</html>
