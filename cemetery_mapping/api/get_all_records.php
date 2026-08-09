<?php
/**
 * Get All Burial Records API
 * Returns all burial records with coordinates
 */

header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $stmt = $pdo->query("
        SELECT id, decedent_name, birth_date, death_date, barangay, plot_number, 
               memory_space, latitude, longitude, is_fenced, family_name, photo
        FROM burial_records 
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL
        ORDER BY date_added DESC
    ");
    
    $records = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'records' => $records,
        'count' => count($records)
    ]);
} catch (PDOException $e) {
    error_log("Get all records error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
