<?php
// Prevent caching to avoid redirect loops
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Include centralized session configuration
require_once __DIR__ . '/includes/session_config.php';

$API_BASE = "http://localhost:5001";

// Debug: Log session status
debugSession("Signup page");

// Redirect if already logged in
if(isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    error_log("Redirecting to index.php - user already logged in: " . $_SESSION['username']);
    header("Location: index.php");
    exit;
}

// Handle session set from signup
if(isset($_GET['set_session']) && !empty($_GET['username'])) {
    $_SESSION['username'] = $_GET['username'];
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
    <title>Sign Up - SmartCard AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/signup.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="signup-container">
            <div class="logo-container">
                <img src="public/images/logo.png" alt="SmartCard AI Logo" class="logo">
                <div class="company-name">SmartCard AI</div>
                <div class="tagline">Affordable Dashboards in Town</div>
            </div>

            <h2 class="form-title">Sign Up</h2>
            <div class="form-subtitle">
                for <span class="text">AI Dashboard Chatbot</span>
            </div>

            <form id="signupForm" autocomplete="off">
                <div class="form-group">
                    <label for="signupUsername" class="form-label">Username</label>
                    <input type="text" class="form-control" id="signupUsername" required>
                </div>

                <div class="form-group">
                    <label for="signupPassword" class="form-label">Password</label>
                    <input type="password" class="form-control" id="signupPassword" required>
                </div>

                <div class="form-group">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="acceptTerms" required>
                        <label class="form-check-label" for="acceptTerms">
                            Accept <a href="https://www.smartcardai.com/terms" target="_blank">T&C</a>
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="acceptPrivacy" required>
                        <label class="form-check-label" for="acceptPrivacy">
                            Accept <a href="https://www.smartcardai.com/privacy" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                </div>

                <button type="submit" class="signup-btn">Sign Up</button>

                <div id="signupMsg" class="alert alert-danger" style="display: none;"></div>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_BASE = "<?= $API_BASE ?>";

        document.getElementById('signupForm').onsubmit = async (e) => {
            e.preventDefault();
            const username = document.getElementById('signupUsername').value;
            const password = document.getElementById('signupPassword').value;
            const acceptedTerms = document.getElementById('acceptTerms').checked;
            const acceptedPrivacy = document.getElementById('acceptPrivacy').checked;

            // Clear previous messages
            document.getElementById('signupMsg').style.display = 'none';

            try {
                const res = await fetch(`${API_BASE}/signup`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        username,
                        password,
                        accepted_terms: acceptedTerms,
                        accepted_privacy: acceptedPrivacy
                    })
                });

                const data = await res.json();

                if(data.success) {
                    window.location = `?set_session=1&username=${encodeURIComponent(username)}`;
                } else {
                    document.getElementById('signupMsg').textContent = data.message || 'Signup failed';
                    document.getElementById('signupMsg').style.display = 'block';
                }
            } catch (error) {
                document.getElementById('signupMsg').textContent = 'Network error. Please try again.';
                document.getElementById('signupMsg').style.display = 'block';
            }
        };
    </script>
</body>
</html>
