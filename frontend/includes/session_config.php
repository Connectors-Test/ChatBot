<?php
/**
 * Centralized session configuration
 * This file should be included BEFORE session_start() in all PHP files
 */

// Set session configuration before starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

// Set explicit session save path to ensure sessions work properly
$sessionPath = '/tmp/php_sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);

// Set session garbage collection settings
ini_set('session.gc_maxlifetime', 3600); // 1 hour
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Set session cookie lifetime (0 = until browser closes)
ini_set('session.cookie_lifetime', 0);

// Start the session
session_start();

/**
 * Debug function to log session information
 * Only active in development - remove in production
 */
function debugSession($pageName) {
    error_log("$pageName - Session ID: " . session_id());
    error_log("$pageName - Session username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NOT SET'));
    error_log("$pageName - Session save path: " . session_save_path());
}

/**
 * Validate session and handle invalid sessions
 * @return bool True if session is valid, false otherwise
 */
function validateSession() {
    // Only destroy sessions that are actually expired or corrupted
    // Sessions without usernames are valid for non-logged-in users

    // Check session age (only if user is logged in)
    if (isset($_SESSION['login_time']) && isset($_SESSION['username'])) {
        $session_age = time() - $_SESSION['login_time'];
        $max_age = 3600; // 1 hour

        if ($session_age > $max_age) {
            error_log("Session expired - destroying session: " . session_id());
            session_destroy();
            session_start(); // Start a new session
            return false;
        }
    }

    return true;
}

/**
 * Check if user is authenticated (has valid session with username)
 * @return bool True if user is logged in, false otherwise
 */
function isAuthenticated() {
    return isset($_SESSION['username']) && !empty($_SESSION['username']);
}

/**
 * Require authentication for protected pages
 * @param string $redirectTo Page to redirect to if not authenticated (default: login.php)
 * @return bool True if user is authenticated, redirects if not
 */
function requireAuth($redirectTo = 'login.php') {
    if (!isAuthenticated()) {
        error_log("Access denied - redirecting to $redirectTo for session: " . session_id());
        if (checkRedirectLoop()) {
            recordRedirect();
            header("Location: $redirectTo");
            exit;
        } else {
            error_log("Redirect loop detected - showing error page");
            http_response_code(429);
            echo "<h1>Too Many Redirects</h1><p>Please clear your browser cookies and try again.</p>";
            exit;
        }
    }
    return true;
}

/**
 * Check for potential redirect loops
 * @return bool True if redirect is safe, false if loop detected
 */
function checkRedirectLoop() {
    $redirect_count = $_SESSION['redirect_count'] ?? 0;
    $redirect_time = $_SESSION['last_redirect'] ?? 0;

    // Reset counter if more than 30 seconds have passed
    if (time() - $redirect_time > 30) {
        $redirect_count = 0;
    }

    // If too many redirects in short time, block further redirects
    if ($redirect_count > 5) {
        error_log("Redirect loop detected - blocking redirects for session: " . session_id());
        return false;
    }

    return true;
}

/**
 * Record a redirect attempt
 */
function recordRedirect() {
    $_SESSION['redirect_count'] = ($_SESSION['redirect_count'] ?? 0) + 1;
    $_SESSION['last_redirect'] = time();
}
?>
