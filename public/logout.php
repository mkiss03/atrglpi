<?php
/**
 * Logout
 * ÁTR Beragadt Betegek - Admin Logout
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Admin.php';

startSession();

// Logout
Admin::logout();

setFlashMessage('success', 'Sikeres kijelentkezés!');
redirect('index.php');
