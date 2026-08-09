<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM plot_pricing WHERE is_active = 1 ORDER BY price ASC");
    $pricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pricing' => $pricing
    ]);
    
} catch (PDOException $e) {
    error_log("Pricing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
