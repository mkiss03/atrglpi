<?php
/**
 * Edit Record (Admin Only)
 * ÁTR Beragadt Betegek - Edit Existing Record
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/AtrRecord.php';

startSession();

// Only admins can edit
if (!isAdmin()) {
    setFlashMessage('error', 'Nincs jogosultságod ehhez a művelethez.');
    redirect('list.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setFlashMessage('error', 'Érvénytelen rekord azonosító.');
    redirect('list.php');
}

$atrRecord = new AtrRecord();
$record = $atrRecord->getById($id);

if (!$record) {
    setFlashMessage('error', 'A rekord nem található.');
    redirect('list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validateAtrRecord($_POST);

    if (empty($errors)) {
        try {
            // Convert datetime-local format to MySQL datetime
            $tavido = str_replace('T', ' ', $_POST['tavido']);

            $data = [
                'osztaly' => $_POST['osztaly'],
                'tavido' => $tavido,
                'atr_dismissing_type' => $_POST['atr_dismissing_type'],
                'atr_nursing_cycle_id' => trim($_POST['atr_nursing_cycle_id']),
                'atr_nursing_cycle_data_id' => trim($_POST['atr_nursing_cycle_data_id']),
            ];

            if ($atrRecord->update($id, $data)) {
                setFlashMessage('success', 'Rekord sikeresen módosítva!');
                redirect('list.php');
            } else {
                setFlashMessage('error', 'Hiba történt a módosítás során.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Hiba történt: ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $error) {
            setFlashMessage('error', $error);
            break;
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
                        <li class="breadcrumb-item"><a href="index.php">Rögzítés</a></li>
                        <li class="breadcrumb-item"><a href="list.php">Lista</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Szerkesztés</li>
                    </ol>
                </nav>
                <h1 class="page-title">Rekord szerkesztése (ID: <?= e($record['id']) ?>)</h1>
                <p class="page-subtitle">Módosítsd a rekord adatait.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="edit.php?id=<?= $id ?>" id="atrForm">
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
                                value="<?= e($record['intezmeny']) ?>"
                                readonly
                                disabled
                            >
                            <div class="form-text">
                                Az intézmény kódja nem módosítható.
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
                                <option value="">Válassz osztályt...</option>
                                <?php foreach ($osztalyData as $osztaly): ?>
                                    <option value="<?= e($osztaly['nngyk_kod']) ?>"
                                            <?= $record['osztaly'] === $osztaly['nngyk_kod'] ? 'selected' : '' ?>>
                                        <?= e($osztaly['medsol_kod']) ?> –
                                        <?= e($osztaly['osztaly_nev']) ?>
                                        (NNGYK: <?= e($osztaly['nngyk_kod']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                                value="<?= formatDateTimeLocal($record['tavido']) ?>"
                                required
                            >
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
                                    <option value="<?= e($key) ?>" <?= $record['atr_dismissing_type'] === $key ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
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
                                value="<?= e($record['atr_nursing_cycle_id']) ?>"
                                required
                            >
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
                                value="<?= e($record['atr_nursing_cycle_data_id']) ?>"
                                required
                            >
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="list.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Vissza a listához
                            </a>
                            <button type="submit" class="btn btn-primary btn-icon">
                                <i class="bi bi-save"></i> Mentés
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right sidebar info -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-info-circle"></i> Rekord adatai
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6">ID:</dt>
                            <dd class="col-sm-6"><?= e($record['id']) ?></dd>

                            <dt class="col-sm-6">Létrehozva:</dt>
                            <dd class="col-sm-6"><?= formatDateTime($record['created_at']) ?></dd>

                            <dt class="col-sm-6">IP cím:</dt>
                            <dd class="col-sm-6"><code><?= e($record['created_ip']) ?></code></dd>

                            <?php if ($record['creator_name']): ?>
                            <dt class="col-sm-6">Létrehozta:</dt>
                            <dd class="col-sm-6"><?= e($record['creator_name']) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
