<?php
/**
 * Process Payment Verification - Simple PHP Form Handler
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

$payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$admin_id = $_SESSION['admin_id'];

if ($payment_id <= 0) {
    header('Location: reservations_simple.php?error=' . urlencode('Invalid payment ID'));
    exit;
}

if ($action !== 'verify' && $action !== 'reject') {
    header('Location: reservations_simple.php?error=' . urlencode('Invalid action'));
    exit;
}

try {
    // Get payment
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        header('Location: reservations_simple.php?error=' . urlencode('Payment not found'));
        exit;
    }
    
    if ($payment['verification_status'] !== 'pending') {
        header('Location: reservations_simple.php?error=' . urlencode('Payment already processed'));
        exit;
    }
    
    if ($action === 'verify') {
        // Update payment status
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET verification_status = 'verified', verified_by = ?, verified_date = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $payment_id]);
        
        // Update reservation amount_paid
        $stmt = $pdo->prepare("
            UPDATE plot_reservations 
            SET amount_paid = amount_paid + ?,
                payment_status = CASE 
                    WHEN amount_paid + ? >= total_amount THEN 'paid'
                    WHEN amount_paid + ? > 0 THEN 'partial'
                    ELSE 'unpaid'
                END
            WHERE id = ?
        ");
        $stmt->execute([
            $payment['amount'],
            $payment['amount'],
            $payment['amount'],
            $payment['reservation_id']
        ]);
        
        header('Location: reservations_simple.php?success=' . urlencode('Payment verified successfully! Amount: ₱' . number_format($payment['amount'], 2)));
        
    } else if ($action === 'reject') {
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET verification_status = 'rejected', verified_by = ?, verified_date = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $payment_id]);
        
        header('Location: reservations_simple.php?success=' . urlencode('Payment rejected'));
    }
    
} catch (PDOException $e) {
    error_log("Process payment error: " . $e->getMessage());
    header('Location: reservations_simple.php?error=' . urlencode('Database error'));
}

exit;
