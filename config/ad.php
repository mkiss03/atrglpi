<?php
/**
 * Active Directory Configuration
 * ÁTR Beragadt Betegek - AD Authentication Settings
 */

return [
    // AD Server
    'host' => '10.1.0.16',
    'port' => 389, // Use 636 for LDAPS
    'use_tls' => true, // Recommended even with internal network

    // Base DN
    'base_dn' => 'dc=kmok,dc=local',

    // Account suffix (domain)
    'account_suffix' => '@kmok.local',

    // Service account for LDAP bind (to search for users)
    'service_user' => 'telefonkonyv',
    'service_pass' => 'Book1234!',

    // Admin group DN (users in this group get admin privileges)
    'admin_group_dn' => 'CN=Informatikai Osztály,OU=Groups,DC=kmok,DC=local',

    // User search base (where to search for users)
    'user_search_base' => 'DC=kmok,DC=local',

    // User search filter
    'user_filter' => '(&(objectClass=user)(sAMAccountName={username}))',
];
