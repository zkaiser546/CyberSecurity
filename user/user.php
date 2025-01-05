<?php
session_start();
include '../database/dbConnect.php';
if (!isset($_SESSION['user_ID'])) {
  header("Location: ../login.php");
  exit();
}

// Fetch user details
$user_id = $_SESSION['user_ID'];
$sql = "SELECT username, image FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feedback System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/js-sha3/0.8.0/sha3.min.js"></script>
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

    .star-rating {
      display: flex;
      gap: 0.5rem;
      cursor: pointer;
    }

    .star {
      font-size: 2.5rem;
      color: #444;
      transition: color 0.2s ease;
    }

    .star.hovered,
    .star.selected {
      color: #fbbf24;
      /* Yellow for hovered and selected stars */
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

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h2 {
      font-size: 1.5rem;
      color: white;
    }

    .close-btn {
      cursor: pointer;
      font-size: 1.5rem;
      color: white;
      border: none;
      background: none;
    }
  </style>
</head>

<body class="flex h-screen">

  <!-- Sidebar -->
  <aside class="sidebar w-64 hidden lg:flex flex-col justify-between">
    <div class="p-6">
      <h1 class="text-3xl font-extrabold text-white uppercase tracking-wide">Feedback</h1>
      <nav class="mt-8">
        <button id="feedback-btn" class="block w-full text-left px-6 py-3 text-white hover:bg-gray-700 transition">
          Create Feedback
        </button>
        <button id="history-btn" class="block w-full text-left px-6 py-3 text-white hover:bg-gray-700 transition">
          History
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
      <h1 class="text-xl font-bold tracking-wide uppercase text-white">Dashboard</h1>
      <div class="relative">
        <button id="profile-dropdown-btn" class="flex items-center space-x-3 px-4 py-2 rounded-lg text-white hover:bg-gray-800 transition">
          <img src="<?php echo htmlspecialchars($image); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover">
          <span><?php echo htmlspecialchars($username); ?></span>
        </button>
        <!-- Dropdown -->
        <div id="profile-dropdown" class="profile-dropdown hidden absolute top-14 right-0 w-48 bg-gray-800 rounded-lg shadow-md">
          <button id="setup-profile-btn" class="block w-full px-4 py-2 text-left hover:bg-gray-700 text-white">Setup Profile</button>
          <button id="logout-btn" class="block w-full px-4 py-2 text-left hover:bg-gray-700 text-white">Logout</button>
        </div>
      </div>
    </nav>

    <!-- Content Area -->
    <main id="content-area" class="flex-1 p-10">
      <div class="content-card p-8 rounded-lg">
        <h2 class="text-3xl font-extrabold text-white tracking-tight">Welcome to the <span class="highlight">Feedback System</span></h2>
        <p class="text-gray-300 mt-2">Use the sidebar to navigate between features.</p>
      </div>
    </main>
  </div>

  <!-- Modal -->
  <div id="modal" class="modal hidden">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Feedback Reply</h2>
        <button id="close-modal" class="close-btn">&times;</button>
      </div>
      <div id="modal-body" class="mt-4">
        <!-- Reply Content Will Be Loaded Here -->
      </div>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    const feedbackBtn = document.getElementById("feedback-btn");
    const historyBtn = document.getElementById("history-btn");
    const contentArea = document.getElementById("content-area");
    const profileDropdownBtn = document.getElementById("profile-dropdown-btn");
    const profileDropdown = document.getElementById("profile-dropdown");
    const setupProfileBtn = document.getElementById("setup-profile-btn");
    const logoutBtn = document.getElementById("logout-btn");
    const modal = document.getElementById("modal");
    const modalBody = document.getElementById("modal-body");
    const closeModal = document.getElementById("close-modal");
    const submitBtn = document.getElementById('submit-feedback');

    closeModal.addEventListener("click", () => {
      modal.classList.add("hidden");
    });

    profileDropdownBtn.addEventListener("click", () => {
      profileDropdown.classList.toggle("hidden");
    });

    feedbackBtn.addEventListener("click", () => {
      contentArea.innerHTML = `
            <div class="content-card p-8">
                <h2 class="text-3xl font-bold text-white mb-4">Create Feedback</h2>
                <form id="feedbackForm">
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-300 mb-2">Rate your experience</p>
                        <div id="star-rating" class="star-rating">
                            <span class="star" data-value="1">★</span>
                            <span class="star" data-value="2">★</span>
                            <span class="star" data-value="3">★</span>
                            <span class="star" data-value="4">★</span>
                            <span class="star" data-value="5">★</span>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label for="feedback" class="block text-sm font-medium text-gray-300">Your Feedback</label>
                        <textarea id="feedback" rows="4" class="w-full px-4 py-3 border rounded-md focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="mb-6">
                        <div class="bg-gray-800 text-gray-300 p-4 rounded-lg mb-4">
                            <p><strong>Note:</strong> Your name will be visible to the admin. If you prefer to remain anonymous, please check the box below.</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="anonymous" class="mr-2 w-4 h-4">
                            <label for="anonymous" class="text-gray-300">Submit as anonymous</label>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600">Submit Feedback</button>
                </form>
            </div>
        `;

      // Star rating functionality
      const stars = document.querySelectorAll(".star");
      let selectedRating = 0;

      const resetStars = () => stars.forEach(star => star.classList.remove("hovered", "selected"));
      const highlightStars = rating => {
        stars.forEach(star => {
          if (star.dataset.value <= rating) star.classList.add("hovered");
        });
      };

      stars.forEach(star => {
        star.addEventListener("mouseover", () => {
          resetStars();
          highlightStars(star.dataset.value);
        });

        star.addEventListener("mouseout", () => {
          resetStars();
          if (selectedRating > 0) highlightStars(selectedRating);
        });

        star.addEventListener("click", () => {
          selectedRating = star.dataset.value;
          resetStars();
          highlightStars(selectedRating);
        });
      });

      // Form submission
      const encryptFeedback = (feedback, key) => {
        const iv = CryptoJS.lib.WordArray.random(16);
        const encrypted = CryptoJS.AES.encrypt(feedback, key, {
          iv: iv,
          mode: CryptoJS.mode.CBC,
          padding: CryptoJS.pad.Pkcs7
        });

        // Combine IV and encrypted data
        return iv.toString() + encrypted.toString();
      };

      // Modified form submission
      document.getElementById('feedbackForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const feedback = document.getElementById('feedback').value;
        const anonymous = document.getElementById('anonymous').checked;

        if (!feedback || !selectedRating) {
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please provide feedback and rating'
          });
          return;
        }

        // Use a secure key (in production, this should be fetched from server)
        const encryptionKey = 'SecureFeedback250';
        const encryptedFeedback = encryptFeedback(feedback, encryptionKey);

        const formData = new FormData();
        formData.append('feedback', encryptedFeedback);
        formData.append('rating', selectedRating);
        formData.append('anonymous', anonymous);

        try {
          const response = await fetch('feedback_submit.php', {
            method: 'POST',
            body: formData
          });

          const result = await response.json();

          if (result.success) {
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: result.message,
              showConfirmButton: false,
              timer: 1500
            }).then(() => {
              document.getElementById('feedback').value = '';
              document.getElementById('anonymous').checked = false;
              resetStars();
              selectedRating = 0;
            });
          } else {
            throw new Error(result.message);
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error submitting feedback. Please try again.'
          });
        }
      });
    });
    // Decryption function
    const decryptFeedback = (encryptedData, key) => {
      if (!encryptedData) return '';
      try {
        const iv = CryptoJS.enc.Hex.parse(encryptedData.substr(0, 32));
        const encrypted = encryptedData.substr(32);
        return CryptoJS.AES.decrypt(encrypted, key, {
          iv: iv,
          mode: CryptoJS.mode.CBC,
          padding: CryptoJS.pad.Pkcs7
        }).toString(CryptoJS.enc.Utf8);
      } catch (error) {
        console.error('Decryption error:', error);
        return 'Error decrypting message';
      }
    };

    historyBtn.addEventListener("click", async () => {
      try {
        const response = await fetch('retrieve_history.php');
        const feedbackData = await response.json();
        const encryptionKey = 'SecureFeedback250';

        let tableRows = feedbackData.map(item => {
          const decryptedFeedback = decryptFeedback(item.feedback_text, encryptionKey);
          const decryptedReply = item.reply_text ? decryptFeedback(item.reply_text, encryptionKey) : 'No reply yet';
          const stars = '★'.repeat(parseInt(item.stars));
          const date = new Date(item.created_at).toLocaleDateString();
          const replyDate = item.reply_date ? new Date(item.reply_date).toLocaleDateString() : '';

          return `
        <tr>
          <td class="px-6 py-4">${date}</td>
          <td class="px-6 py-4">${item.display_name}</td>
          <td class="px-6 py-4">${decryptedFeedback}</td>
          <td class="px-6 py-4 text-yellow-400">${stars}</td>
          <td class="px-6 py-4">
            <button class="text-blue-400 hover:underline view-reply-btn" 
                    data-reply="${decryptedReply}"
                    data-reply-date="${replyDate}">
              ${item.reply_text ? 'View Reply' : 'No Reply Yet'}
            </button>
          </td>
        </tr>
      `;
        }).join('');

        contentArea.innerHTML = `
      <div class="content-card p-8">
        <h2 class="text-3xl font-bold text-white mb-4">Feedback History</h2>
        <table class="w-full text-left border-collapse bg-gray-900 rounded-lg">
          <thead class="bg-gray-800">
            <tr>
              <th class="px-6 py-3 text-sm font-medium text-gray-400">Date</th>
              <th class="px-6 py-3 text-sm font-medium text-gray-400">Display Name</th>
              <th class="px-6 py-3 text-sm font-medium text-gray-400">Feedback</th>
              <th class="px-6 py-3 text-sm font-medium text-gray-400">Stars</th>
              <th class="px-6 py-3 text-sm font-medium text-gray-400">Reply</th>
            </tr>
          </thead>
          <tbody>
            ${tableRows}
          </tbody>
        </table>
      </div>
    `;

        const viewReplyButtons = document.querySelectorAll(".view-reply-btn");
        viewReplyButtons.forEach((btn) => {
          btn.addEventListener("click", (e) => {
            const reply = e.target.dataset.reply;
            const replyDate = e.target.dataset.replyDate;

            if (reply === 'No reply yet') {
              modalBody.innerHTML = `
            <div class="space-y-4">
              <p class="text-gray-300">No reply has been received yet.</p>
            </div>
          `;
            } else {
              modalBody.innerHTML = `
            <div class="space-y-4">
              <p class="text-gray-300">${reply}</p>
              ${replyDate ? `<p class="text-sm text-gray-500">Replied on: ${replyDate}</p>` : ''}
            </div>
          `;
            }
            modal.classList.remove("hidden");
          });
        });
      } catch (error) {
        console.error('Error fetching feedback:', error);
        contentArea.innerHTML = `
      <div class="content-card p-8">
        <h2 class="text-3xl font-bold text-white mb-4">Error</h2>
        <p class="text-red-500">Failed to load feedback history.</p>
      </div>
    `;
      }
    });

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

          const response = await fetch("update_profile.php", {
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
      const button = input.nextElementSibling; // The button is directly after the input
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

    logoutBtn.addEventListener("click", () => {
      window.location.href = "logout.php";
    });
  </script>
</body>

</html>