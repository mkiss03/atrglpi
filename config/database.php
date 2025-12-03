<?php
/**
 * Database Configuration
 * ÁTR Beragadt Betegek - Web Application
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_NAME', 'atr_betegek');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection
 * @return PDO
 */
function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

/**
 * Start session if not already started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in as admin
 * @return bool
 */
function isAdmin() {
    startSession();
    return isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0;
}

/**
 * Get current admin info
 * @return array|null
 */
function getCurrentAdmin() {
    startSession();
    if (!isAdmin()) {
        return null;
    }

    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'display_name' => $_SESSION['admin_display_name'] ?? null,
    ];
}

/**
 * Get client IP address (handles proxies)
 * @return string
 */
function getClientIp() {
    $ip = '';

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Check for proxy
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Validate IP
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Set flash message
 * @param string $type (success, error, warning, info)
 * @param string $message
 */
function setFlashMessage($type, $message) {
    startSession();
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlashMessage() {
    startSession();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Redirect to a page
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Escape HTML output
 * @param string $string
 * @return string
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
