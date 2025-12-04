<?php
/**
 * Active Directory Authentication Helper
 * ÁTR Beragadt Betegek - LDAP/AD Integration
 */

/**
 * Authenticate user against Active Directory
 *
 * @param string $username Username (without domain suffix)
 * @param string $password User password
 * @return array|false Returns user data array on success, false on failure
 */
function authenticateAD($username, $password) {
    // Check if LDAP extension is available
    if (!function_exists('ldap_connect')) {
        error_log("AD Auth: LDAP extension not installed - skipping AD authentication");
        return false;
    }

    // Load AD configuration
    $config = require __DIR__ . '/../config/ad.php';

    // Sanitize username (prevent LDAP injection)
    $username = ldap_escape($username, '', LDAP_ESCAPE_FILTER);

    // Connect to AD server
    $ldapConn = ldap_connect($config['host'], $config['port']);

    if (!$ldapConn) {
        error_log("AD Auth: Failed to connect to LDAP server");
        return false;
    }

    // Set LDAP options
    ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($ldapConn, LDAP_OPT_NETWORK_TIMEOUT, 10);

    // Start TLS if configured
    if ($config['use_tls']) {
        if (!@ldap_start_tls($ldapConn)) {
            error_log("AD Auth: Failed to start TLS");
            // Continue without TLS (not recommended for production)
        }
    }

    try {
        // First, bind with service account to search for user
        $serviceBindDn = $config['service_user'] . $config['account_suffix'];
        $serviceBind = @ldap_bind($ldapConn, $serviceBindDn, $config['service_pass']);

        if (!$serviceBind) {
            error_log("AD Auth: Service account bind failed");
            ldap_unbind($ldapConn);
            return false;
        }

        // Search for user
        $searchFilter = str_replace('{username}', $username, $config['user_filter']);
        $searchResult = @ldap_search(
            $ldapConn,
            $config['user_search_base'],
            $searchFilter,
            ['dn', 'sAMAccountName', 'displayName', 'mail', 'department', 'memberOf']
        );

        if (!$searchResult) {
            error_log("AD Auth: User search failed for: $username");
            ldap_unbind($ldapConn);
            return false;
        }

        $entries = ldap_get_entries($ldapConn, $searchResult);

        if ($entries['count'] === 0) {
            error_log("AD Auth: User not found: $username");
            ldap_unbind($ldapConn);
            return false;
        }

        $userEntry = $entries[0];
        $userDn = $userEntry['dn'];

        // Try to bind as the user (authenticate password)
        $userBind = @ldap_bind($ldapConn, $userDn, $password);

        if (!$userBind) {
            error_log("AD Auth: Password authentication failed for: $username");
            ldap_unbind($ldapConn);
            return false;
        }

        // Authentication successful - gather user data
        $displayName = isset($userEntry['displayname'][0]) ? $userEntry['displayname'][0] : $username;
        $email = isset($userEntry['mail'][0]) ? $userEntry['mail'][0] : '';
        $department = isset($userEntry['department'][0]) ? $userEntry['department'][0] : '';
        $memberOf = isset($userEntry['memberof']) ? $userEntry['memberof'] : [];

        // Check if user is in admin group (Informatikai Osztály)
        $isAdmin = false;
        if (is_array($memberOf)) {
            foreach ($memberOf as $group) {
                if (stripos($group, 'Informatikai Osztály') !== false) {
                    $isAdmin = true;
                    break;
                }
            }
        }

        ldap_unbind($ldapConn);

        return [
            'username' => $username,
            'display_name' => $displayName,
            'email' => $email,
            'department' => $department,
            'is_admin' => $isAdmin,
            'dn' => $userDn,
        ];

    } catch (Exception $e) {
        error_log("AD Auth: Exception: " . $e->getMessage());
        if ($ldapConn) {
            @ldap_unbind($ldapConn);
        }
        return false;
    }
}

/**
 * Login user with AD credentials and create session
 *
 * @param string $username Username
 * @param string $password Password
 * @return bool True on success, false on failure
 */
function loginWithAD($username, $password) {
    $userData = authenticateAD($username, $password);

    if (!$userData) {
        return false;
    }

    // Start session
    startSession();

    // Store user data in session
    $_SESSION['ad_authenticated'] = true;
    $_SESSION['ad_username'] = $userData['username'];
    $_SESSION['ad_display_name'] = $userData['display_name'];
    $_SESSION['ad_email'] = $userData['email'];
    $_SESSION['ad_is_admin'] = $userData['is_admin'];

    // If user is in Informatikai Osztály, also set admin session
    if ($userData['is_admin']) {
        // Create/update admin record in database
        require_once __DIR__ . '/../models/Admin.php';
        $adminModel = new Admin();

        // Check if admin exists in database
        $existingAdmin = $adminModel->getByUsername($userData['username']);

        if (!$existingAdmin) {
            // Create admin record (with random password since we use AD)
            $adminModel->create([
                'username' => $userData['username'],
                'display_name' => $userData['display_name'],
                'password' => bin2hex(random_bytes(32)), // Random, won't be used
            ]);
            $existingAdmin = $adminModel->getByUsername($userData['username']);
        }

        // Set admin session variables
        $_SESSION['admin_id'] = $existingAdmin['id'];
        $_SESSION['admin_username'] = $existingAdmin['username'];
        $_SESSION['admin_display_name'] = $existingAdmin['display_name'];
    }

    return true;
}

/**
 * Check if current user is AD authenticated
 *
 * @return bool
 */
function isADAuthenticated() {
    startSession();
    return isset($_SESSION['ad_authenticated']) && $_SESSION['ad_authenticated'] === true;
}

/**
 * Get current AD user data
 *
 * @return array|null
 */
function getCurrentADUser() {
    startSession();
    if (!isADAuthenticated()) {
        return null;
    }

    return [
        'username' => $_SESSION['ad_username'] ?? null,
        'display_name' => $_SESSION['ad_display_name'] ?? null,
        'email' => $_SESSION['ad_email'] ?? null,
        'is_admin' => $_SESSION['ad_is_admin'] ?? false,
    ];
}
