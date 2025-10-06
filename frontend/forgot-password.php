<?php
// Prevent caching to avoid redirect loops
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Include centralized session configuration
require_once __DIR__ . '/includes/session_config.php';

$API_BASE = getenv('RENDER') ? "https://chatbot-backend-mxra.onrender.com" : "http://localhost:5001";

// Debug: Log session status
debugSession("Forgot Password page");

// Redirect if already logged in
if(isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    error_log("Redirecting to index.php - user already logged in: " . $_SESSION['username']);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SmartCard AI</title>
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

            <h2 class="form-title">Forgot Password</h2>
            <div class="form-subtitle">
                Enter your username to reset your password
            </div>

            <form id="forgotPasswordForm" autocomplete="off">
                <div class="form-group">
                    <label for="forgotUsername" class="form-label">Username</label>
                    <input type="text" class="form-control" id="forgotUsername" required>
                </div>

                <button type="submit" class="login-btn">Send Reset Email</button>

                <div id="forgotMsg" class="alert alert-info" style="display: none;"></div>
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

        document.getElementById('forgotPasswordForm').onsubmit = async (e) => {
            e.preventDefault();
            const username = document.getElementById('forgotUsername').value;

            // Clear previous messages
            document.getElementById('forgotMsg').style.display = 'none';

            try {
                const res = await fetch(`${API_BASE}/forgot-password`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({username})
                });

                const data = await res.json();

                const messageDiv = document.getElementById('forgotMsg');
                if(data.success) {
                    messageDiv.className = 'alert alert-success';
                    messageDiv.textContent = data.message;
                    document.getElementById('forgotPasswordForm').style.display = 'none';
                } else {
                    messageDiv.className = 'alert alert-danger';
                    messageDiv.textContent = data.message || 'Request failed';
                }
                messageDiv.style.display = 'block';
            } catch (error) {
                const messageDiv = document.getElementById('forgotMsg');
                messageDiv.className = 'alert alert-danger';
                messageDiv.textContent = 'Network error. Please try again.';
                messageDiv.style.display = 'block';
            }
        };
    </script>
</body>
</html>
