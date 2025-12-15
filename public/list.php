<?php
/**
 * Lista / Áttekintés (List View)
 * ÁTR Beragadt Betegek - View All Records
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log file for debugging
$logFile = __DIR__ . '/../logs/debug.log';
@mkdir(dirname($logFile), 0755, true);

function debugLog($message) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

debugLog("=== LIST.PHP START ===");
debugLog("GET params: " . print_r($_GET, true));

try {
    debugLog("Loading dependencies...");
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../models/AtrRecord.php';
    debugLog("Dependencies loaded successfully");

    // Require AD authentication
    debugLog("Checking authentication...");
    requireAdLogin();
    debugLog("Authentication OK");
} catch (Exception $e) {
    debugLog("EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    die("Error during initialization: " . $e->getMessage() . "<br>File: " . $e->getFile() . "<br>Line: " . $e->getLine());
}

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
debugLog("Setting pagination parameters...");
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
debugLog("Page: $page, PerPage: $perPage, Search: '$search'");

try {
    // Get records
    debugLog("Creating AtrRecord instance...");
    $atrRecord = new AtrRecord();

    debugLog("Fetching records...");
    $records = $atrRecord->getAll($page, $perPage, $search);
    debugLog("Records fetched: " . count($records));

    debugLog("Getting total count...");
    $totalCount = $atrRecord->getTotalCount($search);
    debugLog("Total count: $totalCount");

    $totalPages = ceil($totalCount / $perPage);

    // Get dismissing types for display
    debugLog("Getting dismissing types...");
    $dismissingTypes = AtrRecord::getDismissingTypes();

    // Load osztaly data for search dropdown
    debugLog("Loading osztaly data...");
    $osztalyData = loadOsztalyData();
    debugLog("Osztaly data loaded: " . count($osztalyData) . " items");
} catch (Exception $e) {
    debugLog("EXCEPTION in data loading: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    die("Error loading data: " . $e->getMessage() . "<br>File: " . $e->getFile() . "<br>Line: " . $e->getLine() . "<br>Search: " . htmlspecialchars($search));
}

debugLog("Including header...");
debugLog("=== LIST.PHP DATA LOADED SUCCESSFULLY ===");

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
                <select
                    class="form-select"
                    id="search"
                    name="search"
                >
                    <option value="">Kezdj el gépelni osztálykódra vagy névre...</option>
                    <?php foreach ($osztalyData as $osztaly): ?>
                        <option value="<?= e($osztaly['nngyk_kod']) ?>"
                                <?= $search === $osztaly['nngyk_kod'] ? 'selected' : '' ?>
                                data-medsol="<?= e($osztaly['medsol_kod']) ?>">
                            <?= e($osztaly['medsol_kod']) ?> –
                            <?= e($osztaly['osztaly_nev']) ?>
                            (NNGYK: <?= e($osztaly['nngyk_kod']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
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
