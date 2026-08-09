<?php
/**
 * Search Burial Records API
 * Searches by name, plot number, family name, or barangay
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);

if (empty($query) || strlen($query) < 2) {
    echo json_encode([
        'success' => false,
        'error' => 'Query too short'
    ]);
    exit;
}

try {
    $searchTerm = '%' . $query . '%';
    
    $stmt = $pdo->prepare("
        SELECT id, decedent_name, birth_date, death_date, barangay, plot_number, 
               memory_space, latitude, longitude, is_fenced, family_name, photo
        FROM burial_records 
        WHERE (decedent_name LIKE ? 
           OR plot_number LIKE ? 
           OR family_name LIKE ? 
           OR barangay LIKE ?)
          AND latitude IS NOT NULL 
          AND longitude IS NOT NULL
        ORDER BY decedent_name ASC
        LIMIT 50
    ");
    
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ]);
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
