<?php
session_start();
include '../database/dbConnect.php';
if (!isset($_SESSION['admin_ID'])) {
  header("Location: ../login.php");
  exit();
}

$adminId = $_SESSION['admin_ID'];

$manageUserEnabled = false; // Default to false
if ($adminId) {
  $sql = "SELECT manage_user FROM accessControl WHERE admin_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $adminId);
  $stmt->execute();
  $stmt->bind_result($manageUserStatus);
  $stmt->fetch();
  $stmt->close();

  // Check if 'manage_user' is 'Enabled'
  if ($manageUserStatus === 'Enabled') {
      $manageUserEnabled = true;
  }
}
$sql1 = "SELECT user_ID, username, email, status
        FROM users 
        ORDER BY user_ID DESC";
        $result1 = $conn->query($sql1);

$sql2 = "SELECT username, image FROM admin WHERE admin_id = ?";
$stmt = $conn->prepare($sql2);
$stmt->bind_param("s", $adminId);
$stmt->execute();
$result2 = $stmt->get_result();

if ($result2->num_rows > 0) {
    $user = $result2->fetch_assoc();
    $username = $user['username'];
    $image = $user['image'] ? $user['image'] : 'uploads/default_profile.jpg'; 
} else {
    $username = "Unknown User";
    $image = 'uploads/default_profile.jpg'; 
}

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
<script src="https://cdn.jsdelivr.net/npm/js-sha3@0.8.0/build/sha3.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    .modal {
      position: fixed;
      inset: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 50;
    }

    .modal.hidden {
      display: none;
    }

    .modal-content {
      background-color: #2a2f3b;
      border-radius: 8px;
      padding: 2rem;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
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
      <h1 class="text-3xl font-extrabold text-white uppercase tracking-wide">Admin Panel</h1>
      <nav class="mt-8">
        <button id="view-feedback-btn" class="block w-full text-left px-6 py-3 text-white hover:bg-gray-700 transition">
          View Feedback
        </button>
        <button id="view-reports-btn" class="block w-full text-left px-6 py-3 text-white hover:bg-gray-700 transition">
          View Reports
        </button>
        <?php if ($manageUserEnabled): ?>
        <button id="manage-users-btn" class="block w-full text-left px-6 py-3 text-white hover:bg-gray-700 transition">
            Manage Users
        </button>
    <?php endif; ?>
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
      <h1 class="text-xl font-bold tracking-wide uppercase text-white">Admin Dashboard</h1>
      <div class="relative">
      <button id="profile-dropdown-btn" class="flex items-center space-x-3 px-4 py-2 rounded-lg text-white hover:bg-gray-800 transition">
          <img src="<?php echo htmlspecialchars($image); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover">
          <span>ADMIN</span>
        
        </button>
        <!-- Dropdown -->
        <div id="profile-dropdown" class="profile-dropdown hidden absolute top-14 right-0 w-48 bg-gray-800 rounded-lg shadow-md">
          <button id="setup-profile-btn" class="block w-full px-4 py-2 text-left text-white hover:bg-gray-700">Setup Profile</button>
          <button id="logout-btn" class="block w-full px-4 py-2 text-left text-white hover:bg-gray-700">Logout</button>
        </div>
      </div>
    </nav>

    <!-- Content Area -->
    <main id="content-area" class="flex-1 p-10">
      <div class="content-card p-8 rounded-lg">
        <h2 class="text-3xl font-extrabold text-white tracking-tight">Welcome to the Admin Dashboard</h2>
        <p class="text-gray-300 mt-2">Use the sidebar to view feedback or generate reports.</p>
      </div>
    </main>
  </div>

  <!-- Reply Modal -->
  <div id="reply-modal" class="modal hidden">
    <div class="modal-content">
      <h2 class="text-xl font-bold text-white mb-4">Reply to Feedback</h2>
      <p id="feedback-user" class="text-gray-400 mb-4"></p>
      <textarea id="reply-message" rows="4" class="w-full px-4 py-2 rounded-md bg-gray-800 text-gray-300"></textarea>
      <div class="flex justify-end mt-4">
        <button id="close-reply-modal" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 mr-2">Cancel</button>
        <button id="send-reply" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Send Reply</button>
      </div>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    const contentArea = document.getElementById("content-area");
    const replyModal = document.getElementById("reply-modal");
    const closeReplyModal = document.getElementById("close-reply-modal");
    const sendReplyBtn = document.getElementById("send-reply");
    const profileDropdownBtn = document.getElementById("profile-dropdown-btn");
    const profileDropdown = document.getElementById("profile-dropdown");
    const viewFeedback = document.getElementById("view-feedback-btn");
    const logoutBtn = document.getElementById("logout-btn");
    const setupProfileBtn = document.getElementById("setup-profile-btn");


    // Close Modal
    closeReplyModal.addEventListener("click", () => {
      replyModal.classList.add("hidden");
    });

    // Profile Dropdown Toggle
    profileDropdownBtn.addEventListener("click", () => {
      profileDropdown.classList.toggle("hidden");
    });


    const decryptFeedback = (encryptedData, key) => {
      const iv = CryptoJS.enc.Hex.parse(encryptedData.substr(0, 32));
      const encrypted = encryptedData.substr(32);
      return CryptoJS.AES.decrypt(encrypted, key, {
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.Pkcs7
      }).toString(CryptoJS.enc.Utf8);
    };

    // Modified View Feedback button event listener
    document.getElementById("view-feedback-btn").addEventListener("click", async () => {
      try {
        const response = await fetch('retrieve_all_feedback.php');
        const feedbackData = await response.json();
        const encryptionKey = 'SecureFeedback250';

        let tableRows = feedbackData.map(item => {
          const decryptedText = decryptFeedback(item.feedback_text, encryptionKey);
          const stars = '★'.repeat(parseInt(item.stars)) + '☆'.repeat(5 - parseInt(item.stars));

          const replyButton = item.has_reply ?
            `<button class="text-gray-400 cursor-not-allowed" disabled>Replied</button>` :
            `<button class="text-blue-400 hover:underline reply-btn" 
          data-feedback-id="${item.feedback_dD}"
          data-user="${item.display_name}" 
          data-comment="${decryptedText}">Reply</button>`;

          return `
        <tr>
          <td class="px-6 py-4">${item.feedback_dD}</td>
          <td class="px-6 py-4">${item.display_name}</td>
          <td class="px-6 py-4 text-yellow-400">${stars}</td>
          <td class="px-6 py-4">${decryptedText}</td>
          <td class="px-6 py-4">${replyButton}</td>
        </tr>
      `;
        }).join('');

        contentArea.innerHTML = `
      <div class="content-card p-8">
        <h2 class="text-3xl font-bold text-white mb-4">Feedback</h2>
        <div class="mt-6 bg-gray-900 rounded-lg overflow-hidden">
          <table class="w-full text-left">
            <thead class="bg-gray-800">
              <tr>
                <th class="px-6 py-3 text-sm font-medium text-gray-400">Feedback ID</th>
                <th class="px-6 py-3 text-sm font-medium text-gray-400">User</th>
                <th class="px-6 py-3 text-sm font-medium text-gray-400">Rating</th>
                <th class="px-6 py-3 text-sm font-medium text-gray-400">Comment</th>
                <th class="px-6 py-3 text-sm font-medium text-gray-400">Action</th>
              </tr>
            </thead>
            <tbody>
              ${tableRows}
            </tbody>
          </table>
        </div>
      </div>
    `;

        // Add event listeners for reply buttons
        document.querySelectorAll(".reply-btn").forEach((btn) => {
          btn.addEventListener("click", (e) => {
            const user = e.target.getAttribute("data-user");
            const comment = e.target.getAttribute("data-comment");
            const feedbackId = e.target.getAttribute("data-feedback-id");

            document.getElementById("feedback-user").innerText = `Replying to ${user}: "${comment}"`;
            document.getElementById("reply-message").setAttribute("data-feedback-id", feedbackId);
            document.getElementById("reply-modal").classList.remove("hidden");
            document.getElementById("reply-message").focus();
          });
        });
      } catch (error) {
        console.error('Error fetching feedback:', error);
        contentArea.innerHTML = `
      <div class="content-card p-8">
        <h2 class="text-3xl font-bold text-white mb-4">Error</h2>
        <p class="text-red-500">Failed to load feedback data.</p>
      </div>
    `;
      }

      const encryptReply = (reply, key) => {
        const iv = CryptoJS.lib.WordArray.random(16);
        const encrypted = CryptoJS.AES.encrypt(reply, key, {
          iv: iv,
          mode: CryptoJS.mode.CBC,
          padding: CryptoJS.pad.Pkcs7
        });
        return iv.toString() + encrypted.toString();
      };

      document.getElementById('send-reply').addEventListener('click', async () => {
        const replyText = document.getElementById('reply-message').value;
        const userInfo = document.getElementById('feedback-user').innerText;
        const username = userInfo.split(':')[0].replace('Replying to ', '').trim();
        const feedbackId = document.getElementById('reply-message').getAttribute('data-feedback-id');

        if (!replyText) {
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please write a reply message'
          });
          return;
        }

        const encryptionKey = 'SecureFeedback250';
        const encryptedReply = encryptReply(replyText, encryptionKey);

        const formData = new FormData();
        formData.append('reply', encryptedReply);
        formData.append('username', username);
        formData.append('feedback_id', feedbackId);

        try {
          const response = await fetch('submit_reply.php', {
            method: 'POST',
            body: formData
          });

          const result = await response.json();

          if (result.success) {
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Reply sent successfully!',
              showConfirmButton: false,
              timer: 1500
            }).then(() => {
              document.getElementById('reply-message').value = '';
              document.getElementById('reply-modal').classList.add('hidden');
              // Refresh the feedback list to update reply status
              document.getElementById('view-feedback-btn').click();
            });
          } else {
            throw new Error(result.message);
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error sending reply. Please try again.'
          });
        }
      });

      // Modify the reply button click handler in your view feedback code
      document.querySelectorAll(".reply-btn").forEach((btn) => {
        btn.addEventListener("click", (e) => {
          const user = e.target.getAttribute("data-user");
          const comment = e.target.getAttribute("data-comment");
          document.getElementById("feedback-user").innerText = `Replying to ${user}: "${comment}"`;
          document.getElementById("reply-modal").classList.remove("hidden");
          document.getElementById("reply-message").focus();
        });
      });
    });



   // View Reports Section
