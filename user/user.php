<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feedback System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
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
          <img src="https://via.placeholder.com/40" alt="Profile" class="w-10 h-10">
          <span>John Doe</span>
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
      document.getElementById('feedbackForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const feedback = document.getElementById('feedback').value;
        const anonymous = document.getElementById('anonymous').checked;

        if (!feedback) {
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please provide feedback'
          });
          return;
        }

        if (!selectedRating) {
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please select a rating'
          });
          return;
        }

        // Encrypt feedback before sending
        const encryptedFeedback = CryptoJS.SHA3(feedback, {
          outputLength: 512
        }).toString();

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
    historyBtn.addEventListener("click", () => {
      contentArea.innerHTML = `
        <div class="content-card p-8">
          <h2 class="text-3xl font-bold text-white mb-4">History</h2>
          <table class="w-full text-left border-collapse bg-gray-900 rounded-lg">
            <thead class="bg-gray-800">
              <tr>
                <th class="px-6 py-3 text-sm font-medium text-gray-400">Date</th>
                <th class="px-6 py-3 text-sm font-medium text-gray-400">Feedback</th>
                <th class="px-6 py-3 text-sm font-medium text-gray-400">Stars</th>
                <th class="px-6 py-3 text-sm font-medium text-gray-400">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="px-6 py-4">2024-12-03</td>
                <td class="px-6 py-4">Awesome experience!</td>
                <td class="px-6 py-4 text-yellow-400">★★★★★</td>
                <td class="px-6 py-4">
                  <button class="text-blue-400 hover:underline view-reply-btn" data-reply="Thank you for your feedback!">View</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      `;

      const viewReplyButtons = document.querySelectorAll(".view-reply-btn");
      viewReplyButtons.forEach((btn) => {
        btn.addEventListener("click", (e) => {
          const reply = e.target.dataset.reply;
          modalBody.innerHTML = `<p class="text-gray-300">${reply}</p>`;
          modal.classList.remove("hidden");
        });
      });
    });

    setupProfileBtn.addEventListener("click", () => {
      contentArea.innerHTML = `
        <div class="content-card p-8">
          <h2 class="text-3xl font-bold text-white mb-4">Setup Profile</h2>
          <form>
            <div class="mb-4">
              <label for="profile-pic" class="block text-sm font-medium text-gray-300">Profile Picture</label>
              <input type="file" id="profile-pic" class="block w-full mt-1 px-4 py-2 border rounded-md">
            </div>
            <div class="mb-4">
              <label for="old-password" class="block text-sm font-medium text-gray-300">Old Password</label>
              <input type="password" id="old-password" class="block w-full mt-1 px-4 py-2 border rounded-md">
            </div>
            <div class="mb-4">
              <label for="new-password" class="block text-sm font-medium text-gray-300">New Password</label>
              <input type="password" id="new-password" class="block w-full mt-1 px-4 py-2 border rounded-md">
            </div>
            <div class="mb-4">
              <label for="confirm-password" class="block text-sm font-medium text-gray-300">Confirm New Password</label>
              <input type="password" id="confirm-password" class="block w-full mt-1 px-4 py-2 border rounded-md">
            </div>
            <button class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Save Changes</button>
          </form>
        </div>
      `;
    });

    logoutBtn.addEventListener("click", () => {
      window.location.href = "../login.php";
    });
  </script>
</body>

</html>