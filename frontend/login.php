<?php
// Prevent caching to avoid redirect loops
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Include centralized session configuration
require_once __DIR__ . '/includes/session_config.php';

$API_BASE = getenv('RENDER') ? "https://chatbot-backend-mxra.onrender.com" : "http://localhost:5001";

// Debug: Log session status
debugSession("Login page");

// Validate session first
validateSession();

// Redirect if already logged in
if(isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    error_log("Redirecting to index.php - user already logged in: " . $_SESSION['username']);
    header("Location: index.php");
    exit;
}

// Handle session set from signup or successful login
if(isset($_GET['set_session']) && !empty($_GET['username'])) {
    $_SESSION['username'] = $_GET['username'];
    $_SESSION['login_time'] = time(); // Record login time
    $_SESSION['redirect_count'] = 0; // Reset redirect counter
    $_SESSION['last_redirect'] = 0; // Reset redirect timer
    error_log("Setting session for user: " . $_GET['username']);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartCard AI</title>
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

            <h2 class="form-title">Login</h2>
            <div class="form-subtitle">
                for <span class="text">AI Dashboard Chatbot</span>
            </div>

            <form id="loginForm" autocomplete="off">
                <div class="form-group">
                    <label for="loginUsername" class="form-label">Username</label>
                    <input type="text" class="form-control" id="loginUsername" required>
                </div>

                <div class="form-group">
                    <label for="loginPassword" class="form-label">Password</label>
                    <input type="password" class="form-control" id="loginPassword" required>
                </div>

                <button type="submit" class="login-btn">Login</button>

                <div id="loginMsg" class="alert alert-danger" style="display: none;"></div>
            </form>

            <div class="forgot-password-link">
                <a href="forgot-password.php">Forgot Password?</a>
            </div>

            <div class="signup-link">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_BASE = "<?= $API_BASE ?>";

        document.getElementById('loginForm').onsubmit = async (e) => {
            e.preventDefault();
            const username = document.getElementById('loginUsername').value;
            const password = document.getElementById('loginPassword').value;

            // Clear previous messages
            document.getElementById('loginMsg').style.display = 'none';

            try {
                const res = await fetch(`${API_BASE}/login`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({username, password})
                });

                const data = await res.json();

                if(data.success) {
                    window.location = `?set_session=1&username=${encodeURIComponent(username)}`;
                } else {
                    document.getElementById('loginMsg').textContent = data.message || 'Login failed';
                    document.getElementById('loginMsg').style.display = 'block';
                }
            } catch (error) {
                document.getElementById('loginMsg').textContent = 'Network error. Please try again.';
                document.getElementById('loginMsg').style.display = 'block';
            }
        };
    </script>
</body>
</html>
