<?php
/**
 * Admin Settings
 * ÁTR Beragadt Betegek - Admin Management (Admin Only)
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
    redirect('index.php');
}

$admin = new Admin();

// Handle create admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $displayName = trim($_POST['display_name'] ?? '');

    if (empty($username) || empty($password) || empty($displayName)) {
        setFlashMessage('error', 'Minden mező kitöltése kötelező.');
    } elseif (strlen($password) < 6) {
        setFlashMessage('error', 'A jelszónak legalább 6 karakter hosszúnak kell lennie.');
    } else {
        $data = [
            'username' => $username,
            'password' => $password,
            'display_name' => $displayName,
        ];

        $result = $admin->create($data);

        if ($result) {
            setFlashMessage('success', 'Admin felhasználó sikeresen létrehozva!');
            redirect('admin.php');
        } else {
            setFlashMessage('error', 'Hiba történt a felhasználó létrehozása során. A felhasználónév lehet foglalt.');
        }
    }
}

// Handle delete admin
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    if ($admin->delete($id)) {
        setFlashMessage('success', 'Admin felhasználó sikeresen törölve!');
    } else {
        setFlashMessage('error', 'Hiba történt a törlés során. Nem törölheted saját magadat.');
    }

    redirect('admin.php');
}

// Get all admins
$admins = $admin->getAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Rögzítés</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Admin beállítások</li>
                    </ol>
                </nav>
                <h1 class="page-title">
                    <i class="bi bi-shield-check"></i> Admin beállítások
                </h1>
                <p class="page-subtitle">Admin felhasználók kezelése és rendszer beállítások.</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Admin Users List -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-people"></i> Admin felhasználók
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Felhasználónév</th>
                                    <th>Megjelenített név</th>
                                    <th>Létrehozva</th>
                                    <th>Műveletek</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $adminUser): ?>
                                <tr>
                                    <td><?= e($adminUser['id']) ?></td>
                                    <td><code><?= e($adminUser['username']) ?></code></td>
                                    <td><?= e($adminUser['display_name']) ?></td>
                                    <td><?= formatDateTime($adminUser['created_at']) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="change-password.php?id=<?= $adminUser['id'] ?>"
                                               class="btn btn-sm btn-warning"
                                               title="Jelszó módosítása">
                                                <i class="bi bi-key"></i>
                                            </a>
                                            <?php if (getCurrentAdmin()['id'] !== $adminUser['id']): ?>
                                            <a href="admin.php?action=delete&id=<?= $adminUser['id'] ?>"
                                               class="btn btn-sm btn-danger btn-delete"
                                               title="Törlés">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php else: ?>
                                            <span class="badge bg-primary">Te vagy</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Rendszer információk
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">PHP verzió:</dt>
                        <dd class="col-sm-8"><code><?= phpversion() ?></code></dd>

                        <dt class="col-sm-4">Szerver szoftver:</dt>
                        <dd class="col-sm-8"><code><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></code></dd>

                        <dt class="col-sm-4">Adatbázis:</dt>
                        <dd class="col-sm-8"><code><?= DB_NAME ?></code></dd>

                        <dt class="col-sm-4">Alkalmazás verzió:</dt>
                        <dd class="col-sm-8"><code>v1.0.0</code></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Create New Admin -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person-plus"></i> Új admin létrehozása
                </div>
                <div class="card-body">
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="create">

                        <div class="mb-3">
                            <label for="username" class="form-label">Felhasználónév <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="username"
                                name="username"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="display_name" class="form-label">Megjelenített név <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="display_name"
                                name="display_name"
                                placeholder="pl. Dr. Kovács Anna"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Jelszó <span class="text-danger">*</span></label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                minlength="6"
                                required
                            >
                            <div class="form-text">Minimum 6 karakter</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Admin létrehozása
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Gyors statisztikák
                </div>
                <div class="card-body">
                    <?php
                    $atrRecord = new \AtrRecord();
                    $totalRecords = $atrRecord->getTotalCount();
                    ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <div class="text-muted">Összes rekord</div>
                            <h3 class="mb-0"><?= number_format($totalRecords, 0, ',', ' ') ?></h3>
                        </div>
                        <i class="bi bi-database text-primary" style="font-size: 2rem;"></i>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted">Admin felhasználók</div>
                            <h3 class="mb-0"><?= count($admins) ?></h3>
                        </div>
                        <i class="bi bi-people text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