document.getElementById("view-reports-btn").addEventListener("click", () => {
  contentArea.innerHTML = `
    <div class="content-card p-8">
      <h2 class="text-3xl font-bold text-white mb-4">Reports</h2>
      <div class="overflow-auto">
        <table class="w-full bg-gray-900 rounded-lg border-collapse table-auto">
          <thead class="bg-gray-800">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-400">Month</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-400">Feedbacks</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-400">Average Stars</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="px-6 py-4"><?php echo date('F') ?></td>
              <td class="px-6 py-4"><?php echo $totalFeedbacks;?></td>
              <td class="px-6 py-4"><?php echo $averageStars;?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  `;
});


    // Setup Profile Section
 
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

          const response = await fetch("changePassAdmin.php", {
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
    

    // Logout Button
    logoutBtn.addEventListener("click", () => {
      window.location.href = "Ad_logout.php";
    }
  
  );

      // Manage Users Tab
      document.addEventListener("click", async (event) => {
  if (event.target.id === "manage-users-btn") {
    // Load users table structure first
    const contentArea = document.getElementById('content-area'); // Make sure this element exists
    if (!contentArea) {
      console.error('Content area element not found');
      return;
    }

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
            <!-- Data will be loaded here -->
          </tbody>
        </table>
      </div>
    `;

    // Fetch and populate users data
    try {
      const response = await fetch('get_users.php');
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const users = await response.json();
      
      const tableBody = document.getElementById('user-table-body');
      if (!tableBody) {
        console.error('Table body element not found');
        return;
      }

      if (users.length > 0) {
        tableBody.innerHTML = users.map(user => `
          <tr>
            <td>${user.user_id}</td>
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td>${user.status}</td>
            <td><button class='text-blue-400 edit-btn' data-user-id='${user.user_id}'>Edit</button></td>
          </tr>
        `).join('');
      } else {
        tableBody.innerHTML = "<tr><td colspan='5'>No users found.</td></tr>";
      }
    } catch (error) {
      console.error('Error loading users:', error);
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Failed to load users. Please try again.",
        });
      } else {
        alert("Failed to load users. Please try again.");
      }
    }
  }

  // Handle Edit Button Click
  if (event.target.classList.contains("edit-btn")) {
    const userId = event.target.getAttribute("data-user-id");
    const contentArea = document.getElementById('content-area');
    
    if (!userId || !contentArea) {
      console.error('Missing required elements');
      return;
    }

    try {
      const response = await fetch(`getUserDetailsAdmin.php?user_ID=${userId}`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const user = await response.json();

      if (user && !user.error) {
        contentArea.innerHTML = `
          <div class="content-card p-8">
            <h2 class="text-3xl font-bold text-white mb-4">Edit Username</h2>
            <form id="edit-user-form">
              <input type="hidden" id="edit-user-id" value="${user.user_id}">
              <div class="mb-4">
                <label for="edit-username" class="block text-sm font-medium text-gray-300">Full Name</label>
                <input type="text" id="edit-username" value="${user.username}" class="block w-full mt-1 px-4 py-2 border rounded-md">
              </div>
              <div class="mb-4">
                 <p class="text-lg font-bold text-gray-300">Email: ${user.email}</p>
                 <p class="text-lg font-bold text-gray-300">Status: ${user.status}</p>
              </div>
              <button type="button" id="save-user-btn" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Save Username</button>
            </form>
          </div>
        `;
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: user.error || "Failed to load user details.",
          });
        } else {
          alert(user.error || "Failed to load user details.");
        }
      }
    } catch (error) {
      console.error('Error fetching user details:', error);
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Failed to load user details. Please try again.",
        });
      } else {
        alert("Failed to load user details. Please try again.");
      }
    }
  }

  if (event.target.id === "save-user-btn") {
    const userId = document.getElementById("edit-user-id")?.value;
    const username = document.getElementById("edit-username")?.value;

    if (!userId || !username) {
      console.error('Missing required fields');
      return;
    }

    try {
      const response = await fetch("updateUserAdmin.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ 
          user_ID: userId, 
          username
        }),
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        if (typeof Swal !== 'undefined') {
          await Swal.fire({
            icon: "success",
            title: "Success!",
            text: result.message,
            showConfirmButton: false,
            timer: 1500,
          });
        } else {
          alert("Username updated successfully!");
        }

        // Reload the Manage Users tab
        const manageUsersBtn = document.getElementById("manage-users-btn");
        if (manageUsersBtn) {
          manageUsersBtn.click();
        }
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: result.message || "Failed to update username.",
          });
        } else {
          alert(result.message || "Failed to update username.");
        }
      }
    } catch (error) {
      console.error('Error updating username:', error);
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "An unexpected error occurred. Please try again.",
        });
      } else {
        alert("An unexpected error occurred. Please try again.");
      }
    }
  }
});
  
  </script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const contentArea = document.getElementById("content-area");
    
      
    });
  </script>
</body>

</html>