<?php
/**
 * Get Single Burial Record API
 * Returns detailed information for a specific record
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, decedent_name, family_name, birth_date, death_date, plot_number, 
               barangay, memory_space, latitude, longitude, is_fenced, photo, date_added
        FROM burial_records 
        WHERE id = ?
    ");
    
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    
    if ($record) {
        echo json_encode([
            'success' => true,
            'record' => $record
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Record not found'
        ]);
    }
} catch (PDOException $e) {
    error_log("Get record error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
