<?php
/**
 * Get Barangays API
 * Returns list of barangays with record counts
 */

header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $stmt = $pdo->query("
        SELECT barangay, COUNT(*) as count 
        FROM burial_records 
        WHERE barangay IS NOT NULL 
        GROUP BY barangay 
        ORDER BY barangay ASC
    ");
    
    $barangays = $stmt->fetchAll();
    
    // Also get list of all possible barangays (predefined)
    $allBarangays = [
        'Matinao',
        'Poblacion',
        'San Isidro',
        'San Jose',
        'San Miguel',
        'San Pedro',
        'San Roque',
        'Santa Cruz'
    ];
    
    echo json_encode([
        'success' => true,
        'barangays' => $barangays,
        'all_barangays' => $allBarangays
    ]);
    
} catch (PDOException $e) {
    error_log("Get barangays error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
