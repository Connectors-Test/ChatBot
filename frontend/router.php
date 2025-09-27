<?php
/**
 * Simple router for the ChatBot application
 * This handles routing without the redirect loop issue
 */

// Get the requested path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Remove query parameters
$path = explode('?', $path)[0];

// Define routes that don't require authentication
$public_routes = [
    'login.php',
    'signup.php',
    'forgot-password.php',
    'reset-password.php',
    'favicon.ico'
];

// Check if this is a public route
$is_public_route = in_array($path, $public_routes) ||
                   strpos($path, 'public/') === 0 ||
                   $path === '' ||
                   $path === 'index.php';

// Include session configuration for all requests
require_once __DIR__ . '/includes/session_config.php';

// Debug logging
debugSession("Router - Path: $path, Public: " . ($is_public_route ? 'Yes' : 'No'));

// Validate session
validateSession();

// For public routes, allow access
if ($is_public_route) {
    // Handle root path - redirect to login for better UX
    if ($path === '' || $path === 'index.php') {
        header("Location: login.php");
        exit;
    }

    // Serve the requested file
    $file_path = __DIR__ . '/' . $path;
    if (file_exists($file_path) && is_file($file_path)) {
        // For PHP files, include them
        if (substr($path, -4) === '.php') {
            require_once $file_path;
        } else {
            // For static files, serve them directly
            return false; // Let PHP's built-in server handle static files
        }
    } else {
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        echo "<p>The requested file '$path' was not found.</p>";
    }
} else {
    // Protected route - require authentication
    if (!isAuthenticated()) {
        error_log("Access denied - redirecting to login.php for session: " . session_id());
        if (checkRedirectLoop()) {
            recordRedirect();
            header("Location: login.php");
            exit;
        } else {
            error_log("Redirect loop detected - showing error page");
            http_response_code(429);
            echo "<h1>Too Many Redirects</h1><p>Please clear your browser cookies and try again.</p>";
            exit;
        }
    }

    // Serve the protected file
    $file_path = __DIR__ . '/' . $path;
    if (file_exists($file_path) && is_file($file_path)) {
        require_once $file_path;
    } else {
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        echo "<p>The requested file '$path' was not found.</p>";
    }
}
?>
