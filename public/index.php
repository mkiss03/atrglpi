<?php
/**
 * Rögzítés (Main Form Page)
 * ÁTR Beragadt Betegek - Create New Record
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/AtrRecord.php';

startSession();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token (basic implementation)
    $errors = validateAtrRecord($_POST);

    if (empty($errors)) {
        try {
            $atrRecord = new AtrRecord();

            // Convert datetime-local format to MySQL datetime
            $tavido = str_replace('T', ' ', $_POST['tavido']);

            $data = [
                'intezmeny' => '140100', // Fixed value
                'osztaly' => $_POST['osztaly'],
                'tavido' => $tavido,
                'atr_dismissing_type' => $_POST['atr_dismissing_type'],
                'atr_nursing_cycle_id' => trim($_POST['atr_nursing_cycle_id']),
                'atr_nursing_cycle_data_id' => trim($_POST['atr_nursing_cycle_data_id']),
                'created_ip' => getClientIp(),
                'created_by_admin_id' => getCurrentAdmin()['id'] ?? null,
            ];

            $id = $atrRecord->create($data);

            if ($id) {
                setFlashMessage('success', 'Rekord sikeresen rögzítve! (ID: ' . $id . ')');
                redirect('index.php');
            } else {
                setFlashMessage('error', 'Hiba történt a rekord mentése során.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Hiba történt: ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $error) {
            setFlashMessage('error', $error);
            break; // Show only first error
        }
    }
}

// Load osztaly data
$osztalyData = loadOsztalyData();

// Get dismissing types
$dismissingTypes = AtrRecord::getDismissingTypes();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active" aria-current="page">Rögzítés</li>
                        <li class="breadcrumb-item">Új rekord</li>
                    </ol>
                </nav>
                <h1 class="page-title">Alapadatok</h1>
                <p class="page-subtitle">Intézmény, osztály és távozási idő megadása.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="index.php" id="atrForm">
        <div class="row">
            <div class="col-lg-8">
                <!-- Alapadatok Card -->
                <div class="card">
                    <div class="card-header">
                        Alapadatok
                    </div>
                    <div class="card-body">
                        <!-- Intézmény (Read-only) -->
                        <div class="mb-3">
                            <label for="intezmeny" class="form-label">
                                Intézmény (INTEZMENY)
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="intezmeny"
                                value="NNK6_12345678"
                                readonly
                                disabled
                            >
                            <div class="form-text">
                                Automatikusan kitöltve, nem módosítható.
                            </div>
                        </div>

                        <!-- Osztály (Searchable Dropdown) -->
                        <div class="mb-3">
                            <label for="osztaly" class="form-label">
                                Osztály (OSZTALY – NNK9 kód) <span class="text-danger">*</span>
                            </label>
                            <select
                                class="form-select"
                                id="osztaly"
                                name="osztaly"
                                required
                            >
                                <option value="">Kezdj el gépelni osztálykódra vagy névre...</option>
                                <?php foreach ($osztalyData as $osztaly): ?>
                                    <option value="<?= e($osztaly['nngyk_kod']) ?>"
                                            data-medsol="<?= e($osztaly['medsol_kod']) ?>">
                                        <?= e($osztaly['medsol_kod']) ?> –
                                        <?= e($osztaly['osztaly_nev']) ?>
                                        (NNGYK: <?= e($osztaly['nngyk_kod']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Kereshető osztálykód és név alapján is.
                            </div>
                        </div>

                        <!-- Távozási idő -->
                        <div class="mb-3">
                            <label for="tavido" class="form-label">
                                Távozási idő (TAVIDO) <span class="text-danger">*</span>
                            </label>
                            <input
                                type="datetime-local"
                                class="form-control"
                                id="tavido"
                                name="tavido"
                                required
                            >
                            <div class="form-text">
                                A beteg HIS-ben rögzített távozási időpontja.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ÁTR adatok Card -->
                <div class="card">
                    <div class="card-header">
                        ÁTR adatok
                    </div>
                    <div class="card-body">
                        <!-- Elbocsátás módja -->
                        <div class="mb-3">
                            <label for="atr_dismissing_type" class="form-label">
                                Elbocsátás módja (ATR_DISMISSING_TYPE) <span class="text-danger">*</span>
                            </label>
                            <select
                                class="form-select"
                                id="atr_dismissing_type"
                                name="atr_dismissing_type"
                                required
                            >
                                <option value="">Válassz elbocsátás módot...</option>
                                <?php foreach ($dismissingTypes as $key => $label): ?>
                                    <option value="<?= e($key) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- ÁTR ápolási ciklus azonosító -->
                        <div class="mb-3">
                            <label for="atr_nursing_cycle_id" class="form-label">
                                ÁTR ápolási ellátás azonosító (ATR_NURSING_CYCLE_ID) <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="atr_nursing_cycle_id"
                                name="atr_nursing_cycle_id"
                                placeholder="4KRYCDMJAS6VRPMH"
                                required
                            >
                            <div class="form-text">
                                Az azonosítót a HIS rendszer adja meg.
                            </div>
                        </div>

                        <!-- ÁTR ápolási ciklus adat azonosító -->
                        <div class="mb-3">
                            <label for="atr_nursing_cycle_data_id" class="form-label">
                                ÁTR ápolási ellátás adat azonosító (ATR_NURSING_CYCLE_DATA_ID) <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="atr_nursing_cycle_data_id"
                                name="atr_nursing_cycle_data_id"
                                placeholder="Adat azonosító"
                                required
                            >
                            <div class="form-text">
                                A Dashboard felületen, a páciens adatlapján, a Betegápolósoknál (ÁTR) alatt található.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" id="resetForm">
                                <i class="bi bi-x-circle"></i> Mezők törlése
                            </button>
                            <button type="submit" class="btn btn-success btn-icon">
                                <i class="bi bi-plus-circle"></i> Hozzáadás
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right sidebar info -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-info-circle"></i> Információ
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Az ÁTR-hez kapcsolódó beragadt betegek adatainak rögzítése.</strong>
                        </p>
                        <hr>
                        <p class="mb-2 text-muted">
                            <small>
                                A rendszer automatikusan menti az IP címedet és a rögzítés időpontját.
                            </small>
                        </p>
                        <?php if (isAdmin()): ?>
                        <p class="mb-0 text-success">
                            <small>
                                <i class="bi bi-shield-check"></i>
                                Admin felhasználóként vagy bejelentkezve.
                            </small>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-lightbulb"></i> Gyors hivatkozások
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="list.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-list-ul"></i> Rögzített rekordok
                            </a>
                            <?php if (isAdmin()): ?>
                            <a href="export.php" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-file-earmark-excel"></i> Excel export
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
