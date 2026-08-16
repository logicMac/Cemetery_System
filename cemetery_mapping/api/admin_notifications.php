<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Pending reservations
    $stmt = $pdo->query("SELECT COUNT(*) FROM plot_reservations WHERE status = 'pending'");
    $pending_reservations = (int)$stmt->fetchColumn();

    // Pending payments
    $stmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE verification_status = 'pending'");
    $pending_payments = (int)$stmt->fetchColumn();

    // New visitors (registered in the last 24 hours)
    $stmt = $pdo->query("SELECT COUNT(*) FROM visitors WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $new_visitors = (int)$stmt->fetchColumn();

    // Recent pending reservations (last 5)
    $stmt = $pdo->query("
        SELECT pr.id, v.full_name as visitor_name, pr.reservation_date
        FROM plot_reservations pr
        JOIN visitors v ON pr.visitor_id = v.id
        WHERE pr.status = 'pending'
        ORDER BY pr.reservation_date DESC
        LIMIT 5
    ");
    $recent_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent pending payments (last 5)
    $stmt = $pdo->query("
        SELECT p.id, v.full_name as visitor_name, p.amount, p.payment_date
        FROM payments p
        JOIN plot_reservations pr ON p.reservation_id = pr.id
        JOIN visitors v ON pr.visitor_id = v.id
        WHERE p.verification_status = 'pending'
        ORDER BY p.payment_date DESC
        LIMIT 5
    ");
    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent new visitors (last 5)
    $stmt = $pdo->query("
        SELECT id, full_name, email, created_at
        FROM visitors
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $recent_visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => $pending_reservations + $pending_payments + $new_visitors,
        'pending_reservations' => $pending_reservations,
        'pending_payments' => $pending_payments,
        'new_visitors' => $new_visitors,
        'recent_reservations' => $recent_reservations,
        'recent_payments' => $recent_payments,
        'recent_visitors' => $recent_visitors
    ]);
} catch (PDOException $e) {
    error_log("Notifications error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
