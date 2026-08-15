<?php
/**
 * Update Available Plot API
 * Updates existing available plot with optional photo replacement
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

if (!$id || $latitude === false || $longitude === false) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
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
    // Build update query
    if ($photo_filename) {
        $stmt = $pdo->prepare("
            UPDATE available_plots 
            SET plot_number = ?, latitude = ?, longitude = ?, notes = ?, photo = ?, 
                has_grid = ?, grid_rows = ?, grid_cols = ?, compartment_count = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $plot_number, $latitude, $longitude, $notes, $photo_filename,
            $has_grid, $grid_rows, $grid_cols, $compartment_count, $id
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE available_plots 
            SET plot_number = ?, latitude = ?, longitude = ?, notes = ?,
                has_grid = ?, grid_rows = ?, grid_cols = ?, compartment_count = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $plot_number, $latitude, $longitude, $notes,
            $has_grid, $grid_rows, $grid_cols, $compartment_count, $id
        ]);
    }

    // Update compartments if grid settings changed
    if ($has_grid && $grid_rows && $grid_cols) {
        // Delete old compartments
        $pdo->prepare("DELETE FROM plot_compartments WHERE plot_id = ?")->execute([$id]);

        // Insert new compartments
        $compartmentStmt = $pdo->prepare("
            INSERT INTO plot_compartments (plot_id, compartment_number, row_index, col_index) 
            VALUES (?, ?, ?, ?)
        ");

        for ($row = 0; $row < $grid_rows; $row++) {
            for ($col = 0; $col < $grid_cols; $col++) {
                $compartment_number = chr(65 + $row) . ($col + 1);
                $compartmentStmt->execute([$id, $compartment_number, $row, $col]);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Plot updated successfully'
    ]);

} catch (PDOException $e) {
    if ($photo_filename && file_exists($upload_path)) {
        unlink($upload_path);
    }

    error_log("Update plot error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
