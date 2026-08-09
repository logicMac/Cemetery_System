<?php
/**
 * Process Reservation - Simple PHP Form Handler
 * No JavaScript, pure PHP
 */

session_start();
require_once '../config/database.php';

// Check admin
if (!isset($_SESSION['admin_id'])) {
    die('Not logged in as admin');
}

// Check POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

$reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$admin_id = $_SESSION['admin_id'];

if ($reservation_id <= 0) {
    header('Location: reservations_simple.php?error=' . urlencode('Invalid reservation ID'));
    exit;
}

if ($action !== 'approve' && $action !== 'reject') {
    header('Location: reservations_simple.php?error=' . urlencode('Invalid action'));
    exit;
}

try {
    // Get reservation
    $stmt = $pdo->prepare("SELECT * FROM plot_reservations WHERE id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        header('Location: reservations_simple.php?error=' . urlencode('Reservation not found'));
        exit;
    }
    
    if ($reservation['status'] !== 'pending') {
        header('Location: reservations_simple.php?error=' . urlencode('Reservation already processed'));
        exit;
    }
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("
            UPDATE plot_reservations 
            SET status = 'approved', approved_by = ?, approved_date = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $reservation_id]);
        
        if ($stmt->rowCount() > 0) {
            header('Location: reservations_simple.php?success=' . urlencode('Reservation #' . $reservation_id . ' approved successfully!'));
        } else {
            header('Location: reservations_simple.php?error=' . urlencode('Failed to update reservation'));
        }
        
    } else if ($action === 'reject') {
        $stmt = $pdo->prepare("
            UPDATE plot_reservations 
            SET status = 'rejected', approved_by = ?, approved_date = NOW(), rejection_reason = ? 
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, 'Rejected by admin', $reservation_id]);
        
        if ($stmt->rowCount() > 0) {
            header('Location: reservations_simple.php?success=' . urlencode('Reservation #' . $reservation_id . ' rejected'));
        } else {
            header('Location: reservations_simple.php?error=' . urlencode('Failed to reject reservation'));
        }
    }
    
} catch (PDOException $e) {
    error_log("Process reservation error: " . $e->getMessage());
    header('Location: reservations_simple.php?error=' . urlencode('Database error'));
}

exit;
