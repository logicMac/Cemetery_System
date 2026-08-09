<?php
/**
 * Test Approve Endpoint
 * This will help debug the approval issue
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Log everything for debugging
$debug = [
    'session_exists' => isset($_SESSION['admin_id']),
    'admin_id' => $_SESSION['admin_id'] ?? 'NOT SET',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'session_data' => $_SESSION
];

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized - No admin session found',
        'debug' => $debug
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method',
        'debug' => $debug
    ]);
    exit;
}

try {
    $admin_id = $_SESSION['admin_id'];
    $reservation_id = $_POST['reservation_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    $debug['received_reservation_id'] = $reservation_id;
    $debug['received_action'] = $action;
    
    if (!$reservation_id) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing reservation_id',
            'debug' => $debug
        ]);
        exit;
    }
    
    if (!$action || !in_array($action, ['approve', 'reject'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid action: ' . $action,
            'debug' => $debug
        ]);
        exit;
    }
    
    // Get reservation details
    $stmt = $pdo->prepare("SELECT * FROM plot_reservations WHERE id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch();
    
    $debug['reservation_found'] = $reservation ? 'YES' : 'NO';
    
    if (!$reservation) {
        echo json_encode([
            'success' => false, 
            'message' => 'Reservation not found',
            'debug' => $debug
        ]);
        exit;
    }
    
    $debug['current_status'] = $reservation['status'];
    
    if ($reservation['status'] !== 'pending') {
        echo json_encode([
            'success' => false, 
            'message' => 'Reservation already processed (status: ' . $reservation['status'] . ')',
            'debug' => $debug
        ]);
        exit;
    }
    
    // Try to update
    if ($action === 'approve') {
        $stmt = $pdo->prepare("
            UPDATE plot_reservations 
            SET status = 'approved', approved_by = ?, approved_date = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$admin_id, $reservation_id]);
        
        $debug['update_result'] = $result;
        $debug['rows_affected'] = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => 'TEST: Reservation approved successfully!',
            'debug' => $debug
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'TEST: Reject not implemented in test',
            'debug' => $debug
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'debug' => $debug
    ]);
}
