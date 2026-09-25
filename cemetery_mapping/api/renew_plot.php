<?php
/**
 * Plot Renewal API
 * Visitors can:
 *   - list their own renewal requests
 *   - list expiring/expired plots they own
 *   - submit a new renewal request
 *
 * Admins can:
 *   - list all renewal requests
 *   - approve / reject a request (handled in process_renewal.php)
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/database.php';

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?: [];
$action = $_GET['action'] ?? ($_POST['action'] ?? ($jsonInput['action'] ?? ''));

// ---------- helpers ----------
function jsonOut($data) {
    echo json_encode($data);
    exit;
}

function isVisitor() {
    return isset($_SESSION['visitor_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// ---------- actions ----------
switch ($action) {

    // Visitor: list their own renewal requests
    case 'my_requests':
        if (!isVisitor()) jsonOut(['success' => false, 'message' => 'Unauthorized']);
        try {
            $stmt = $pdo->prepare("
                SELECT r.*,
                       v.full_name AS visitor_name,
                       b.decedent_name, b.plot_number AS burial_plot,
                       ap.plot_number AS available_plot
                FROM plot_renewal_requests r
                LEFT JOIN visitors v ON v.id = r.visitor_id
                LEFT JOIN burial_records b ON b.id = r.record_id
                LEFT JOIN available_plots ap ON ap.id = r.plot_id
                WHERE r.visitor_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$_SESSION['visitor_id']]);
            jsonOut(['success' => true, 'requests' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            error_log("Renewal my_requests error: " . $e->getMessage());
            jsonOut(['success' => false, 'message' => 'Database error']);
        }
        break;

    // Visitor: list expiring/expired plots linked to them
    case 'my_expiring_plots':
        if (!isVisitor()) jsonOut(['success' => false, 'message' => 'Unauthorized']);
        try {
            $visitorId = $_SESSION['visitor_id'];
            $plots = [];

            // Burial records directly linked to this visitor via visitor_id
            $stmt = $pdo->prepare("
                SELECT b.id, b.decedent_name, b.plot_number, b.expiration_date, b.renewal_count,
                       'burial' AS plot_type
                FROM burial_records b
                WHERE b.visitor_id = ? AND b.expiration_date IS NOT NULL
                ORDER BY b.expiration_date ASC
            ");
            $stmt->execute([$visitorId]);
            $plots = array_merge($plots, $stmt->fetchAll());

            // Available plots linked to this visitor via plot_reservations
            $stmt = $pdo->prepare("
                SELECT ap.id, ap.plot_number, ap.notes, ap.expiration_date, ap.renewal_count,
                       'available' AS plot_type
                FROM available_plots ap
                INNER JOIN plot_reservations pr ON pr.plot_id = ap.id
                WHERE pr.visitor_id = ? AND ap.expiration_date IS NOT NULL
                  AND pr.status IN ('pending', 'approved')
                ORDER BY ap.expiration_date ASC
            ");
            $stmt->execute([$visitorId]);
            $plots = array_merge($plots, $stmt->fetchAll());

            jsonOut(['success' => true, 'plots' => $plots]);
        } catch (PDOException $e) {
            error_log("Renewal my_expiring_plots error: " . $e->getMessage());
            jsonOut(['success' => false, 'message' => 'Database error']);
        }
        break;

    // Visitor: submit a new renewal request
    case 'submit':
        if (!isVisitor()) jsonOut(['success' => false, 'message' => 'Unauthorized']);

        $plot_type = $_POST['plot_type'] ?? ($jsonInput['plot_type'] ?? '');
        $record_id = $_POST['record_id'] ?? ($jsonInput['record_id'] ?? null);
        $plot_id = $_POST['plot_id'] ?? ($jsonInput['plot_id'] ?? null);
        $requested_years = (int)($_POST['requested_years'] ?? ($jsonInput['requested_years'] ?? 5));
        $reason = $_POST['reason'] ?? ($jsonInput['reason'] ?? '');
        $current_expiration_date = $_POST['current_expiration_date'] ?? ($jsonInput['current_expiration_date'] ?? null);

        if (!in_array($plot_type, ['burial', 'available'])) {
            jsonOut(['success' => false, 'message' => 'Invalid plot type']);
        }
        if ($plot_type === 'burial' && empty($record_id)) {
            jsonOut(['success' => false, 'message' => 'Record ID is required for burial plots']);
        }
        if ($plot_type === 'available' && empty($plot_id)) {
            jsonOut(['success' => false, 'message' => 'Plot ID is required for available plots']);
        }
        if ($requested_years < 1 || $requested_years > 50) {
            jsonOut(['success' => false, 'message' => 'Requested years must be between 1 and 50']);
        }

        try {
            // Prevent duplicate pending requests for the same plot
            $checkStmt = $pdo->prepare("
                SELECT id FROM plot_renewal_requests
                WHERE visitor_id = ? AND plot_type = ? AND
                      (record_id = ? OR plot_id = ?) AND status = 'pending'
                LIMIT 1
            ");
            $checkStmt->execute([
                $_SESSION['visitor_id'],
                $plot_type,
                $record_id ?: 0,
                $plot_id ?: 0
            ]);
            if ($checkStmt->fetch()) {
                jsonOut(['success' => false, 'message' => 'You already have a pending renewal request for this plot.']);
            }

            $stmt = $pdo->prepare("
                INSERT INTO plot_renewal_requests
                (visitor_id, record_id, plot_id, plot_type, current_expiration_date, requested_years, reason)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['visitor_id'],
                $record_id ?: null,
                $plot_id ?: null,
                $plot_type,
                $current_expiration_date ?: null,
                $requested_years,
                $reason ?: null
            ]);
            jsonOut(['success' => true, 'message' => 'Renewal request submitted. An admin will review it shortly.', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            error_log("Renewal submit error: " . $e->getMessage());
            jsonOut(['success' => false, 'message' => 'Database error']);
        }
        break;

    // Admin: list all renewal requests (optionally filtered by status)
    case 'admin_list':
        if (!isAdmin()) jsonOut(['success' => false, 'message' => 'Unauthorized']);
        $status = $_GET['status'] ?? '';
        try {
            $sql = "
                SELECT r.*,
                       v.full_name AS visitor_name, v.email AS visitor_email,
                       b.decedent_name, b.plot_number AS burial_plot,
                       ap.plot_number AS available_plot,
                       au.username AS reviewer_name
                FROM plot_renewal_requests r
                LEFT JOIN visitors v ON v.id = r.visitor_id
                LEFT JOIN burial_records b ON b.id = r.record_id
                LEFT JOIN available_plots ap ON ap.id = r.plot_id
                LEFT JOIN admin_users au ON au.id = r.reviewed_by
            ";
            $params = [];
            if (in_array($status, ['pending', 'approved', 'rejected'])) {
                $sql .= " WHERE r.status = ?";
                $params[] = $status;
            }
            $sql .= " ORDER BY FIELD(r.status, 'pending', 'approved', 'rejected'), r.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonOut(['success' => true, 'requests' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            error_log("Renewal admin_list error: " . $e->getMessage());
            jsonOut(['success' => false, 'message' => 'Database error']);
        }
        break;

    default:
        jsonOut(['success' => false, 'message' => 'Unknown action']);
}
