<?php
/**
 * API: Compartment Records Management
 * Purpose: Manage multiple burial records within a single compartment
 * Actions: add, remove, list, update_capacity
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Authentication check (admin only)
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

function respond($success, $message, $data = null) {
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $data ? ['data' => $data] : []
    ));
    exit;
}

try {
    // Parse JSON input or use POST data
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        
        // ============================================
        // List all records in a compartment
        // ============================================
        case 'list':
            $compartmentId = $input['compartment_id'] ?? $_GET['compartment_id'] ?? null;
            
            if (!$compartmentId) {
                respond(false, 'Compartment ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    cr.id,
                    cr.compartment_id,
                    cr.record_id,
                    cr.burial_level,
                    cr.position_notes,
                    cr.date_added,
                    br.decedent_name,
                    br.birth_date,
                    br.death_date,
                    br.plot_number,
                    br.family_name,
                    br.photo,
                    pc.compartment_number,
                    pc.capacity,
                    pc.occupied_count
                FROM compartment_records cr
                JOIN burial_records br ON cr.record_id = br.id
                JOIN plot_compartments pc ON cr.compartment_id = pc.id
                WHERE cr.compartment_id = ?
                ORDER BY cr.burial_level, cr.date_added
            ");
            $stmt->execute([$compartmentId]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            respond(true, 'Records retrieved', ['records' => $records]);
            break;

        // ============================================
        // Add a burial record to a compartment
        // ============================================
        case 'add':
            $compartmentId = $input['compartment_id'] ?? null;
            $recordId = $input['record_id'] ?? null;
            $burialLevel = $input['burial_level'] ?? 'ground';
            $positionNotes = $input['position_notes'] ?? null;
            
            if (!$compartmentId || !$recordId) {
                respond(false, 'Compartment ID and Record ID are required');
            }
            
            // Check capacity
            $stmt = $pdo->prepare("
                SELECT capacity, occupied_count 
                FROM plot_compartments 
                WHERE id = ?
            ");
            $stmt->execute([$compartmentId]);
            $compartment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$compartment) {
                respond(false, 'Compartment not found');
            }
            
            if ($compartment['occupied_count'] >= $compartment['capacity']) {
                respond(false, "Compartment is full (capacity: {$compartment['capacity']})");
            }
            
            // Check if record already exists in this compartment
            $stmt = $pdo->prepare("
                SELECT id FROM compartment_records 
                WHERE compartment_id = ? AND record_id = ?
            ");
            $stmt->execute([$compartmentId, $recordId]);
            if ($stmt->fetch()) {
                respond(false, 'Record is already in this compartment');
            }
            
            // Add the record
            $stmt = $pdo->prepare("
                INSERT INTO compartment_records 
                (compartment_id, record_id, burial_level, position_notes) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$compartmentId, $recordId, $burialLevel, $positionNotes]);
            
            // Update occupied count
            $pdo->prepare("
                UPDATE plot_compartments 
                SET occupied_count = occupied_count + 1,
                    is_occupied = 1
                WHERE id = ?
            ")->execute([$compartmentId]);
            
            respond(true, 'Record added to compartment', [
                'id' => $pdo->lastInsertId(),
                'compartment_id' => $compartmentId,
                'record_id' => $recordId
            ]);
            break;

        // ============================================
        // Remove a burial record from a compartment
        // ============================================
        case 'remove':
            $compartmentRecordId = $input['id'] ?? null;
            $compartmentId = $input['compartment_id'] ?? null;
            $recordId = $input['record_id'] ?? null;
            
            if ($compartmentRecordId) {
                // Delete by junction table ID
                $stmt = $pdo->prepare("
                    SELECT compartment_id FROM compartment_records WHERE id = ?
                ");
                $stmt->execute([$compartmentRecordId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$result) {
                    respond(false, 'Compartment record not found');
                }
                $compartmentId = $result['compartment_id'];
                
                $pdo->prepare("DELETE FROM compartment_records WHERE id = ?")->execute([$compartmentRecordId]);
            } else if ($compartmentId && $recordId) {
                // Delete by compartment_id + record_id
                $pdo->prepare("
                    DELETE FROM compartment_records 
                    WHERE compartment_id = ? AND record_id = ?
                ")->execute([$compartmentId, $recordId]);
            } else {
                respond(false, 'Either ID or (compartment_id + record_id) is required');
            }
            
            // Update occupied count and is_occupied flag
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM compartment_records 
                WHERE compartment_id = ?
            ");
            $stmt->execute([$compartmentId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $pdo->prepare("
                UPDATE plot_compartments 
                SET occupied_count = ?,
                    is_occupied = ?
                WHERE id = ?
            ")->execute([$count, $count > 0 ? 1 : 0, $compartmentId]);
            
            respond(true, 'Record removed from compartment');
            break;

        // ============================================
        // Update compartment capacity
        // ============================================
        case 'update_capacity':
            $compartmentId = $input['compartment_id'] ?? null;
            $capacity = $input['capacity'] ?? null;
            
            if (!$compartmentId || !$capacity) {
                respond(false, 'Compartment ID and capacity are required');
            }
            
            if ($capacity < 1) {
                respond(false, 'Capacity must be at least 1');
            }
            
            // Check if new capacity is less than current occupied count
            $stmt = $pdo->prepare("
                SELECT occupied_count FROM plot_compartments WHERE id = ?
            ");
            $stmt->execute([$compartmentId]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($capacity < $current['occupied_count']) {
                respond(false, "Cannot reduce capacity below current occupied count ({$current['occupied_count']})");
            }
            
            $pdo->prepare("
                UPDATE plot_compartments 
                SET capacity = ? 
                WHERE id = ?
            ")->execute([$capacity, $compartmentId]);
            
            respond(true, 'Compartment capacity updated', ['capacity' => $capacity]);
            break;

        // ============================================
        // Get compartments for a specific burial record
        // ============================================
        case 'get_by_record':
            $recordId = $input['record_id'] ?? $_GET['record_id'] ?? null;
            
            if (!$recordId) {
                respond(false, 'Record ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    cr.*,
                    pc.compartment_number,
                    pc.row_index,
                    pc.col_index,
                    pc.plot_id,
                    ap.plot_number,
                    ap.latitude,
                    ap.longitude
                FROM compartment_records cr
                JOIN plot_compartments pc ON cr.compartment_id = pc.id
                JOIN available_plots ap ON pc.plot_id = ap.id
                WHERE cr.record_id = ?
            ");
            $stmt->execute([$recordId]);
            $compartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            respond(true, 'Compartments retrieved', ['compartments' => $compartments]);
            break;

        default:
            respond(false, 'Invalid action. Available: list, add, remove, update_capacity, get_by_record');
    }
    
} catch (PDOException $e) {
    error_log("Compartment Records API Error: " . $e->getMessage());
    respond(false, 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Compartment Records API Error: " . $e->getMessage());
    respond(false, 'Error: ' . $e->getMessage());
}
