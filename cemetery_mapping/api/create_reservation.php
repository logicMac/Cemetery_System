<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log

// Check if visitor is logged in
if (!isset($_SESSION['visitor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to make a reservation']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $visitor_id = $_SESSION['visitor_id'];
    $plot_id = isset($_POST['plot_id']) ? intval($_POST['plot_id']) : null;
    $compartment_id = isset($_POST['compartment_id']) && $_POST['compartment_id'] !== '' ? intval($_POST['compartment_id']) : null;
    $reservation_type = $_POST['reservation_type'] ?? 'standard';
    $purpose = $_POST['purpose'] ?? '';
    $intended_for = $_POST['intended_for'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    
    // Validate required fields
    if (!$plot_id) {
        echo json_encode(['success' => false, 'message' => 'Plot ID is required']);
        exit;
    }
    
    if (!$reservation_type) {
        echo json_encode(['success' => false, 'message' => 'Reservation type is required']);
        exit;
    }
    
    if (!$purpose) {
        echo json_encode(['success' => false, 'message' => 'Purpose is required']);
        exit;
    }
    
    if (!$intended_for) {
        echo json_encode(['success' => false, 'message' => 'Intended for field is required']);
        exit;
    }
    
    if (!$contact_number) {
        echo json_encode(['success' => false, 'message' => 'Contact number is required']);
        exit;
    }
    
    // Check if plot exists and is available
    $stmt = $pdo->prepare("SELECT id, plot_number, has_grid, grid_rows, grid_cols FROM available_plots WHERE id = ?");
    $stmt->execute([$plot_id]);
    $plot = $stmt->fetch();
    
    if (!$plot) {
        echo json_encode(['success' => false, 'message' => 'Plot not found or no longer available']);
        exit;
    }
    
    // If compartment is specified, validate it exists for plots with grids
    if ($compartment_id !== null && $plot['has_grid'] == 1) {
        // For now, we'll just store the compartment_id as a reference
        // You can add validation logic here if you have a compartments table
    }
    
    // Check if visitor already has pending/approved reservation for this plot AND compartment
    if ($compartment_id !== null) {
        // For specific compartment reservations
        $stmt = $pdo->prepare("
            SELECT id, status, compartment_id FROM plot_reservations 
            WHERE visitor_id = ? AND plot_id = ? AND compartment_id = ? AND status IN ('pending', 'approved')
        ");
        $stmt->execute([$visitor_id, $plot_id, $compartment_id]);
    } else {
        // For entire plot reservations (no compartment specified)
        $stmt = $pdo->prepare("
            SELECT id, status, compartment_id FROM plot_reservations 
            WHERE visitor_id = ? AND plot_id = ? AND compartment_id IS NULL AND status IN ('pending', 'approved')
        ");
        $stmt->execute([$visitor_id, $plot_id]);
    }
    
    $existing = $stmt->fetch();
    
    if ($existing) {
        $statusText = ucfirst($existing['status']);
        if ($compartment_id !== null) {
            echo json_encode(['success' => false, 'message' => "You already have a {$statusText} reservation for this compartment"]);
        } else {
            echo json_encode(['success' => false, 'message' => "You already have a {$statusText} reservation for this plot"]);
        }
        exit;
    }
    
    // Get pricing based on reservation type
    $stmt = $pdo->prepare("SELECT price FROM plot_pricing WHERE plot_type = ? AND is_active = 1");
    $stmt->execute([$reservation_type]);
    $pricing = $stmt->fetch();
    
    if (!$pricing) {
        // Fallback to default pricing if not found
        $defaultPrices = [
            'standard' => 5000.00,
            'premium' => 15000.00,
            'family' => 25000.00,
            'lawn' => 8000.00
        ];
        $total_amount = $defaultPrices[$reservation_type] ?? 5000.00;
    } else {
        $total_amount = $pricing['price'];
    }
    
    // Create reservation
    $stmt = $pdo->prepare("
        INSERT INTO plot_reservations 
        (visitor_id, plot_id, compartment_id, reservation_type, purpose, intended_for, contact_number, total_amount, status, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')
    ");
    
    $result = $stmt->execute([
        $visitor_id,
        $plot_id,
        $compartment_id,
        $reservation_type,
        $purpose,
        $intended_for,
        $contact_number,
        $total_amount
    ]);
    
    if (!$result) {
        throw new Exception('Failed to insert reservation record');
    }
    
    $reservation_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reservation submitted successfully! Awaiting admin approval.',
        'reservation_id' => $reservation_id,
        'total_amount' => $total_amount,
        'plot_number' => $plot['plot_number'] ?? 'Plot #' . $plot_id,
        'compartment' => $compartment_id
    ]);
    
} catch (PDOException $e) {
    error_log("Reservation PDO error: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    
    // Check for specific errors
    if ($e->getCode() == '42S02') {
        echo json_encode(['success' => false, 'message' => 'Database tables not set up. Please contact administrator.']);
    } elseif ($e->getCode() == '23000') {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided. Please check all fields.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    error_log("Reservation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
