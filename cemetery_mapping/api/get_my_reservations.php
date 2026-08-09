<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if visitor is logged in
if (!isset($_SESSION['visitor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

try {
    $visitor_id = $_SESSION['visitor_id'];
    
    $stmt = $pdo->prepare("
        SELECT 
            pr.*,
            ap.plot_number,
            ap.latitude,
            ap.longitude,
            pc.compartment_number,
            v.full_name as visitor_name,
            a.username as approved_by_name
        FROM plot_reservations pr
        LEFT JOIN available_plots ap ON pr.plot_id = ap.id
        LEFT JOIN plot_compartments pc ON pr.compartment_id = pc.id
        LEFT JOIN visitors v ON pr.visitor_id = v.id
        LEFT JOIN admin_users a ON pr.approved_by = a.id
        WHERE pr.visitor_id = ?
        ORDER BY pr.reservation_date DESC
    ");
    
    $stmt->execute([$visitor_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payments for each reservation
    foreach ($reservations as &$reservation) {
        $stmt = $pdo->prepare("
            SELECT * FROM payments 
            WHERE reservation_id = ? 
            ORDER BY payment_date DESC
        ");
        $stmt->execute([$reservation['id']]);
        $reservation['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $reservations
    ]);
    
} catch (PDOException $e) {
    error_log("Get reservations error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
