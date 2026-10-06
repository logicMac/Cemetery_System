<?php
/**
 * Get Deceased in a Grid/Compartment API
 * Returns all burial records that belong to a specific plot grid (compartment/fenced area)
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$grid_id = filter_input(INPUT_GET, 'grid_id', FILTER_VALIDATE_INT);

if (!$grid_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid grid ID']);
    exit;
}

try {
    // Get all burial records in this grid, joining through plot_grid_cells
    $stmt = $pdo->prepare("
        SELECT br.id, br.decedent_name, br.birth_date, br.death_date, br.burial_date,
               br.burial_time, br.barangay, br.plot_number, br.family_name, br.photo,
               br.latitude, br.longitude, br.is_fenced,
               pgc.row_idx AS cell_row, pgc.col_idx AS cell_col
        FROM burial_records br
        INNER JOIN plot_grid_cells pgc ON pgc.record_id = br.id
        WHERE pgc.grid_id = ?
        ORDER BY pgc.row_idx ASC, pgc.col_idx ASC
    ");
    $stmt->execute([$grid_id]);
    $deceased = $stmt->fetchAll();

    // Get grid info
    $gridStmt = $pdo->prepare("
        SELECT id, name, center_lat, center_lng, rows_count, cols_count
        FROM plot_grids
        WHERE id = ?
    ");
    $gridStmt->execute([$grid_id]);
    $grid = $gridStmt->fetch();

    echo json_encode([
        'success' => true,
        'grid' => $grid,
        'deceased' => $deceased,
        'count' => count($deceased)
    ]);
} catch (PDOException $e) {
    error_log("Get grid deceased error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
