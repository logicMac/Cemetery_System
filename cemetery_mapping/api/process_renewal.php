<?php
/**
 * Process Renewal API (Admin only)
 * Approves or rejects a pending renewal request.
 *
 * On approval:
 *  - Extends the plot's expiration_date by the requested years from today (or from current expiry, whichever is later).
 *  - Increments renewal_count on the plot.
 *  - Sets request status to 'approved'.
 *
 * On rejection:
 *  - Sets request status to 'rejected' with admin_notes.
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

$request_id = (int)($_POST['request_id'] ?? ($jsonInput['request_id'] ?? 0));
$decision   = $_POST['decision']   ?? ($jsonInput['decision']   ?? '');
$admin_notes = $_POST['admin_notes'] ?? ($jsonInput['admin_notes'] ?? '');
$extend_years = (int)($_POST['extend_years'] ?? ($jsonInput['extend_years'] ?? 0));

if (!$request_id || !in_array($decision, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch the renewal request
    $stmt = $pdo->prepare("SELECT * FROM plot_renewal_requests WHERE id = ? FOR UPDATE");
    $stmt->execute([$request_id]);
    $req = $stmt->fetch();

    if (!$req) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Renewal request not found']);
        exit;
    }
    if ($req['status'] !== 'pending') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'This request has already been ' . $req['status']]);
        exit;
    }

    if ($decision === 'approve') {
        $years = $extend_years > 0 ? $extend_years : (int)$req['requested_years'];
        if ($years < 1 || $years > 50) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Invalid extension period']);
            exit;
        }

        // Compute new expiration date: extend from current expiry if still in the future, else from today
        $today = new DateTime();
        $baseDate = $today;
        if (!empty($req['current_expiration_date'])) {
            $current = new DateTime($req['current_expiration_date']);
            if ($current > $today) {
                $baseDate = $current;
            }
        }
        $baseDate->modify("+{$years} years");
        $newExpiration = $baseDate->format('Y-m-d');

        if ($req['plot_type'] === 'burial' && $req['record_id']) {
            $upd = $pdo->prepare("UPDATE burial_records SET expiration_date = ?, renewal_count = renewal_count + 1 WHERE id = ?");
            $upd->execute([$newExpiration, $req['record_id']]);
        } elseif ($req['plot_type'] === 'available' && $req['plot_id']) {
            $upd = $pdo->prepare("UPDATE available_plots SET expiration_date = ?, renewal_count = renewal_count + 1 WHERE id = ?");
            $upd->execute([$newExpiration, $req['plot_id']]);
        }

        $upd = $pdo->prepare("
            UPDATE plot_renewal_requests
            SET status = 'approved', admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([$admin_notes ?: "Approved for {$years} year(s). New expiry: {$newExpiration}", $_SESSION['admin_id'], $request_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Renewal approved. New expiration date: {$newExpiration}", 'new_expiration' => $newExpiration]);
    } else {
        // Reject
        $upd = $pdo->prepare("
            UPDATE plot_renewal_requests
            SET status = 'rejected', admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([$admin_notes ?: 'Rejected by admin', $_SESSION['admin_id'], $request_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Renewal request rejected']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Process renewal error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
