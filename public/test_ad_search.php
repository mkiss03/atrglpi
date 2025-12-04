<?php
/**
 * AD Test Script - Search for user "kismm"
 * Based on working user_keres() function
 */

// LDAP extension check
if (!function_exists('ldap_connect')) {
    die("ERROR: LDAP extension not installed! Run: sudo apt-get install php-ldap");
}

echo "<h1>AD User Search Test - kismm</h1>";
echo "<pre>";

// AD Configuration (from working example)
$username = "telefonkonyv";
$password = "Book1234!";
$account_suffix = '@kmok.local';
$hostname = '10.1.0.16';
$base = "dc=kmok,dc=local";

echo "=== Configuration ===\n";
echo "Hostname: $hostname\n";
echo "Base DN: $base\n";
echo "Service Account: $username$account_suffix\n";
echo "\n";

// Connect to AD server
echo "=== Connecting to AD server ===\n";
$con = ldap_connect($hostname);

if (!is_resource($con)) {
    die("ERROR: Cannot connect to LDAP server!\n");
}
echo "✓ Connected to LDAP server\n\n";

// Set LDAP options (exactly as in working example)
ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($con, LDAP_OPT_REFERRALS, 0);
ldap_set_option($con, LDAP_OPT_TIMELIMIT, 10);
ldap_set_option($con, LDAP_OPT_NETWORK_TIMEOUT, 10);
echo "✓ LDAP options set\n\n";

// Bind with service account
echo "=== Binding with service account ===\n";
$bind = @ldap_bind($con, $username . $account_suffix, $password);

if (!$bind) {
    $error = ldap_error($con);
    die("ERROR: Service account bind failed!\nLDAP Error: $error\n");
}
echo "✓ Service account bind successful\n\n";

// Search for user "kismm"
echo "=== Searching for user: kismm ===\n";
$keresett = "kismm";
$mire = "samaccountname"; // Search by account name

$attributes_ad = array(
    "displayName", "description", "cn", "givenName", "sn", "mail",
    "co", "mobile", "company", "telephonenumber", "facsimiletelephonenumber",
    "ipPhone", "info", "physicaldeliveryofficename", "homephone", "title",
    "department", "memberof", "samaccountname", "useraccountcontrol"
);

$searchFilter = "($mire=*$keresett*)";
echo "Search Filter: $searchFilter\n";
echo "Searching...\n\n";

$result = ldap_search($con, $base, $searchFilter, $attributes_ad);

if (!$result) {
    $error = ldap_error($con);
    die("ERROR: Search failed!\nLDAP Error: $error\n");
}

$info = ldap_get_entries($con, $result);
echo "✓ Search completed\n";
echo "Found {$info['count']} result(s)\n\n";

// Display results
if ($info['count'] > 0) {
    echo "=== USER DETAILS ===\n";

    for ($i = 0; $i < $info['count']; $i++) {
        $user = $info[$i];

        echo "\n--- User #" . ($i + 1) . " ---\n";
        echo "DN: " . ($user['dn'] ?? 'N/A') . "\n";
        echo "sAMAccountName: " . ($user['samaccountname'][0] ?? 'N/A') . "\n";
        echo "Display Name: " . ($user['displayname'][0] ?? 'N/A') . "\n";
        echo "First Name: " . ($user['givenname'][0] ?? 'N/A') . "\n";
        echo "Last Name: " . ($user['sn'][0] ?? 'N/A') . "\n";
        echo "Email: " . ($user['mail'][0] ?? 'N/A') . "\n";
        echo "Department: " . ($user['department'][0] ?? 'N/A') . "\n";
        echo "Title: " . ($user['title'][0] ?? 'N/A') . "\n";
        echo "Phone: " . ($user['telephonenumber'][0] ?? 'N/A') . "\n";
        echo "Mobile: " . ($user['mobile'][0] ?? 'N/A') . "\n";
        echo "Office: " . ($user['physicaldeliveryofficename'][0] ?? 'N/A') . "\n";
        echo "User Account Control: " . ($user['useraccountcontrol'][0] ?? 'N/A') . "\n";

        // Member Of (groups)
        echo "\nMember Of (Groups):\n";
        if (isset($user['memberof'])) {
            for ($j = 0; $j < $user['memberof']['count']; $j++) {
                echo "  - " . $user['memberof'][$j] . "\n";
            }
        } else {
            echo "  (No groups)\n";
        }
    }

    echo "\n=== RAW DATA (for debugging) ===\n";
    print_r($info);

} else {
    echo "⚠ No users found matching 'kismm'\n";
    echo "\nPossible reasons:\n";
    echo "- Username is incorrect\n";
    echo "- User is disabled (useraccountcontrol = 66050)\n";
    echo "- User is in 'NotListedPhoneBook' group\n";
}

ldap_close($con);
echo "\n✓ Connection closed\n";
echo "</pre>";
?>
