<?php
/**
 * Add Available Plot API
 * Creates new available burial plot with optional grid
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

if ($latitude === false || $longitude === false) {
    echo json_encode(['success' => false, 'error' => 'Invalid coordinates']);
    exit;
}

$plot_number = filter_input(INPUT_POST, 'plot_number', FILTER_SANITIZE_STRING);
$notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
$has_grid = isset($_POST['has_grid']) ? 1 : 0;
$grid_rows = $has_grid ? filter_input(INPUT_POST, 'grid_rows', FILTER_VALIDATE_INT) : null;
$grid_cols = $has_grid ? filter_input(INPUT_POST, 'grid_cols', FILTER_VALIDATE_INT) : null;
$compartment_count = $has_grid && $grid_rows && $grid_cols ? $grid_rows * $grid_cols : 1;

// Handle photo upload
$photo_filename = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];
    
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB']);
        exit;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, ['image/jpeg', 'image/png', 'image/jpg'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $photo_filename = uniqid('plot_') . '.' . $extension;
    $upload_path = '../uploads/plots/' . $photo_filename;
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo json_encode(['success' => false, 'error' => 'Failed to upload photo']);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO available_plots 
        (plot_number, latitude, longitude, notes, photo, has_grid, grid_rows, grid_cols, compartment_count, added_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $plot_number,
        $latitude,
        $longitude,
        $notes,
        $photo_filename,
        $has_grid,
        $grid_rows,
        $grid_cols,
        $compartment_count,
        $_SESSION['admin_username']
    ]);
    
    $plot_id = $pdo->lastInsertId();
    
    // Create compartments if grid is enabled
    if ($has_grid && $grid_rows && $grid_cols) {
        $compartmentStmt = $pdo->prepare("
            INSERT INTO plot_compartments (plot_id, compartment_number, row_index, col_index) 
            VALUES (?, ?, ?, ?)
        ");
        
        for ($row = 0; $row < $grid_rows; $row++) {
            for ($col = 0; $col < $grid_cols; $col++) {
                $compartment_number = chr(65 + $row) . ($col + 1);
                $compartmentStmt->execute([$plot_id, $compartment_number, $row, $col]);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'plot_id' => $plot_id,
        'message' => 'Plot added successfully'
    ]);
    
} catch (PDOException $e) {
    if ($photo_filename && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    error_log("Add plot error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
