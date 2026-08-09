<?php
/**
 * Simple Approve/Reject Reservation API
 * Created from scratch - bulletproof version
 */

session_start();
require_once '../config/database.php';

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Log for debugging
error_log("=== APPROVE API CALLED ===");
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));

// Response function
function respond($success, $message, $data = []) {
    $response = array_merge(['success' => $success, 'message' => $message], $data);
    error_log("Response: " . json_encode($response));
    echo json_encode($response);
    exit;
}

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    respond(false, 'Not logged in as admin. Session admin_id not found.');
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
}

// Get parameters
$reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

error_log("Reservation ID: $reservation_id");
error_log("Action: $action");

// Validate parameters
if ($reservation_id <= 0) {
    respond(false, 'Invalid reservation ID: ' . $reservation_id);
}

if ($action !== 'approve' && $action !== 'reject') {
    respond(false, 'Invalid action: ' . $action);
}

try {
    $admin_id = $_SESSION['admin_id'];
    
    // Get reservation
    $stmt = $pdo->prepare("SELECT * FROM plot_reservations WHERE id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        respond(false, 'Reservation not found with ID: ' . $reservation_id);
    }
    
    error_log("Found reservation: " . print_r($reservation, true));
    
    // Check current status
    if ($reservation['status'] !== 'pending') {
        respond(false, 'Reservation is not pending (current status: ' . $reservation['status'] . ')');
    }
    
    // Perform action
    if ($action === 'approve') {
        $sql = "UPDATE plot_reservations SET status = 'approved', approved_by = ?, approved_date = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$admin_id, $reservation_id]);
        
        error_log("Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
        error_log("Rows affected: " . $stmt->rowCount());
        
        if ($result && $stmt->rowCount() > 0) {
            respond(true, 'Reservation approved successfully!', [
                'reservation_id' => $reservation_id,
                'new_status' => 'approved'
            ]);
        } else {
            respond(false, 'Update query executed but no rows affected');
        }
        
    } else if ($action === 'reject') {
        $rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : 'No reason provided';
        
        $sql = "UPDATE plot_reservations SET status = 'rejected', approved_by = ?, approved_date = NOW(), rejection_reason = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$admin_id, $rejection_reason, $reservation_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            respond(true, 'Reservation rejected', [
                'reservation_id' => $reservation_id,
                'new_status' => 'rejected'
            ]);
        } else {
            respond(false, 'Reject update failed');
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    respond(false, 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    respond(false, 'Error: ' . $e->getMessage());
}
