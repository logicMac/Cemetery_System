<?php
/**
 * Mark Burial Done API
 * Marks a scheduled burial as completed (is_buried = 1)
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$record_id = (int)($_POST['record_id'] ?? 0);
if ($record_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE burial_records SET is_buried = 1 WHERE id = ?");
    $stmt->execute([$record_id]);
    echo json_encode(['success' => true, 'message' => 'Burial marked as done']);
} catch (PDOException $e) {
    error_log("Mark buried error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
