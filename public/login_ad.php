<?php
/**
 * AD Login Page (First Tier Authentication)
 * ÁTR Beragadt Betegek - Active Directory Authentication
 *
 * This is the FIRST login screen - all users must authenticate via AD here.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ad-auth.php';

session_start();

// If already AD authenticated, redirect to main page
if (isset($_SESSION['ad_user'])) {
    redirect('index.php');
}

// Handle AD login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        setFlashMessage('error', 'Kérlek add meg a felhasználónevet és jelszót.');
    } else {
        // Authenticate against Active Directory
        $adUserData = authenticateAD($username, $password);

        if ($adUserData) {
            // Store AD user data in session
            $_SESSION['ad_user'] = [
                'samaccountname' => $adUserData['username'],
                'displayname'    => $adUserData['display_name'],
                'department'     => $adUserData['department'] ?? '',
                'mail'           => $adUserData['email'] ?? '',
                'dn'             => $adUserData['dn'] ?? '',
            ];

            // Also set legacy session variables for compatibility
            $_SESSION['ad_authenticated'] = true;
            $_SESSION['ad_username'] = $adUserData['username'];
            $_SESSION['ad_display_name'] = $adUserData['display_name'];
            $_SESSION['ad_email'] = $adUserData['email'] ?? '';

            $displayName = $adUserData['display_name'] ?? $username;
            setFlashMessage('success', "Sikeres bejelentkezés, $displayName!");

            // Redirect to originally requested page or index
            $redirectTo = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirectTo);
        } else {
            setFlashMessage('error', 'Hibás AD felhasználónév vagy jelszó.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés – ÁTR Beragadt betegek</title>

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
                                <i class="bi bi-hospital"></i>
                                <h3 class="mt-3 mb-2">ÁTR – Beragadt betegek</h3>
                                <p class="text-muted">Bejelentkezés (AD azonosítóval)</p>
                            </div>

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

                            <!-- AD Login Form -->
                            <form method="POST" action="login_ad.php">
                                <div class="mb-4">
                                    <label for="username" class="form-label">AD Felhasználónév</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="username"
                                            name="username"
                                            placeholder="pl. kissj"
                                            required
                                            autofocus
                                        >
                                    </div>
                                    <small class="form-text text-muted">
                                        Csak a felhasználónév, domain nélkül (pl. "kissj", nem "kissj@kmok.local")
                                    </small>
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label">Jelszó</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input
                                            type="password"
                                            class="form-control"
                                            id="password"
                                            name="password"
                                            placeholder="AD jelszó"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="d-grid gap-2 mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-box-arrow-in-right"></i> Bejelentkezés
                                    </button>
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
