<?php
/**
 * Get Reserved Compartments for a Plot
 * Returns list of compartment IDs that are already reserved/approved
 */

require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $plot_id = $_GET['plot_id'] ?? null;
    
    if (!$plot_id) {
        echo json_encode(['success' => false, 'message' => 'Plot ID is required']);
        exit;
    }
    
    // Get compartments that are reserved or approved (not rejected or cancelled)
    $stmt = $pdo->prepare("
        SELECT DISTINCT compartment_id 
        FROM plot_reservations 
        WHERE plot_id = ? 
        AND compartment_id IS NOT NULL
        AND status IN ('pending', 'approved')
        ORDER BY compartment_id
    ");
    
    $stmt->execute([$plot_id]);
    $reserved = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'reserved' => array_map('intval', $reserved),
        'count' => count($reserved)
    ]);
    
} catch (PDOException $e) {
    error_log("Get reserved compartments error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'reserved' => []
    ]);
}
