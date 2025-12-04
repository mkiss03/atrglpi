<?php
/**
 * Active Directory Authentication Helper
 * ÁTR Beragadt Betegek - LDAP/AD Integration
 */

/**
 * Authenticate user against Active Directory
 * Based on working user_keres() function - simplified for legacy AD 2008/2009
 *
 * @param string $username Username (without domain suffix)
 * @param string $password User password
 * @return array|false Returns user data array on success, false on failure
 */
function authenticateAD($username, $password) {
    // Check if LDAP extension is available
    if (!function_exists('ldap_connect')) {
        error_log("AD Auth: LDAP extension not installed");
        return false;
    }

    // AD Configuration (exactly as in working user_keres function)
    $serviceUsername = "telefonkonyv";
    $servicePassword = "Book1234!";
    $accountSuffix = '@kmok.local';
    $hostname = '10.1.0.16';
    $baseDn = "dc=kmok,dc=local";

    // Connect to AD server (no port specified, like working example)
    $con = ldap_connect($hostname);

    if (!is_resource($con)) {
        error_log("AD Auth: Failed to connect to LDAP server");
        return false;
    }

    // Set LDAP options (exactly as in working example)
    ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($con, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($con, LDAP_OPT_TIMELIMIT, 10);
    ldap_set_option($con, LDAP_OPT_NETWORK_TIMEOUT, 10);

    // Try to authenticate user directly first
    $userBind = @ldap_bind($con, $username . $accountSuffix, $password);

    if (!$userBind) {
        // User authentication failed
        error_log("AD Auth: User authentication failed for: $username");
        ldap_close($con);
        return false;
    }

    // User authenticated successfully! Now get user info with service account
    ldap_close($con);

    // Reconnect with service account to get user details
    $con = ldap_connect($hostname);
    if (!is_resource($con)) {
        error_log("AD Auth: Failed to reconnect to LDAP server");
        return false;
    }

    ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($con, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($con, LDAP_OPT_TIMELIMIT, 10);
    ldap_set_option($con, LDAP_OPT_NETWORK_TIMEOUT, 10);

    // Bind with service account to search user details
    if (!ldap_bind($con, $serviceUsername . $accountSuffix, $servicePassword)) {
        error_log("AD Auth: Service account bind failed");
        ldap_close($con);
        return false;
    }

    // Search for user details (attributes from working example)
    $attributes_ad = array(
        "displayName", "description", "cn", "givenName", "sn", "mail",
        "co", "mobile", "company", "telephonenumber", "facsimiletelephonenumber",
        "ipPhone", "info", "physicaldeliveryofficename", "homephone", "title",
        "department", "memberof", "samaccountname", "useraccountcontrol"
    );

    $searchFilter = "(samaccountname=" . $username . ")";
    $result = ldap_search($con, $baseDn, $searchFilter, $attributes_ad);

    if (!$result) {
        error_log("AD Auth: User search failed");
        ldap_close($con);
        return false;
    }

    $info = ldap_get_entries($con, $result);

    if ($info['count'] === 0) {
        error_log("AD Auth: User not found in search");
        ldap_close($con);
        return false;
    }

    // Get user data
    $userEntry = $info[0];
    $displayName = isset($userEntry['displayname'][0]) ? $userEntry['displayname'][0] : $username;
    $email = isset($userEntry['mail'][0]) ? $userEntry['mail'][0] : '';
    $department = isset($userEntry['department'][0]) ? $userEntry['department'][0] : '';
    $memberOf = isset($userEntry['memberof']) ? $userEntry['memberof'] : [];

    // Check if user is in "Informatikai Osztály" group
    $isAdmin = false;
    if (is_array($memberOf)) {
        for ($i = 0; $i < count($memberOf); $i++) {
            if (stripos($memberOf[$i], 'Informatikai Osztály') !== false) {
                $isAdmin = true;
                break;
            }
        }
    }

    ldap_close($con);

    error_log("AD Auth: Authentication successful for: $username");

    return [
        'username' => $username,
        'display_name' => $displayName,
        'email' => $email,
        'department' => $department,
        'is_admin' => $isAdmin,
        'dn' => isset($userEntry['dn']) ? $userEntry['dn'] : '',
    ];
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
