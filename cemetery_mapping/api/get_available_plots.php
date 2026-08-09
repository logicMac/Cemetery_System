<?php
/**
 * Get Available Plots API
 * Returns all available burial plots
 */

header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $stmt = $pdo->query("
        SELECT id, plot_number, latitude, longitude, notes, photo, 
               has_grid, grid_rows, grid_cols, compartment_count
        FROM available_plots 
        ORDER BY date_added DESC
    ");
    
    $plots = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'plots' => $plots,
        'count' => count($plots)
    ]);
} catch (PDOException $e) {
    error_log("Get available plots error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
