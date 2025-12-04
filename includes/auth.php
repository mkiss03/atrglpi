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
 * Require AD Login - Redirect to AD login if not authenticated
 *
 * FIRST TIER: This is the base authentication level.
 * All users MUST authenticate via Active Directory before accessing ANY page.
 *
 * Usage: Call this at the top of any protected page
 *
 * @param string $loginPage Path to AD login page (default: login_ad.php)
 * @return void
 */
function requireAdLogin($loginPage = 'login_ad.php') {
    // Check if user is authenticated via AD (check $_SESSION['ad_user'])
    if (empty($_SESSION['ad_user'])) {
        // Store the requested page for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';

        // Redirect to AD login page
        $currentDir = dirname($_SERVER['SCRIPT_NAME']);
        $loginUrl = rtrim($currentDir, '/') . '/' . ltrim($loginPage, '/');

        header("Location: $loginUrl");
        exit;
    }
}

/**
 * Require Admin Access (Second Tier Authentication)
 *
 * SECOND TIER: Only "Informatikai osztály" department can access admin login.
 * This function checks if the current AD user is allowed to access admin features.
 *
 * Usage: Call this on admin_login.php and admin-specific pages
 *
 * @return void Dies with 403 if user doesn't have access
 */
function requireAdminAccess() {
    // Must have AD session first
    if (empty($_SESSION['ad_user'])) {
        http_response_code(403);
        die('Nincs jogosultság. Kérlek jelentkezz be AD azonosítóval először.');
    }

    $adUser = $_SESSION['ad_user'];
    $department = $adUser['department'] ?? '';

    // Check if user is in "Informatikai osztály" department
    if (strcasecmp($department, 'Informatikai osztály') !== 0) {
        http_response_code(403);
        die('Nincs jogosultság az admin bejelentkezéshez. Csak az Informatikai osztály tagjai férhetnek hozzá.');
    }
}

/**
 * Get current authenticated user info
 * Returns AD user data if available, otherwise admin data
 *
 * @return array|null User data array or null if not authenticated
 */
function getCurrentAuthUser() {
    // Prefer AD user data from $_SESSION['ad_user']
    if (!empty($_SESSION['ad_user'])) {
        return [
            'type' => 'ad',
            'username' => $_SESSION['ad_user']['samaccountname'] ?? 'Unknown',
            'display_name' => $_SESSION['ad_user']['displayname'] ?? 'Unknown User',
            'email' => $_SESSION['ad_user']['mail'] ?? '',
            'department' => $_SESSION['ad_user']['department'] ?? '',
            'is_admin' => isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0,
        ];
    }

    // Fallback to database admin (legacy compatibility)
    if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0) {
        return [
            'type' => 'database',
            'username' => $_SESSION['admin_username'] ?? 'Unknown',
            'display_name' => $_SESSION['admin_display_name'] ?? 'Unknown User',
            'email' => '',
            'department' => '',
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
