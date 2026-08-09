<?php
/**
 * Get Plot Grid API
 * Returns grid configuration and compartment details
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$plot_id = filter_input(INPUT_GET, 'plot_id', FILTER_VALIDATE_INT);

if (!$plot_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid plot ID']);
    exit;
}

try {
    // Get plot info
    $plotStmt = $pdo->prepare("
        SELECT id, plot_number, latitude, longitude, has_grid, 
               grid_rows, grid_cols, grid_data, compartment_count
        FROM available_plots 
        WHERE id = ?
    ");
    $plotStmt->execute([$plot_id]);
    $plot = $plotStmt->fetch();
    
    if (!$plot) {
        echo json_encode(['success' => false, 'error' => 'Plot not found']);
        exit;
    }
    
    // Get compartments
    $compStmt = $pdo->prepare("
        SELECT id, compartment_number, row_index, col_index, 
               is_occupied, record_id, notes
        FROM plot_compartments 
        WHERE plot_id = ? 
        ORDER BY row_index, col_index
    ");
    $compStmt->execute([$plot_id]);
    $compartments = $compStmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'plot' => $plot,
        'compartments' => $compartments
    ]);
    
} catch (PDOException $e) {
    error_log("Get plot grid error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
