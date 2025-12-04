<?php
/**
 * Logout
 * ÁTR Beragadt Betegek - Logout (clears both AD and Admin sessions)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Clear session and redirect to AD login
session_start();
setFlashMessage('success', 'Sikeres kijelentkezés!');
logoutUser('login_ad.php');
