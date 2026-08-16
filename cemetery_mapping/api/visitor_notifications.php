<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['visitor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$action = $_GET['action'] ?? '';

// Mark all notifications as read
if ($action === 'mark_read') {
    $_SESSION['visitor_notifs_last_read'] = date('Y-m-d H:i:s');
    echo json_encode(['success' => true]);
    exit;
}

$last_read = $_SESSION['visitor_notifs_last_read'] ?? '1970-01-01 00:00:00';

try {
    $visitor_id = $_SESSION['visitor_id'];
    $notifications = [];
    $unread_count = 0;

    // 1. Approved reservations awaiting payment
    $stmt = $pdo->prepare("
        SELECT pr.id, pr.plot_id, ap.plot_number, pr.reservation_date, pr.total_amount, pr.status
        FROM plot_reservations pr
        LEFT JOIN available_plots ap ON pr.plot_id = ap.id
        WHERE pr.visitor_id = ? AND pr.status = 'approved' AND pr.payment_status = 'unpaid'
        ORDER BY pr.reservation_date DESC
        LIMIT 10
    ");
    $stmt->execute([$visitor_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $isUnread = strtotime($row['reservation_date']) > strtotime($last_read);
        if ($isUnread) $unread_count++;
        $notifications[] = [
            'type' => 'payment_due',
            'title' => 'Payment Due',
            'message' => 'Reservation for Plot ' . ($row['plot_number'] ?? '#' . $row['plot_id']) . ' was approved. Please submit payment.',
            'amount' => $row['total_amount'],
            'reservation_id' => $row['id'],
            'date' => $row['reservation_date'],
            'icon' => 'credit-card',
            'color' => '#10b981',
            'unread' => $isUnread
        ];
    }

    // 2. Pending reservations awaiting approval
    $stmt = $pdo->prepare("
        SELECT pr.id, pr.plot_id, ap.plot_number, pr.reservation_date, pr.status
        FROM plot_reservations pr
        LEFT JOIN available_plots ap ON pr.plot_id = ap.id
        WHERE pr.visitor_id = ? AND pr.status = 'pending'
        ORDER BY pr.reservation_date DESC
        LIMIT 10
    ");
    $stmt->execute([$visitor_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $isUnread = strtotime($row['reservation_date']) > strtotime($last_read);
        if ($isUnread) $unread_count++;
        $notifications[] = [
            'type' => 'pending',
            'title' => 'Reservation Pending',
            'message' => 'Your reservation for Plot ' . ($row['plot_number'] ?? '#' . $row['plot_id']) . ' is awaiting admin approval.',
            'reservation_id' => $row['id'],
            'date' => $row['reservation_date'],
            'icon' => 'clock',
            'color' => '#c9a86c',
            'unread' => $isUnread
        ];
    }

    // 3. Rejected reservations
    $stmt = $pdo->prepare("
        SELECT pr.id, pr.plot_id, ap.plot_number, pr.reservation_date, pr.status
        FROM plot_reservations pr
        LEFT JOIN available_plots ap ON pr.plot_id = ap.id
        WHERE pr.visitor_id = ? AND pr.status = 'rejected'
        ORDER BY pr.reservation_date DESC
        LIMIT 5
    ");
    $stmt->execute([$visitor_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $isUnread = strtotime($row['reservation_date']) > strtotime($last_read);
        if ($isUnread) $unread_count++;
        $notifications[] = [
            'type' => 'rejected',
            'title' => 'Reservation Rejected',
            'message' => 'Your reservation for Plot ' . ($row['plot_number'] ?? '#' . $row['plot_id']) . ' was rejected.',
            'reservation_id' => $row['id'],
            'date' => $row['reservation_date'],
            'icon' => 'x-circle',
            'color' => '#b55a5a',
            'unread' => $isUnread
        ];
    }

    // 4. Payment verified
    $stmt = $pdo->prepare("
        SELECT p.id, p.reservation_id, p.amount, p.payment_date, p.verification_status,
               ap.plot_number
        FROM payments p
        JOIN plot_reservations pr ON p.reservation_id = pr.id
        LEFT JOIN available_plots ap ON pr.plot_id = ap.id
        WHERE pr.visitor_id = ? AND p.verification_status = 'verified'
        ORDER BY p.payment_date DESC
        LIMIT 5
    ");
    $stmt->execute([$visitor_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $isUnread = strtotime($row['payment_date']) > strtotime($last_read);
        if ($isUnread) $unread_count++;
        $notifications[] = [
            'type' => 'payment_verified',
            'title' => 'Payment Verified',
            'message' => 'Your payment of ₱' . number_format($row['amount'], 2) . ' for Plot ' . ($row['plot_number'] ?? '') . ' has been verified.',
            'reservation_id' => $row['reservation_id'],
            'date' => $row['payment_date'],
            'icon' => 'check-circle',
            'color' => '#10b981',
            'unread' => $isUnread
        ];
    }

    // Sort all by date desc
    usort($notifications, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    // Limit to 15 most recent
    $notifications = array_slice($notifications, 0, 15);

    echo json_encode([
        'success' => true,
        'count' => count($notifications),
        'unread_count' => $unread_count,
        'notifications' => $notifications
    ]);

} catch (PDOException $e) {
    error_log("Visitor notifications error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
