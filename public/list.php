<?php
/**
 * Lista / Áttekintés (List View)
 * ÁTR Beragadt Betegek - View All Records
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/AtrRecord.php';

// Require AD authentication
requireAdLogin();

$isAdminUser = isAdmin();

// Handle delete action (admin only)
if ($isAdminUser && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $atrRecord = new AtrRecord();
    $id = (int)$_GET['id'];

    if ($atrRecord->delete($id)) {
        setFlashMessage('success', 'Rekord sikeresen törölve!');
    } else {
        setFlashMessage('error', 'Hiba történt a törlés során.');
    }

    redirect('list.php');
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get records
$atrRecord = new AtrRecord();
$records = $atrRecord->getAll($page, $perPage, $search);
$totalCount = $atrRecord->getTotalCount($search);
$totalPages = ceil($totalCount / $perPage);

// Get dismissing types for display
$dismissingTypes = AtrRecord::getDismissingTypes();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Rögzítés</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Lista / Áttekintés</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">Rögzített rekordok</h1>
                        <p class="page-subtitle">Összesen <?= $totalCount ?> rekord</p>
                    </div>
                    <?php if ($isAdminUser): ?>
                    <div>
                        <a href="export.php" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Excel export
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="row mb-3">
        <div class="col-lg-6">
            <form method="GET" action="list.php" class="d-flex gap-2">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Keresés osztály, azonosítók alapján..."
                    value="<?= e($search) ?>"
                >
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Keresés
                </button>
                <?php if ($search): ?>
                <a href="list.php" class="btn btn-secondary">
                    <i class="bi bi-x"></i> Törlés
                </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Records Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>INTEZMENY</th>
                                    <th>OSZTALY</th>
                                    <th>TAVIDO</th>
                                    <th>ATR_DISMISSING_TYPE</th>
                                    <th>ATR_NURSING_CYCLE_ID</th>
                                    <th>ATR_NURSING_CYCLE_DATA_ID</th>
                                    <?php if ($isAdminUser): ?>
                                    <th>CREATED_IP</th>
                                    <th>CREATED_AT</th>
                                    <th>Létrehozta</th>
                                    <th>Műveletek</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="<?= $isAdminUser ? '11' : '7' ?>" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                        <p class="text-muted mt-2 mb-0">
                                            <?= $search ? 'Nincs találat a keresésre.' : 'Még nincs rögzített rekord.' ?>
                                        </p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td><?= e($record['id']) ?></td>
                                        <td><?= e($record['intezmeny']) ?></td>
                                        <td><?= e($record['osztaly']) ?></td>
                                        <td><?= formatDateTime($record['tavido']) ?></td>
                                        <td>
                                            <small><?= e($dismissingTypes[$record['atr_dismissing_type']] ?? $record['atr_dismissing_type']) ?></small>
                                        </td>
                                        <td><code><?= e($record['atr_nursing_cycle_id']) ?></code></td>
                                        <td><code><?= e($record['atr_nursing_cycle_data_id']) ?></code></td>
                                        <?php if ($isAdminUser): ?>
                                        <td><?= e($record['created_ip']) ?></td>
                                        <td><?= formatDateTime($record['created_at']) ?></td>
                                        <td><?= $record['creator_name'] ? e($record['creator_name']) : '<span class="text-muted">-</span>' ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-primary" title="Szerkesztés">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="list.php?action=delete&id=<?= $record['id'] ?>"
                                                   class="btn btn-sm btn-danger btn-delete"
                                                   title="Törlés">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                            <i class="bi bi-chevron-left"></i> Előző
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                            Következő <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
