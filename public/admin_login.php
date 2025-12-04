<?php
/**
 * Admin Login Page (Second Tier Authentication)
 * ÁTR Beragadt Betegek - Database Admin Authentication
 *
 * This is the SECOND login screen - only for "Informatikai osztály" users.
 * Users must be AD authenticated AND in the correct department to access this page.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Admin.php';

// Require AD authentication first
requireAdLogin();

// Check if user is in "Informatikai osztály" department
requireAdminAccess();

// If already admin authenticated, redirect to index
if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0) {
    redirect('index.php');
}

// Handle admin login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        setFlashMessage('error', 'Kérlek add meg az admin felhasználónevet és jelszót.');
    } else {
        // Authenticate against database (admins table)
        $admin = new Admin();

        if ($admin->login($username, $password)) {
            setFlashMessage('success', 'Sikeres admin bejelentkezés!');
            redirect('index.php');
        } else {
            setFlashMessage('error', 'Hibás admin felhasználónév vagy jelszó.');
        }
    }
}

$currentAdUser = getCurrentAuthUser();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Bejelentkezés – ÁTR Beragadt betegek</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card login-card shadow-lg">
                        <div class="card-body p-5">
                            <!-- Logo -->
                            <div class="login-logo">
                                <i class="bi bi-shield-lock"></i>
                                <h3 class="mt-3 mb-2">Admin Bejelentkezés</h3>
                                <p class="text-muted">Csak Informatikai osztály számára</p>
                            </div>

                            <!-- Current AD User Info -->
                            <?php if ($currentAdUser): ?>
                            <div class="alert alert-info mb-4">
                                <i class="bi bi-person-check"></i>
                                <strong>AD felhasználó:</strong> <?= e($currentAdUser['display_name']) ?><br>
                                <small>Osztály: <?= e($currentAdUser['department']) ?></small>
                            </div>
                            <?php endif; ?>

                            <!-- Flash Message -->
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
                            <div class="alert <?= $alertType ?> alert-dismissible fade show" role="alert">
                                <?= e($flashMessage['message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>

                            <!-- Admin Login Form -->
                            <form method="POST" action="admin_login.php">
                                <div class="mb-4">
                                    <label for="username" class="form-label">Admin Felhasználónév</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-person-badge"></i>
                                        </span>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="username"
                                            name="username"
                                            placeholder="Admin felhasználónév (DB)"
                                            required
                                            autofocus
                                        >
                                    </div>
                                    <small class="form-text text-muted">
                                        Ez a rendszer adminisztrációs fiókja (adatbázisban tárolt).
                                    </small>
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label">Admin Jelszó</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-key"></i>
                                        </span>
                                        <input
                                            type="password"
                                            class="form-control"
                                            id="password"
                                            name="password"
                                            placeholder="Admin jelszó"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mb-3">
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class="bi bi-shield-check"></i> Admin Bejelentkezés
                                    </button>
                                </div>

                                <div class="text-center">
                                    <a href="index.php" class="text-muted">
                                        <i class="bi bi-arrow-left"></i> Vissza a főoldalra
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <p class="text-white mb-0">
                            <small>&copy; <?= date('Y') ?> ÁTR Beragadt Betegek Nyilvántartó Rendszer</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
