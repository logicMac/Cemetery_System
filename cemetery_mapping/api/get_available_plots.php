<?php
/**
 * Get Available Plots API
 * Returns all burial plots with reservation status and compartment details
 */

header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $stmt = $pdo->query("
        SELECT 
            ap.id, ap.plot_number, ap.latitude, ap.longitude, ap.polygon, ap.notes, ap.photo, 
            ap.has_grid, ap.grid_rows, ap.grid_cols, ap.compartment_count,
            (SELECT pr.status FROM plot_reservations pr 
             WHERE pr.plot_id = ap.id AND pr.compartment_id IS NULL 
             AND pr.status IN ('pending', 'approved') 
             ORDER BY pr.reservation_date DESC LIMIT 1) as reservation_status,
            (SELECT COUNT(*) FROM plot_reservations pr 
             WHERE pr.plot_id = ap.id AND pr.status IN ('pending', 'approved')) as reservation_count,
            (SELECT COUNT(*) FROM plot_compartments pc WHERE pc.plot_id = ap.id) as total_compartments,
            (SELECT COUNT(*) FROM plot_compartments pc WHERE pc.plot_id = ap.id AND pc.is_occupied = 1) as occupied_compartments
        FROM available_plots ap
        ORDER BY ap.date_added DESC
    ");
    
    $plots = $stmt->fetchAll();

    // Detect optional schema pieces (compartment_records table + capacity columns)
    // so this endpoint still works if add_compartment_records.sql wasn't applied
    $hasCompartmentRecords = false;
    $hasCapacityCols = false;
    try {
        $pdo->query("SELECT 1 FROM compartment_records LIMIT 1");
        $hasCompartmentRecords = true;
    } catch (PDOException $e) { /* table missing */ }
    try {
        $pdo->query("SELECT capacity, occupied_count FROM plot_compartments LIMIT 1");
        $hasCapacityCols = true;
    } catch (PDOException $e) { /* columns missing */ }

    // Self-heal: ensure grid plots have their plot_compartments rows
    $insertComp = $pdo->prepare(
        "INSERT IGNORE INTO plot_compartments (plot_id, compartment_number, row_index, col_index) VALUES (?, ?, ?, ?)"
    );

    // Enrich with compartment details if has_grid
    foreach ($plots as &$plot) {
        if ($plot['has_grid']) {
            $rows = (int)$plot['grid_rows'];
            $cols = (int)$plot['grid_cols'];
            if ($rows > 0 && $cols > 0 && (int)$plot['total_compartments'] < $rows * $cols) {
                for ($r = 0; $r < $rows; $r++) {
                    for ($c = 0; $c < $cols; $c++) {
                        $insertComp->execute([$plot['id'], chr(65 + $r) . ($c + 1), $r, $c]);
                    }
                }
                $plot['total_compartments'] = $rows * $cols;
            }

            $extraCols = $hasCapacityCols
                ? "COALESCE(pc.capacity, 1) as capacity, COALESCE(pc.occupied_count, 0) as occupied_count,"
                : "1 as capacity, pc.is_occupied as occupied_count,";
            $recordCount = $hasCompartmentRecords
                ? "(SELECT COUNT(*) FROM compartment_records cr WHERE cr.compartment_id = pc.id) as record_count"
                : "0 as record_count";

            $compStmt = $pdo->prepare("
                SELECT
                    pc.id,
                    pc.compartment_number,
                    pc.row_index,
                    pc.col_index,
                    pc.is_occupied,
                    $extraCols
                    $recordCount
                FROM plot_compartments pc
                WHERE pc.plot_id = ?
                ORDER BY pc.row_index, pc.col_index
            ");
            $compStmt->execute([$plot['id']]);
            $plot['compartments'] = $compStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    echo json_encode([
        'success' => true,
        'plots' => $plots,
        'count' => count($plots)
    ]);
} catch (PDOException $e) {
    error_log("Get available plots error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
