<?php
/**
 * Header Template
 * ÁTR Beragadt Betegek
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

startSession();
$currentAdmin = getCurrentAdmin();
$isAdminUser = isAdmin();
$currentAuthUser = getCurrentAuthUser(); // Get AD user info

// Get current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ÁTR – Beragadt betegek</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Select2 CSS (for searchable dropdown) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-icon">
                        <i class="bi bi-hospital"></i>
                    </div>
                    <div class="logo-text">
                        <h5 class="mb-0">ÁTR – Beragadt betegek</h5>
                        <small>Beteg adatrögzítő és export felület</small>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                            <i class="bi bi-plus-circle"></i>
                            <span>Rögzítés</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="list.php" class="nav-link <?= $currentPage === 'list' ? 'active' : '' ?>">
                            <i class="bi bi-list-ul"></i>
                            <span>Lista / Áttekintés</span>
                        </a>
                    </li>

                    <?php if ($isAdminUser): ?>
                    <li class="nav-item">
                        <a href="export.php" class="nav-link <?= $currentPage === 'export' ? 'active' : '' ?>">
                            <i class="bi bi-file-earmark-excel"></i>
                            <span>Exportok</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($isAdminUser): ?>
                    <li class="nav-item">
                        <a href="admin.php" class="nav-link <?= $currentPage === 'admin' ? 'active' : '' ?>">
                            <i class="bi bi-gear"></i>
                            <span>Admin beállítások</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <li class="nav-divider"></li>

                    <?php if (!$isAdminUser && $currentAuthUser && strcasecmp($currentAuthUser['department'] ?? '', 'Informatikai osztály') === 0): ?>
                    <!-- Show Admin Login only for Informatikai osztály users who are not yet admin -->
                    <li class="nav-item">
                        <a href="admin_login.php" class="nav-link <?= $currentPage === 'admin_login' ? 'active' : '' ?>">
                            <i class="bi bi-shield-lock"></i>
                            <span>Admin bejelentkezés</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Logout is always visible for authenticated users -->
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Kijelentkezés</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Header Bar -->
            <header class="top-header">
                <div class="header-left">
                    <!-- Breadcrumb will be inserted here by pages -->
                    <div id="breadcrumb-container"></div>
                </div>

                <div class="header-right">
                    <?php if ($isAdminUser): ?>
                        <!-- Admin user (both AD and Admin authenticated) -->
                        <span class="badge bg-success me-2">
                            <i class="bi bi-shield-check"></i> Admin
                        </span>
                        <div class="user-info">
                            <i class="bi bi-person-circle"></i>
                            <span class="user-name"><?= e($currentAuthUser['display_name'] ?? $currentAdmin['display_name']) ?></span>
                        </div>
                    <?php elseif ($currentAuthUser): ?>
                        <!-- AD user (normal access) -->
                        <span class="badge bg-secondary me-2">
                            <i class="bi bi-person"></i> Normál
                        </span>
                        <div class="user-info">
                            <i class="bi bi-person-circle"></i>
                            <span class="user-name"><?= e($currentAuthUser['display_name']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Flash Messages -->
            <?php
            $flashMessage = getFlashMessage();
            if ($flashMessage):
                $alertType = 'alert-info';
                switch ($flashMessage['type']) {
                    case 'success':
                        $alertType = 'alert-success';
                        break;
                    case 'error':
                        $alertType = 'alert-danger';
                        break;
                    case 'warning':
                        $alertType = 'alert-warning';
                        break;
                }
            ?>
            <div class="container-fluid mt-3">
                <div class="alert <?= $alertType ?> alert-dismissible fade show" role="alert">
                    <?= e($flashMessage['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Content Area -->
            <main class="content-area">
