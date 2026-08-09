<?php
/**
 * Save Plot Grid API
 * Updates grid configuration and compartment data
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$plot_id = filter_var($input['plot_id'] ?? null, FILTER_VALIDATE_INT);
$grid_data = $input['grid_data'] ?? [];

if (!$plot_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid plot ID']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Update plot grid data
    $stmt = $pdo->prepare("
        UPDATE available_plots 
        SET grid_data = ? 
        WHERE id = ?
    ");
    $stmt->execute([json_encode($grid_data), $plot_id]);
    
    // Update individual compartments if provided
    if (!empty($grid_data['compartments'])) {
        $updateStmt = $pdo->prepare("
            UPDATE plot_compartments 
            SET is_occupied = ?, notes = ? 
            WHERE plot_id = ? AND row_index = ? AND col_index = ?
        ");
        
        foreach ($grid_data['compartments'] as $comp) {
            $updateStmt->execute([
                $comp['is_occupied'] ?? 0,
                $comp['notes'] ?? null,
                $plot_id,
                $comp['row'],
                $comp['col']
            ]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Grid data saved successfully'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Save plot grid error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
