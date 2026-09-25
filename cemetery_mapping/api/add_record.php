<?php
/**
 * Add Burial Record API
 * Handles creation of new burial records with photo upload
 */

session_start();
header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

// Validate required fields
$decedent_name = strip_tags((string)filter_input(INPUT_POST, 'decedent_name'));
$latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

if (empty($decedent_name) || $latitude === false || $longitude === false) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Sanitize optional fields
$family_name = strip_tags((string)filter_input(INPUT_POST, 'family_name'));
$visitor_id = filter_input(INPUT_POST, 'visitor_id', FILTER_VALIDATE_INT);
$birth_date = strip_tags((string)filter_input(INPUT_POST, 'birth_date'));
$death_date = strip_tags((string)filter_input(INPUT_POST, 'death_date'));
$plot_number = strip_tags((string)filter_input(INPUT_POST, 'plot_number'));
$barangay = strip_tags((string)filter_input(INPUT_POST, 'barangay'));
$memory_space = strip_tags((string)filter_input(INPUT_POST, 'memory_space'));
$expiration_date = strip_tags((string)filter_input(INPUT_POST, 'expiration_date'));
$burial_date = strip_tags((string)filter_input(INPUT_POST, 'burial_date'));
$burial_time = strip_tags((string)filter_input(INPUT_POST, 'burial_time'));
$is_buried = isset($_POST['is_buried']) && $_POST['is_buried'] === '0' ? 0 : 1;
$is_fenced = isset($_POST['is_fenced']) ? 1 : 0;

// Validate optional expiration date
$expiration_date = !empty($expiration_date) ? date('Y-m-d', strtotime($expiration_date)) : null;
if (!empty($expiration_date) && $expiration_date === '1970-01-01') {
    $expiration_date = null;
}

// Validate optional burial (interment) date and time
$burial_date = !empty($burial_date) ? date('Y-m-d', strtotime($burial_date)) : null;
$burial_time = !empty($burial_time) ? date('H:i:s', strtotime($burial_time)) : null;

// Polygon is a JSON array of [lat, lng] points; validate it instead of sanitizing
$polygon = null;
if (!empty($_POST['polygon'])) {
    $decoded = json_decode($_POST['polygon'], true);
    if (is_array($decoded) && count($decoded) >= 3) {
        $polygon = json_encode($decoded);
    }
}

// Handle photo upload
$photo_filename = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit']);
        exit;
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    
    if (!in_array($mime_type, $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPEG and PNG allowed']);
        exit;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $photo_filename = uniqid('burial_') . '.' . $extension;
    $upload_path = '../uploads/photos/' . $photo_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo json_encode(['success' => false, 'error' => 'Failed to upload photo']);
        exit;
    }
}

// Insert record into database
try {
    $stmt = $pdo->prepare("
        INSERT INTO burial_records
        (decedent_name, family_name, visitor_id, birth_date, death_date, burial_date, burial_time, is_buried, expiration_date, plot_number, barangay,
         memory_space, latitude, longitude, polygon, is_fenced, photo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $decedent_name,
        $family_name,
        $visitor_id ?: null,
        $birth_date ?: null,
        $death_date ?: null,
        $burial_date,
        $burial_time,
        $is_buried,
        $expiration_date,
        $plot_number,
        $barangay,
        $memory_space,
        $latitude,
        $longitude,
        $polygon,
        $is_fenced,
        $photo_filename
    ]);
    
    $record_id = $pdo->lastInsertId();

    // Optional grid/cell assignment
    $gridId = filter_input(INPUT_POST, 'grid_id', FILTER_VALIDATE_INT);
    $cell = strip_tags((string)filter_input(INPUT_POST, 'cell'));
    if ($gridId && $cell) {
        $parts = explode(',', $cell);
        if (count($parts) === 2) {
            $rowIdx = (int)trim($parts[0]);
            $colIdx = (int)trim($parts[1]);
            $assign = $pdo->prepare("UPDATE plot_grid_cells SET record_id = ?, is_selected = 1, date_updated = NOW() WHERE grid_id = ? AND row_idx = ? AND col_idx = ? AND record_id IS NULL");
            $assign->execute([$record_id, $gridId, $rowIdx, $colIdx]);
        }
    }

    echo json_encode([
        'success' => true,
        'record_id' => $record_id,
        'message' => 'Record added successfully'
    ]);

} catch (PDOException $e) {
    // Delete uploaded photo if database insert fails
    if ($photo_filename && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    error_log("Add record error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
