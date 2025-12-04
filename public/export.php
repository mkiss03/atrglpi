<?php
/**
 * Export to Excel/CSV
 * ÁTR Beragadt Betegek - Export Data
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/AtrRecord.php';

// Require AD authentication
requireAdLogin();

// Only admins can access export
if (!isAdmin()) {
    setFlashMessage('error', 'Nincs jogosultságod az export funkcióhoz. Kérlek jelentkezz be admin felhasználóként.');
    redirect('login.php');
}

// Handle download action
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $atrRecord = new AtrRecord();
    $data = $atrRecord->getAllForExport();

    if (empty($data)) {
        setFlashMessage('warning', 'Nincs exportálható adat.');
        redirect('export.php');
    }

    $filename = 'atr_export_' . date('Y-m-d_His') . '.csv';
    exportToCSV($data, $filename);
    exit;
}

// Get record count for display
$atrRecord = new AtrRecord();
$totalCount = $atrRecord->getTotalCount();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Rögzítés</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Exportok</li>
                    </ol>
                </nav>
                <h1 class="page-title">Excel / CSV Exportok</h1>
                <p class="page-subtitle">Exportáld az ÁTR beragadt betegek adatait Excel formátumban.</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-file-earmark-excel"></i> Excel Export
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="mb-3">ÁTR Beragadt Betegek - Teljes Export</h5>
                            <p class="text-muted mb-3">
                                Az exportált fájl <strong>csak a 6 kötelező oszlopot</strong> tartalmazza:
                            </p>
                            <ul class="mb-4">
                                <li><code>INTEZMENY</code></li>
                                <li><code>OSZTALY</code></li>
                                <li><code>TAVIDO</code></li>
                                <li><code>ATR_DISMISSING_TYPE</code></li>
                                <li><code>ATR_NURSING_CYCLE_ID</code></li>
                                <li><code>ATR_NURSING_CYCLE_DATA_ID</code></li>
                            </ul>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Fontos:</strong> Az <code>created_ip</code> és <code>created_at</code> mezők
                                <strong>NEM</strong> kerülnek bele az exportba. Ezek csak az adatbázisban és az admin
                                listanézetben láthatók.
                            </div>

                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="badge bg-primary" style="font-size: 1.5rem; padding: 1rem;">
                                    <i class="bi bi-database"></i> <?= number_format($totalCount, 0, ',', ' ') ?>
                                </div>
                                <div>
                                    <div class="text-muted">Összes rekord</div>
                                    <small class="text-muted">Az exportálható rekordok száma</small>
                                </div>
                            </div>

                            <?php if ($totalCount > 0): ?>
                            <a href="export.php?action=download" class="btn btn-success btn-lg">
                                <i class="bi bi-download"></i> Excel Export Letöltése
                            </a>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-lg" disabled>
                                <i class="bi bi-x-circle"></i> Nincs exportálható adat
                            </button>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="bi bi-file-earmark-spreadsheet text-success" style="font-size: 8rem; opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-gear"></i> Export beállítások
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Fájl formátum:</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="format" id="formatCSV" checked disabled>
                        <label class="form-check-label" for="formatCSV">
                            <strong>CSV (UTF-8 BOM)</strong> – Excel-kompatibilis, pontosvesszővel elválasztott
                        </label>
                    </div>

                    <hr class="my-4">

                    <h6 class="mb-3">Egyéb információk:</h6>
                    <ul class="text-muted mb-0">
                        <li>Az export automatikusan UTF-8 BOM karakterkódolást használ az Excel kompatibilitás érdekében.</li>
                        <li>A dátumok <code>ÉÉÉÉ.MM.NN ÓÓ:PP</code> formátumban jelennek meg.</li>
                        <li>Az export fájlnév tartalmazza a letöltés időpontját.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Használati útmutató
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Hogyan használd az exportot?</h6>
                    <ol class="mb-3">
                        <li class="mb-2">Kattints az <strong>"Excel Export Letöltése"</strong> gombra.</li>
                        <li class="mb-2">A böngésző automatikusan letölti a CSV fájlt.</li>
                        <li class="mb-2">Nyisd meg a fájlt <strong>Microsoft Excel</strong>-ben vagy más táblázatkezelőben.</li>
                        <li class="mb-2">Az adatok automatikusan megfelelő formátumban jelennek meg.</li>
                    </ol>

                    <div class="alert alert-warning">
                        <small>
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Figyelem:</strong> Ha az Excel nem jeleníti meg helyesen a magyar ékezetes
                            karaktereket, használd a "Adatok → Szövegből" importálást UTF-8 kódolással.
                        </small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-lightbulb"></i> Gyors hivatkozások
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Új rekord rögzítése
                        </a>
                        <a href="list.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-list-ul"></i> Rögzített rekordok
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
