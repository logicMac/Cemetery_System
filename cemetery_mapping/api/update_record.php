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
$decedent_name = filter_input(INPUT_POST, 'decedent_name', FILTER_SANITIZE_STRING);
$latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

if (!$id || empty($decedent_name) || $latitude === false || $longitude === false) {
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
        SET decedent_name = ?, family_name = ?, birth_date = ?, death_date = ?, 
            plot_number = ?, barangay = ?, memory_space = ?, latitude = ?, 
            longitude = ?, is_fenced = ?, photo = ?
        WHERE id = ?
    ");
    
    $updateStmt->execute([
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
