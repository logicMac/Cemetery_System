<?php
/**
 * Delete Available Plot API
 * Removes plot and associated compartments
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    // Get photo filename
    $stmt = $pdo->prepare("SELECT photo FROM available_plots WHERE id = ?");
    $stmt->execute([$id]);
    $plot = $stmt->fetch();
    
    if (!$plot) {
        echo json_encode(['success' => false, 'error' => 'Plot not found']);
        exit;
    }
    
    // Delete plot (compartments will be deleted automatically via CASCADE)
    $deleteStmt = $pdo->prepare("DELETE FROM available_plots WHERE id = ?");
    $deleteStmt->execute([$id]);
    
    // Delete photo if exists
    if ($plot['photo']) {
        $photo_path = '../uploads/plots/' . $plot['photo'];
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Plot deleted successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Delete plot error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
