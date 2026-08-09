<?php
/**
 * Get Recent Records API
 * Returns most recently added burial records
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;
$limit = min($limit, 100); // Max 100 records

try {
    $stmt = $pdo->prepare("
        SELECT id, decedent_name, family_name, birth_date, death_date, 
               plot_number, barangay, is_fenced, photo, date_added
        FROM burial_records 
        ORDER BY date_added DESC 
        LIMIT ?
    ");
    
    $stmt->execute([$limit]);
    $records = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'records' => $records,
        'count' => count($records)
    ]);
    
} catch (PDOException $e) {
    error_log("Get recent records error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
