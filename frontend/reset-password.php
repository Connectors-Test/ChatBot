<?php
// Prevent caching to avoid redirect loops
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Include centralized session configuration
require_once __DIR__ . '/includes/session_config.php';

$API_BASE = getenv('RENDER') ? "https://chatbot-backend.onrender.com" : "http://localhost:5001";

// Debug: Log session status
debugSession("Reset Password page");

// Redirect if already logged in
if(isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    error_log("Redirecting to index.php - user already logged in: " . $_SESSION['username']);
    header("Location: index.php");
    exit;
}

// Get token from URL parameter
$token = $_GET['token'] ?? '';
if(empty($token)) {
    header("Location: forgot-password.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SmartCard AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/login.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo-container">
                <img src="public/images/logo.png" alt="SmartCard AI Logo" class="logo">
                <div class="company-name">SmartCard AI</div>
                <div class="tagline">Affordable Dashboards in Town</div>
            </div>

            <h2 class="form-title">Reset Password</h2>
            <div class="form-subtitle">
                Enter your new password
            </div>

            <form id="resetPasswordForm" autocomplete="off">
                <div class="form-group">
                    <label for="newPassword" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="newPassword" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirmPassword" required minlength="6">
                </div>

                <button type="submit" class="login-btn">Reset Password</button>

                <div id="resetMsg" class="alert alert-info" style="display: none;"></div>
            </form>

            <div class="login-link">
                Remember your password? <a href="login.php">Back to Login</a>
            </div>

            <div class="signup-link">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_BASE = "<?= $API_BASE ?>";
        const RESET_TOKEN = "<?= $token ?>";

        document.getElementById('resetPasswordForm').onsubmit = async (e) => {
            e.preventDefault();
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Clear previous messages
            document.getElementById('resetMsg').style.display = 'none';

            // Validate passwords match
            if(newPassword !== confirmPassword) {
                const messageDiv = document.getElementById('resetMsg');
                messageDiv.className = 'alert alert-danger';
                messageDiv.textContent = 'Passwords do not match';
                messageDiv.style.display = 'block';
                return;
            }

            // Validate password length
            if(newPassword.length < 6) {
                const messageDiv = document.getElementById('resetMsg');
                messageDiv.className = 'alert alert-danger';
                messageDiv.textContent = 'Password must be at least 6 characters long';
                messageDiv.style.display = 'block';
                return;
            }

            try {
                const res = await fetch(`${API_BASE}/reset-password`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        token: RESET_TOKEN,
                        new_password: newPassword
                    })
                });

                const data = await res.json();

                const messageDiv = document.getElementById('resetMsg');
                if(data.success) {
                    messageDiv.className = 'alert alert-success';
                    messageDiv.textContent = data.message + ' Redirecting to login...';
                    document.getElementById('resetPasswordForm').style.display = 'none';

                    // Redirect to login page after 3 seconds
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 3000);
                } else {
                    messageDiv.className = 'alert alert-danger';
                    messageDiv.textContent = data.message || 'Password reset failed';
                }
                messageDiv.style.display = 'block';
            } catch (error) {
                const messageDiv = document.getElementById('resetMsg');
                messageDiv.className = 'alert alert-danger';
                messageDiv.textContent = 'Network error. Please try again.';
                messageDiv.style.display = 'block';
            }
        };
    </script>
</body>
</html>
