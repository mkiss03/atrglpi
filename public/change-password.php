<?php
/**
 * Change Password
 * ÁTR Beragadt Betegek - Admin Password Change
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Admin.php';

// Require AD authentication
requireAdLogin();

// Only admins can access
if (!isAdmin()) {
    setFlashMessage('error', 'Nincs jogosultságod ehhez az oldalhoz.');
    redirect('login.php');
}

$adminModel = new Admin();
$currentUser = getCurrentAdmin();

// Get target admin ID
$targetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($targetId <= 0) {
    setFlashMessage('error', 'Érvénytelen admin azonosító.');
    redirect('admin.php');
}

$targetAdmin = $adminModel->getById($targetId);

if (!$targetAdmin) {
    setFlashMessage('error', 'Az admin felhasználó nem található.');
    redirect('admin.php');
}

// Check if changing own password
$isOwnPassword = ($currentUser['id'] == $targetId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate current password (only if changing own password)
    if ($isOwnPassword && empty($currentPassword)) {
        $errors[] = 'A jelenlegi jelszó megadása kötelező.';
    }

    // Validate new password
    if (empty($newPassword)) {
        $errors[] = 'Az új jelszó megadása kötelező.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'Az új jelszónak legalább 8 karakter hosszúnak kell lennie.';
    }

    // Validate password confirmation
    if (empty($confirmPassword)) {
        $errors[] = 'Az új jelszó megerősítése kötelező.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'Az új jelszó és a megerősítés nem egyezik.';
    }

    if (empty($errors)) {
        // Change password
        $result = $adminModel->changePassword(
            $targetId,
            $newPassword,
            $isOwnPassword ? $currentPassword : null
        );

        if ($result) {
            setFlashMessage('success', 'A jelszó sikeresen megváltoztatva!');
            redirect('admin.php');
        } else {
            if ($isOwnPassword) {
                setFlashMessage('error', 'Hiba történt: Hibás jelenlegi jelszó vagy adatbázis hiba.');
            } else {
                setFlashMessage('error', 'Hiba történt a jelszó módosítása során.');
            }
        }
    } else {
        foreach ($errors as $error) {
            setFlashMessage('error', $error);
            break; // Show only first error
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Rögzítés</a></li>
                        <li class="breadcrumb-item"><a href="admin.php">Admin beállítások</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Jelszó módosítása</li>
                    </ol>
                </nav>
                <h1 class="page-title">
                    <i class="bi bi-key"></i> Jelszó módosítása
                </h1>
                <p class="page-subtitle">
                    Admin: <strong><?= e($targetAdmin['display_name']) ?></strong>
                    (<?= e($targetAdmin['username']) ?>)
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-lock"></i> Új jelszó beállítása
                </div>
                <div class="card-body">
                    <form method="POST" action="change-password.php?id=<?= $targetId ?>">
                        <?php if ($isOwnPassword): ?>
                        <!-- Current Password (only for own password) -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                Jelenlegi jelszó <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="current_password"
                                    name="current_password"
                                    placeholder="Jelenlegi jelszó"
                                    required
                                >
                            </div>
                            <div class="form-text">
                                Biztonsági okokból add meg a jelenlegi jelszavadat.
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                Új jelszó <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-key"></i>
                                </span>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="new_password"
                                    name="new_password"
                                    placeholder="Új jelszó"
                                    minlength="8"
                                    required
                                >
                            </div>
                            <div class="form-text">
                                Legalább 8 karakter hosszú legyen.
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                Új jelszó megerősítése <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-key"></i>
                                </span>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="confirm_password"
                                    name="confirm_password"
                                    placeholder="Új jelszó megerősítése"
                                    minlength="8"
                                    required
                                >
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="admin.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Vissza
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Jelszó módosítása
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Tudnivalók
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Jelszó követelmények:</h6>
                    <ul class="mb-3">
                        <li>Minimum 8 karakter hosszú</li>
                        <li>Az új jelszónak meg kell egyeznie a megerősítéssel</li>
                        <?php if ($isOwnPassword): ?>
                        <li>A jelenlegi jelszó megadása kötelező</li>
                        <?php endif; ?>
                    </ul>

                    <?php if ($isOwnPassword): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Fontos:</strong> A saját jelszavad módosítod. Sikeres módosítás után
                        újra be kell jelentkezned az új jelszóval.
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Megjegyzés:</strong> Egy másik admin felhasználó jelszavát módosítod.
                        Az érintett admin az új jelszóval tud belépni.
                    </div>
                    <?php endif; ?>

                    <hr>

                    <h6 class="mb-2">Biztonság:</h6>
                    <p class="text-muted mb-0">
                        <small>
                            A jelszavak biztonságosan vannak tárolva bcrypt hash algoritmussal.
                            A régi jelszó nem látható és nem visszaállítható.
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
