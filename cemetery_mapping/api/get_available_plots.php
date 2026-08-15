<?php
/**
 * Get Available Plots API
 * Returns all burial plots with reservation status
 */

header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $stmt = $pdo->query("
        SELECT 
            ap.id, ap.plot_number, ap.latitude, ap.longitude, ap.notes, ap.photo, 
            ap.has_grid, ap.grid_rows, ap.grid_cols, ap.compartment_count,
            (SELECT pr.status FROM plot_reservations pr 
             WHERE pr.plot_id = ap.id AND pr.compartment_id IS NULL 
             AND pr.status IN ('pending', 'approved') 
             ORDER BY pr.reservation_date DESC LIMIT 1) as reservation_status,
            (SELECT COUNT(*) FROM plot_reservations pr 
             WHERE pr.plot_id = ap.id AND pr.status IN ('pending', 'approved')) as reservation_count
        FROM available_plots ap
        ORDER BY ap.date_added DESC
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
    