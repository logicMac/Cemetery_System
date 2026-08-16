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
    $search = trim($_GET['search'] ?? '');

    $query = "
        SELECT 
            pr.*,
            v.full_name as visitor_name,
            v.email as visitor_email,
            v.phone as visitor_phone,
            ap.plot_number
        FROM plot_reservations pr
        JOIN visitors v ON pr.visitor_id = v.id
        LEFT JOIN available_plots ap ON pr.plot_id = ap.id
        WHERE 1=1
    ";

    $params = [];

    if ($status_filter !== 'all') {
        $query .= " AND pr.status = :status";
        $params[':status'] = $status_filter;
    }

    if (!empty($search)) {
        $query .= " AND (v.full_name LIKE :search OR v.email LIKE :search OR ap.plot_number LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $query .= " ORDER BY pr.id DESC";

    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get payments for each reservation
    foreach ($reservations as &$res) {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE reservation_id = ? ORDER BY payment_date DESC");
        $stmt->execute([$res['id']]);
        $res['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $res['total_paid'] = 0;
        foreach ($res['payments'] as $payment) {
            if ($payment['verification_status'] === 'verified') {
                $res['total_paid'] += (float)$payment['amount'];
            }
        }
        $res['balance'] = (float)$res['total_amount'] - $res['total_paid'];
    }

    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'count' => count($reservations)
    ]);

} catch (PDOException $e) {
    error_log("Search reservations error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
