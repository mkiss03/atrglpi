<?php
/**
 * Helper Functions
 * ÁTR Beragadt Betegek - Utility Functions
 */

/**
 * Load osztaly data from CSV
 * CSV structure: mskod;neak;nngyk;szakmakod;oszt_tipus;nev
 * @return array
 */
function loadOsztalyData() {
    $csvFile = __DIR__ . '/../data/osztaly.csv';

    if (!file_exists($csvFile)) {
        return [];
    }

    $osztalyData = [];

    if (($handle = fopen($csvFile, 'r')) !== false) {
        // Read header row
        $header = fgetcsv($handle, 1000, ';');

        // Read data rows
        while (($row = fgetcsv($handle, 1000, ';')) !== false) {
            if (count($row) >= 6) {
                // Row structure: mskod, neak, nngyk, szakmakod, oszt_tipus, nev
                $osztalyData[] = [
                    'medsol_kod' => trim($row[0]),           // mskod (pl. BE08)
                    'osztaly_nev' => trim($row[5], ' "'),   // nev (remove quotes)
                    'nngyk_kod' => trim($row[2]),           // nngyk (9 char code)
                ];
            }
        }

        fclose($handle);
    }

    return $osztalyData;
}

/**
 * Format datetime for display
 * @param string $datetime
 * @return string
 */
function formatDateTime($datetime) {
    if (empty($datetime)) {
        return '';
    }

    $dt = new DateTime($datetime);
    return $dt->format('Y.m.d H:i');
}

/**
 * Format date for datetime-local input
 * @param string $datetime
 * @return string
 */
function formatDateTimeLocal($datetime) {
    if (empty($datetime)) {
        return '';
    }

    $dt = new DateTime($datetime);
    return $dt->format('Y-m-d\TH:i');
}

/**
 * Validate ATR record data
 * @param array $data
 * @return array Errors array (empty if valid)
 */
function validateAtrRecord($data) {
    $errors = [];

    if (empty($data['osztaly'])) {
        $errors[] = 'Az osztály mező kitöltése kötelező.';
    }

    if (empty($data['tavido'])) {
        $errors[] = 'A távozási idő mező kitöltése kötelező.';
    }

    if (empty($data['atr_dismissing_type'])) {
        $errors[] = 'Az elbocsátás módja mező kitöltése kötelező.';
    }

    if (empty($data['atr_nursing_cycle_id'])) {
        $errors[] = 'Az ÁTR ápolási ciklus azonosító mező kitöltése kötelező.';
    }

    if (empty($data['atr_nursing_cycle_data_id'])) {
        $errors[] = 'Az ÁTR ápolási ciklus adat azonosító mező kitöltése kötelező.';
    }

    return $errors;
}

/**
 * Export data to CSV
 * @param array $data
 * @param string $filename
 */
function exportToCSV($data, $filename = 'atr_export.csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Add BOM for proper UTF-8 encoding in Excel
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // Write header
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]), ';');
    }

    // Write data rows
    foreach ($data as $row) {
        fputcsv($output, $row, ';');
    }

    fclose($output);
    exit;
}

/**
 * Generate breadcrumb
 * @param array $items
 * @return string HTML
 */
function breadcrumb($items) {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';

    $count = count($items);
    $i = 0;

    foreach ($items as $label => $url) {
        $i++;
        if ($i === $count) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . e($label) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . e($url) . '">' . e($label) . '</a></li>';
        }
    }

    $html .= '</ol></nav>';
    return $html;
}

/**
 * Get current page URL
 * @return string
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get base URL
 * @return string
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);

    return $protocol . '://' . $host . $script;
}
