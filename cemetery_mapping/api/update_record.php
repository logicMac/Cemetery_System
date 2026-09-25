<?php
/**
 * Update Burial Record API
 * Updates existing burial record with optional photo replacement
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
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$decedent_name = strip_tags((string)filter_input(INPUT_POST, 'decedent_name'));
$latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

if (!$id || empty($decedent_name) || $latitude === false || $longitude === false) {
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

$expiration_date = !empty($expiration_date) ? date('Y-m-d', strtotime($expiration_date)) : null;
if (!empty($expiration_date) && $expiration_date === '1970-01-01') {
    $expiration_date = null;
}

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

// Get existing record
try {
    $stmt = $pdo->prepare("SELECT photo FROM burial_records WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        echo json_encode(['success' => false, 'error' => 'Record not found']);
        exit;
    }
    
    $photo_filename = $existing['photo'];
    
    // Handle new photo upload
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
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            exit;
        }
        
        // Delete old photo
        if ($photo_filename) {
            $old_path = '../uploads/photos/' . $photo_filename;
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }
        
        // Upload new photo
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $photo_filename = uniqid('burial_') . '.' . $extension;
        $upload_path = '../uploads/photos/' . $photo_filename;
        
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            echo json_encode(['success' => false, 'error' => 'Failed to upload photo']);
            exit;
        }
    }
    
    // Update record
    $updateStmt = $pdo->prepare("
        UPDATE burial_records
        SET decedent_name = ?, family_name = ?, visitor_id = ?, birth_date = ?, death_date = ?, burial_date = ?, burial_time = ?, is_buried = ?, expiration_date = ?,
            plot_number = ?, barangay = ?, memory_space = ?, latitude = ?,
            longitude = ?, polygon = ?, is_fenced = ?, photo = ?
        WHERE id = ?
    ");

    $updateStmt->execute([
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
        $photo_filename,
        $id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Record updated successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Update record error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
