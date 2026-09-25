<?php
/**
 * Import Burial Records API (CSV)
 * Accepts a CSV file (saved from Excel) and bulk-inserts burial records.
 * Expected headers (first row): decedent_name, family_name, birth_date, death_date,
 * burial_date, burial_time, expiration_date, plot_number, barangay, memory_space,
 * is_fenced, latitude, longitude
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['csv_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    echo json_encode(['success' => false, 'message' => 'Please upload a .csv file (in Excel: File > Save As > CSV)']);
    exit;
}
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
    exit;
}

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Could not read file']);
    exit;
}

// Read header row and map column positions
$header = fgetcsv($handle);
if (!$header) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'Empty CSV file']);
    exit;
}
// Strip UTF-8 BOM from first header cell if present
$header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
$header = array_map(function ($h) { return strtolower(trim($h)); }, $header);

$allowedCols = ['decedent_name','family_name','birth_date','death_date','burial_date',
                'burial_time','expiration_date','plot_number','barangay','memory_space','is_fenced',
                'latitude','longitude'];
$colMap = [];
foreach ($header as $i => $h) {
    if (in_array($h, $allowedCols, true)) {
        $colMap[$h] = $i;
    }
}

if (!isset($colMap['decedent_name'])) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'CSV must contain a "decedent_name" column header']);
    exit;
}

function csvVal($row, $colMap, $key) {
    if (!isset($colMap[$key])) return null;
    $v = trim($row[$colMap[$key]] ?? '');
    return $v === '' ? null : $v;
}
function csvDate($row, $colMap, $key) {
    $v = csvVal($row, $colMap, $key);
    if ($v === null) return null;
    $t = strtotime($v);
    if ($t === false) return null;
    $d = date('Y-m-d', $t);
    return $d === '1970-01-01' ? null : $d;
}
function csvTime($row, $colMap, $key) {
    $v = csvVal($row, $colMap, $key);
    if ($v === null) return null;
    $t = strtotime($v);
    return $t === false ? null : date('H:i:s', $t);
}

$imported = 0;
$skipped = 0;
$errors = [];
$rowNum = 1; // header is row 1

$insert = $pdo->prepare("
    INSERT INTO burial_records
    (decedent_name, family_name, birth_date, death_date, burial_date, burial_time, is_buried, expiration_date,
     plot_number, barangay, memory_space, is_fenced, latitude, longitude)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$pdo->beginTransaction();
try {
    while (($row = fgetcsv($handle)) !== false) {
        $rowNum++;
        // Skip fully empty rows
        if (count($row) === 1 && trim($row[0]) === '') continue;

        $name = csvVal($row, $colMap, 'decedent_name');
        if ($name === null) {
            $skipped++;
            $errors[] = "Row $rowNum: missing decedent_name — skipped";
            continue;
        }

        $lat = csvVal($row, $colMap, 'latitude');
        $lng = csvVal($row, $colMap, 'longitude');
        $lat = ($lat !== null && is_numeric($lat)) ? (float)$lat : null;
        $lng = ($lng !== null && is_numeric($lng)) ? (float)$lng : null;
        if (($lat === null) !== ($lng === null)) { $lat = $lng = null; }

        $burialDate = csvDate($row, $colMap, 'burial_date');
        // If burial_date is in the future, treat as scheduled (not yet buried)
        $isBuried = ($burialDate !== null && $burialDate > date('Y-m-d')) ? 0 : 1;

        $fenced = csvVal($row, $colMap, 'is_fenced');
        $fenced = ($fenced !== null && in_array(strtolower($fenced), ['1','yes','true'], true)) ? 1 : 0;

        $insert->execute([
            $name,
            csvVal($row, $colMap, 'family_name'),
            csvDate($row, $colMap, 'birth_date'),
            csvDate($row, $colMap, 'death_date'),
            $burialDate,
            csvTime($row, $colMap, 'burial_time'),
            $isBuried,
            csvDate($row, $colMap, 'expiration_date'),
            csvVal($row, $colMap, 'plot_number'),
            csvVal($row, $colMap, 'barangay'),
            csvVal($row, $colMap, 'memory_space'),
            $fenced,
            $lat,
            $lng,
        ]);
        $imported++;
    }
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    fclose($handle);
    error_log("Import records error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error during import. No records were saved.']);
    exit;
}
fclose($handle);

echo json_encode([
    'success' => true,
    'imported' => $imported,
    'skipped' => $skipped,
    'errors' => $errors,
]);
