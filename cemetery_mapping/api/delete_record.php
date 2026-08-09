<?php
/**
 * Delete Burial Record API
 * Removes a burial record and associated photo
 */

session_start();
header('Content-Type: application/json');

// Check admin authentication
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
    // Get photo filename before deleting
    $stmt = $pdo->prepare("SELECT photo FROM burial_records WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        echo json_encode(['success' => false, 'error' => 'Record not found']);
        exit;
    }
    
    // Delete record from database
    $deleteStmt = $pdo->prepare("DELETE FROM burial_records WHERE id = ?");
    $deleteStmt->execute([$id]);
    
    // Delete photo file if exists
    if ($record['photo']) {
        $photo_path = '../uploads/photos/' . $record['photo'];
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Record deleted successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Delete record error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
