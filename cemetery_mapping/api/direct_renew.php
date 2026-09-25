<?php
/**
 * Direct Plot Renewal API (Admin only)
 * Admin can directly extend a plot's expiration date without a visitor request.
 *
 * POST:
 *   plot_type = 'burial' | 'available'
 *   record_id = int (for burial)
 *   plot_id   = int (for available)
 *   or_number = string (required — Official Receipt from the Treasurer)
 *   notes = string (optional)
 *
 * Renewal period is fixed at 5 years.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?: [];

$plot_type = $_POST['plot_type'] ?? ($jsonInput['plot_type'] ?? '');
$record_id = (int)($_POST['record_id'] ?? ($jsonInput['record_id'] ?? 0));
$plot_id = (int)($_POST['plot_id'] ?? ($jsonInput['plot_id'] ?? 0));
$extend_years = 5; // Fixed 5-year renewal period
$or_number = trim($_POST['or_number'] ?? ($jsonInput['or_number'] ?? ''));
$notes = $_POST['notes'] ?? ($jsonInput['notes'] ?? '');

if (!in_array($plot_type, ['burial', 'available'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid plot type']);
    exit;
}
if (empty($or_number)) {
    echo json_encode(['success' => false, 'message' => 'OR (Official Receipt) number is required']);
    exit;
}
if ($plot_type === 'burial' && !$record_id) {
    echo json_encode(['success' => false, 'message' => 'Record ID required for burial plots']);
    exit;
}
if ($plot_type === 'available' && !$plot_id) {
    echo json_encode(['success' => false, 'message' => 'Plot ID required for available plots']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get current expiration date
    if ($plot_type === 'burial') {
        $stmt = $pdo->prepare("SELECT expiration_date FROM burial_records WHERE id = ?");
        $stmt->execute([$record_id]);
    } else {
        $stmt = $pdo->prepare("SELECT expiration_date FROM available_plots WHERE id = ?");
        $stmt->execute([$plot_id]);
    }
    $row = $stmt->fetch();
    if (!$row) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Plot not found']);
        exit;
    }

    $currentExp = $row['expiration_date'];

    // Extend from current expiry if still future, else from today
    $today = new DateTime();
    $baseDate = $today;
    if (!empty($currentExp)) {
        $current = new DateTime($currentExp);
        if ($current > $today) {
            $baseDate = $current;
        }
    }
    $baseDate->modify("+{$extend_years} years");
    $newExpiration = $baseDate->format('Y-m-d');

    if ($plot_type === 'burial') {
        $upd = $pdo->prepare("UPDATE burial_records SET expiration_date = ?, renewal_count = renewal_count + 1 WHERE id = ?");
        $upd->execute([$newExpiration, $record_id]);
    } else {
        $upd = $pdo->prepare("UPDATE available_plots SET expiration_date = ?, renewal_count = renewal_count + 1 WHERE id = ?");
        $upd->execute([$newExpiration, $plot_id]);
    }

    // Log in renewal requests table as an admin-direct approval
    $visitorId = null;
    if ($plot_type === 'burial') {
        $vs = $pdo->prepare("SELECT visitor_id FROM burial_records WHERE id = ?");
        $vs->execute([$record_id]);
        $visitorId = $vs->fetchColumn() ?: null;
    }

    $logStmt = $pdo->prepare("
        INSERT INTO plot_renewal_requests
        (visitor_id, record_id, plot_id, plot_type, current_expiration_date, requested_years, or_number, reason, status, admin_notes, reviewed_by, reviewed_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?, ?, NOW())
    ");
    $logStmt->execute([
        $visitorId,
        $plot_type === 'burial' ? $record_id : null,
        $plot_type === 'available' ? $plot_id : null,
        $plot_type,
        $currentExp,
        $extend_years,
        $or_number,
        $notes ?: 'Direct renewal by admin (OR: ' . $or_number . ')',
        'Direct renewal by admin. OR#: ' . $or_number . '. New expiry: ' . $newExpiration,
        $_SESSION['admin_id']
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Plot renewed successfully. New expiration date: {$newExpiration}", 'new_expiration' => $newExpiration]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Direct renewal error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
