<?php
include '../database/dbConnect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
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
          <img src="https://via.placeholder.com/40" alt="Profile" class="w-10 h-10">
          <span>Admin</span>
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
                  <td class="px-6 py-4">February</td>
                  <td class="px-6 py-4">50</td>
                  <td class="px-6 py-4">4.5</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      `;
    });

    // Setup Profile Section
    document.getElementById("setup-profile-btn").addEventListener("click", () => {
      contentArea.innerHTML = `
        <div class="content-card p-8">
          <h2 class="text-3xl font-bold text-white mb-4">Setup Profile</h2>
          <form>
            <label class="block text-gray-300 mb-2">Update Profile Picture</label>
            <input type="file" class="block w-full text-gray-400 bg-gray-800 rounded mb-4 p-2">
            <label class="block text-gray-300 mb-2">Old Password</label>
            <input type="password" class="block w-full bg-gray-800 text-gray-300 rounded mb-4 p-2">
            <label class="block text-gray-300 mb-2">New Password</label>
            <input type="password" class="block w-full bg-gray-800 text-gray-300 rounded mb-4 p-2">
            <button class="bg-blue-500 text-white px-4 py-2 rounded-lg">Save Changes</button>
          </form>
        </div>
      `;
    });

    // Logout Button
    logoutBtn.addEventListener("click", () => {
      window.location.href = "Ad_logout.php";
    });
  </script>
</body>

</html>