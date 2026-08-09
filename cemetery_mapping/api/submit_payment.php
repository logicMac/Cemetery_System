<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if visitor is logged in
if (!isset($_SESSION['visitor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit payment']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $visitor_id = $_SESSION['visitor_id'];
    $reservation_id = $_POST['reservation_id'] ?? null;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $amount = $_POST['amount'] ?? 0;
    $reference_number = $_POST['reference_number'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Validate required fields
    if (!$reservation_id || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment details']);
        exit;
    }
    
    // Verify reservation belongs to visitor
    $stmt = $pdo->prepare("
        SELECT id, total_amount, amount_paid, status 
        FROM plot_reservations 
        WHERE id = ? AND visitor_id = ?
    ");
    $stmt->execute([$reservation_id, $visitor_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        exit;
    }
    
    if ($reservation['status'] !== 'approved' && $reservation['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Cannot add payment to this reservation']);
        exit;
    }
    
    // Handle file upload for proof of payment
    $proof_filename = null;
    if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $proof_filename = 'payment_' . uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $upload_dir . $proof_filename);
        }
    }
    
    // Insert payment record
    $stmt = $pdo->prepare("
        INSERT INTO payments 
        (reservation_id, payment_method, amount, reference_number, proof_of_payment, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $reservation_id,
        $payment_method,
        $amount,
        $reference_number,
        $proof_filename,
        $notes
    ]);
    
    // Update reservation payment status
    $new_amount_paid = $reservation['amount_paid'] + $amount;
    $payment_status = 'partial';
    
    if ($new_amount_paid >= $reservation['total_amount']) {
        $payment_status = 'paid';
    }
    
    $stmt = $pdo->prepare("
        UPDATE plot_reservations 
        SET amount_paid = ?, payment_status = ?
        WHERE id = ?
    ");
    $stmt->execute([$new_amount_paid, $payment_status, $reservation_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment submitted successfully! Awaiting verification.',
        'amount_paid' => $new_amount_paid,
        'payment_status' => $payment_status
    ]);
    
} catch (PDOException $e) {
    error_log("Payment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
