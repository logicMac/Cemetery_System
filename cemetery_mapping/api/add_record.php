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
$decedent_name = filter_input(INPUT_POST, 'decedent_name', FILTER_SANITIZE_STRING);
$latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

if (empty($decedent_name) || $latitude === false || $longitude === false) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Sanitize optional fields
$family_name = filter_input(INPUT_POST, 'family_name', FILTER_SANITIZE_STRING);
$birth_date = filter_input(INPUT_POST, 'birth_date', FILTER_SANITIZE_STRING);
$death_date = filter_input(INPUT_POST, 'death_date', FILTER_SANITIZE_STRING);
$plot_number = filter_input(INPUT_POST, 'plot_number', FILTER_SANITIZE_STRING);
$barangay = filter_input(INPUT_POST, 'barangay', FILTER_SANITIZE_STRING);
$memory_space = filter_input(INPUT_POST, 'memory_space', FILTER_SANITIZE_STRING);
$is_fenced = isset($_POST['is_fenced']) ? 1 : 0;

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
        (decedent_name, family_name, birth_date, death_date, plot_number, barangay, 
         memory_space, latitude, longitude, is_fenced, photo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $decedent_name,
        $family_name,
        $birth_date ?: null,
        $death_date ?: null,
        $plot_number,
        $barangay,
        $memory_space,
        $latitude,
        $longitude,
        $is_fenced,
        $photo_filename
    ]);
    
    $record_id = $pdo->lastInsertId();
    
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
