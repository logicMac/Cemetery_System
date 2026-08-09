<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $status_filter = $_GET['status'] ?? 'all';
    
    $sql = "
        SELECT 
            pr.*,
            ap.plot_number,
            ap.latitude,
            ap.longitude,
            pc.compartment_number,
            v.full_name as visitor_name,
            v.email as visitor_email,
            v.phone as visitor_phone,
            a.username as approved_by_name
        FROM plot_reservations pr
        LEFT JOIN available_plots ap ON pr.plot_id = ap.id
        LEFT JOIN plot_compartments pc ON pr.compartment_id = pc.id
        LEFT JOIN visitors v ON pr.visitor_id = v.id
        LEFT JOIN admin_users a ON pr.approved_by = a.id
    ";
    
    if ($status_filter !== 'all') {
        $sql .= " WHERE pr.status = :status";
    }
    
    $sql .= " ORDER BY pr.reservation_date DESC";
    
    $stmt = $pdo->prepare($sql);
    
    if ($status_filter !== 'all') {
        $stmt->bindParam(':status', $status_filter);
    }
    
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payments for each reservation
    foreach ($reservations as &$reservation) {
        $stmt = $pdo->prepare("
            SELECT p.*, a.username as verified_by_name
            FROM payments p
            LEFT JOIN admin_users a ON p.verified_by = a.id
            WHERE p.reservation_id = ? 
            ORDER BY p.payment_date DESC
        ");
        $stmt->execute([$reservation['id']]);
        $reservation['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'reservations' => $reservations
    ]);
    
} catch (PDOException $e) {
    error_log("Admin get reservations error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
