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
    $reservation_id = $_POST['reservation_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'approve' or 'reject'
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    if (!$reservation_id || !in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    // Get reservation details
    $stmt = $pdo->prepare("SELECT * FROM plot_reservations WHERE id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        exit;
    }
    
    if ($reservation['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Reservation already processed']);
        exit;
    }
    
    if ($action === 'approve') {
        // Update reservation status
        $stmt = $pdo->prepare("
            UPDATE plot_reservations 
            SET status = 'approved', approved_by = ?, approved_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $reservation_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Reservation approved successfully! Visitor can now submit payment.'
        ]);
        
    } else {
        // Reject reservation
        $stmt = $pdo->prepare("
            UPDATE plot_reservations 
            SET status = 'rejected', approved_by = ?, approved_date = NOW(), rejection_reason = ?
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $rejection_reason, $reservation_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Reservation rejected'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Approve reservation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
