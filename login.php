<?php
session_start();
include "config.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uid = trim($_POST['uid']);
    $password = trim($_POST['password']);
    if (!empty($uid) && !empty($password)) {
        // Check user in DB
        $sql = "SELECT * FROM users WHERE uid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $uid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
          $user = $result->fetch_assoc();
          if ($user['password'] === md5($password)) {
              $_SESSION['uid']  = $user['uid'];
              $_SESSION['name'] = $user['name'];
              $_SESSION['role'] = $user['role'];

              if ($user['role'] === 'admin') {
                  header("Location: admin/dashboard.php");
              } elseif ($user['role'] === 'student') {
                  header("Location: student/dashboard.php");
              } else {
                  $error = "User role not assigned. Please contact admin.";
              }
              exit();
        } else {
            $error = "Invalid password.";
        } 
    } else {
      $error = "User not found.";
    }

    } else {
        $error = "Please enter both UID and Password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>College Event Management - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://use.typekit.net/yjp3aho.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />

    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sofia: ["sofia-pro", "sans-serif"],
            },
            colors: {
              "university-blue": "#1e3a8a",
              "university-light": "#3b82f6",
            },
          },
        },
      };
    </script>
    <style>
      .bg-blur {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
      }

      .input-focus:focus {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
      }

      .login-bg {
        background: linear-gradient(
            135deg,
            rgba(30, 58, 138, 0.9) 0%,
            rgba(59, 130, 246, 0.8) 100%
          ),
          url("https://images.unsplash.com/photo-1541339907198-e08756dedf3f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2340&q=80");
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
      }

      .form-glass {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
      }

      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .fade-in {
        animation: fadeIn 0.6s ease-out forwards;
      }

      .error-message {
        transform: translateY(-10px);
        opacity: 0;
        transition: all 0.3s ease;
      }

      .error-message.show {
        transform: translateY(0);
        opacity: 1;
      }

      .transform-reset {
        transform: none !important;
      }

    </style>
  </head>
  <body class="min-h-screen login-bg font-sofia">
    <!-- Main Container -->
    <div
      class="min-h-screen flex items-center justify-center px-4 py-8"
      data-id="main-container"
    >
      <!-- Login Card -->
      <div class="w-full max-w-md fade-in" data-id="login-card">
        <!-- University Header -->
        <div class="text-center mb-8" data-id="header-section">
          <div
            class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-lg mb-4"
            data-id="logo-container"
          >
            <i
              data-lucide="graduation-cap"
              class="w-8 h-8 text-university-blue"
            ></i>
          </div>
          <h1 class="text-3xl font-bold text-white mb-2" data-id="main-title">
            College Event Hub
          </h1>
          <p class="text-blue-100" data-id="subtitle">
            Login to manage your events
          </p>
        </div>

        <!-- Login Form -->
        <div
          class="form-glass rounded-2xl shadow-2xl p-8"
          data-id="form-container"
        >
          <!-- Error Message Display -->
          <div
            id="error-display"
            class="error-message mb-4 hidden"
            data-id="error-display"
          >
            <div
              class="bg-red-50 border border-red-200 rounded-lg p-3 flex items-center"
              data-id="error-content"
            >
              <i
                data-lucide="alert-circle"
                class="w-5 h-5 text-red-500 mr-2"
              ></i>
              <span
                class="text-red-700 text-sm"
                id="error-text"
                data-id="error-text"
              >
                Please check your credentials and try again.
              </span>
            </div>
          </div>

          <form
            method="POST"
            action="login.php"
            id="loginForm"
            data-id="login-form"
          >
            <!-- UID Field -->
            <div class="mb-6" data-id="uid-field-container">
              <label
                for="uid"
                class="block text-sm font-medium text-gray-700 mb-2"
                data-id="uid-label"
              >
                University ID
              </label>
              <div class="relative transform-reset" data-id="uid-input-wrapper">
                <div
                  class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                  data-id="uid-icon"
                >
                  <i
                    data-lucide="user"
                    class="w-5 h-5 text-gray-400"
                  ></i>
                </div>
                <input
                  type="text"
                  id="uid"
                  name="uid"
                  required
                  class="transform-reset input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-university-light focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-500"
                  placeholder="Enter your University ID"
                  data-id="uid-input"
                />
              </div>
            </div>

            <!-- Password Field -->
            <div class="mb-6" data-id="password-field-container">
              <label
                for="password"
                class="block text-sm font-medium text-gray-700 mb-2"
                data-id="password-label"
              >
                Password
              </label>
              <div class="relative" data-id="password-input-wrapper">
                <div
                  class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                  data-id="password-icon"
                >
                  <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                </div>
                <input
                  type="password"
                  id="password"
                  name="password"
                  required
                  class="transform-reset input-focus block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-university-light focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-500"
                  placeholder="Enter your password"
                  data-id="password-input"
                />
                <button
                  type="button"
                  id="togglePassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center"
                  data-id="password-toggle"
                >
                  <i
                    data-lucide="eye"
                    class="w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors"
                  ></i>
                </button>
              </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div
              class="flex items-center justify-between mb-6"
              data-id="form-options"
            >
              <div class="flex items-center" data-id="remember-me-container">
                <input
                  id="remember"
                  name="remember"
                  type="checkbox"
                  class="h-4 w-4 text-university-blue focus:ring-university-light border-gray-300 rounded"
                  data-id="remember-checkbox"
                />
                <label
                  for="remember"
                  class="ml-2 block text-sm text-gray-700"
                  data-id="remember-label"
                >
                  Remember me
                </label>
              </div>
              <a
                href="#"
                class="text-sm text-university-blue hover:text-university-light transition-colors"
                data-id="forgot-password-link"
              >
                Forgot password?
              </a>
            </div>

            <!-- Login Button -->
            <button
              type="submit"
              class="w-full bg-university-blue hover:bg-university-light focus:ring-4 focus:ring-blue-200 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center"
              id="loginButton"
              data-id="login-button"
            >
              <span id="buttonText" data-id="button-text"
                >Login to Dashboard</span
              >
              <i
                data-lucide="arrow-right"
                class="w-5 h-5 ml-2"
                id="buttonIcon"
                data-id="button-icon"
              ></i>
            </button>
          </form>

          <!-- Additional Links -->
          <div
            class="mt-8 pt-6 border-t border-gray-200 text-center"
            data-id="additional-links"
          >
            <p class="text-sm text-gray-600" data-id="signup-prompt">
              Need an account?
              <a
                href="#"
                class="text-university-blue hover:text-university-light font-medium transition-colors"
                data-id="signup-link"
              >
                Contact IT Support
              </a>
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8" data-id="footer-section">
          <p class="text-blue-100 text-sm" data-id="footer-text">
            © 2024 University Event Management System
          </p>
        </div>
      </div>
    </div>

    <script type="module">
      // Initialize Lucide icons
      lucide.createIcons();
      // Password toggle functionality
      const togglePassword = document.getElementById("togglePassword");
      const passwordInput = document.getElementById("password");
      const passwordIcon = togglePassword.querySelector("i");

      togglePassword.addEventListener("click", () => {
        const type =
          passwordInput.getAttribute("type") === "password"
            ? "text"
            : "password";
        passwordInput.setAttribute("type", type);

        // Update icon
        passwordIcon.setAttribute(
          "data-lucide",
          type === "password" ? "eye" : "eye-off"
        );
        // lucide.createIcons();
      });

      // Form validation and submission
      const loginForm = document.getElementById("loginForm");
      const errorDisplay = document.getElementById("error-display");
      const errorText = document.getElementById("error-text");
      const loginButton = document.getElementById("loginButton");
      const buttonText = document.getElementById("buttonText");
      const buttonIcon = document.getElementById("buttonIcon");

      // Show error message
      function showError(message) {
        errorText.textContent = message;
        errorDisplay.classList.remove("hidden");
        errorDisplay.classList.add("show");

        // Auto hide after 5 seconds
        setTimeout(() => {
          hideError();
        }, 5000);
      }

      // Hide error message
      function hideError() {
        errorDisplay.classList.remove("show");
        setTimeout(() => {
          errorDisplay.classList.add("hidden");
        }, 300);
      }

      // Set loading state
      function setLoading(isLoading) {
        if (isLoading) {
          loginButton.disabled = true;
          loginButton.classList.add("opacity-75", "cursor-not-allowed");
          buttonText.textContent = "Logging in...";
          buttonIcon.setAttribute("data-lucide", "loader-2");
          buttonIcon.classList.add("animate-spin");
        } else {
          loginButton.disabled = false;
          loginButton.classList.remove("opacity-75", "cursor-not-allowed");
          buttonText.textContent = "Login to Dashboard";
          buttonIcon.setAttribute("data-lucide", "arrow-right");
          buttonIcon.classList.remove("animate-spin");
        }
        lucide.createIcons();
      }

      // Form submission handler
      loginForm.addEventListener("submit", (e) => {
        hideError();

        const uid = document.getElementById("uid").value.trim();
        const password = document.getElementById("password").value.trim();

        // Client-side validation
        if (!uid) {
          e.preventDefault();
          showError("Please enter your University ID");
          document.getElementById("uid").focus();
          return;
        }

        if (!password) {
          e.preventDefault();
          showError("Please enter your password");
          document.getElementById("password").focus();
          return;
        }

        if (uid.length < 3) {
          e.preventDefault();
          showError("University ID must be at least 3 characters long");
          document.getElementById("uid").focus();
          return;
        }

        if (password.length < 6) {
          e.preventDefault();
          showError("Password must be at least 6 characters long");
          document.getElementById("password").focus();
          return;
        }

        // Set loading state (form will submit to login.php)
        setLoading(true);

        // Note: In a real implementation, you might want to handle the response
        // For now, the form will submit normally to login.php
      });

      // Input field focus effects
      const inputs = document.querySelectorAll(
        'input[type="text"], input[type="password"]'
      );
      inputs.forEach((input) => {
        input.addEventListener("focus", () => {
          input.parentElement.classList.add("ring-2", "ring-university-light");
        });

        input.addEventListener("blur", () => {
          input.parentElement.classList.remove(
            "ring-2",
            "ring-university-light"
          );
        });
      });

      // Forgot password handler
      document
        .getElementById("forgotPasswordLink")
        ?.addEventListener("click", (e) => {
          e.preventDefault();
          showError(
            "Please contact IT Support at support@university.edu for password reset."
          );
        });

      // Add subtle animations on load
      setTimeout(() => {
        const elements = document.querySelectorAll("[data-id]");
        elements.forEach((el, index) => {
          setTimeout(() => {
            el.style.opacity = "1";
            el.style.transform = "translateY(0)";
          }, index * 50);
        });
      }, 100);
    </script>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    lucide.createIcons();
  });
</script>
  </body>
</html>
