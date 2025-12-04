<?php
/**
 * Logout
 * ÁTR Beragadt Betegek - Admin Logout
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Clear session and redirect to login
session_start();
setFlashMessage('success', 'Sikeres kijelentkezés!');
logoutUser('login.php');
