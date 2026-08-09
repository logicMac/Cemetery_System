<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $admin_id = $_SESSION['admin_id'];
    $payment_id = $_POST['payment_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'verify' or 'reject'
    $notes = $_POST['notes'] ?? '';
    
    if (!$payment_id || !in_array($action, ['verify', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    $status = $action === 'verify' ? 'verified' : 'rejected';
    
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET verification_status = ?, verified_by = ?, verified_date = NOW(), notes = ?
        WHERE id = ?
    ");
    $stmt->execute([$status, $admin_id, $notes, $payment_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment ' . $status . ' successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Verify payment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
