<?php
/**
 * Central Authentication Module
 * ÁTR Beragadt Betegek - Session Management & AD Auth Check
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require AD Login - Redirect to login if not authenticated
 *
 * This function checks if the user is authenticated via Active Directory.
 * If not authenticated, redirects to the AD login page.
 *
 * Usage: Call this at the top of any protected page
 *
 * @param string $loginPage Path to login page (default: login.php)
 * @return void
 */
function requireAdLogin($loginPage = 'login.php') {
    // Check if user is authenticated via AD or database admin
    $isAuthenticated = false;

    // Check AD authentication
    if (isset($_SESSION['ad_authenticated']) && $_SESSION['ad_authenticated'] === true) {
        $isAuthenticated = true;
    }

    // Check database admin authentication (fallback)
    if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0) {
        $isAuthenticated = true;
    }

    // If not authenticated, redirect to login page
    if (!$isAuthenticated) {
        // Store the requested page for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';

        // Redirect to login page
        $currentDir = dirname($_SERVER['SCRIPT_NAME']);
        $loginUrl = rtrim($currentDir, '/') . '/' . ltrim($loginPage, '/');

        header("Location: $loginUrl");
        exit;
    }
}

/**
 * Get current authenticated user info
 * Returns AD user data if available, otherwise admin data
 *
 * @return array|null User data array or null if not authenticated
 */
function getCurrentAuthUser() {
    // Prefer AD user data
    if (isset($_SESSION['ad_authenticated']) && $_SESSION['ad_authenticated'] === true) {
        return [
            'type' => 'ad',
            'username' => $_SESSION['ad_username'] ?? 'Unknown',
            'display_name' => $_SESSION['ad_display_name'] ?? 'Unknown User',
            'email' => $_SESSION['ad_email'] ?? '',
            'is_admin' => $_SESSION['ad_is_admin'] ?? false,
        ];
    }

    // Fallback to database admin
    if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0) {
        return [
            'type' => 'database',
            'username' => $_SESSION['admin_username'] ?? 'Unknown',
            'display_name' => $_SESSION['admin_display_name'] ?? 'Unknown User',
            'email' => '',
            'is_admin' => true,
        ];
    }

    return null;
}

/**
 * Check if current user has admin privileges
 *
 * @return bool True if user is admin, false otherwise
 */
function isAuthAdmin() {
    $user = getCurrentAuthUser();
    return $user !== null && ($user['is_admin'] === true);
}

/**
 * Logout current user
 * Clears all session data and redirects to login page
 *
 * @param string $redirectUrl URL to redirect after logout (default: login.php)
 * @return void
 */
function logoutUser($redirectUrl = 'login.php') {
    // Clear all session variables
    $_SESSION = [];

    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy session
    session_destroy();

    // Redirect to login page
    header("Location: $redirectUrl");
    exit;
}
