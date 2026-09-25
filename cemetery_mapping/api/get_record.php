<?php
/**
 * Get Single Burial Record API
 * Returns detailed information for a specific record
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT br.id, br.decedent_name, br.family_name, br.visitor_id, br.birth_date, br.death_date, br.burial_date, br.burial_time, br.is_buried, br.expiration_date, br.plot_number,
               br.barangay, br.memory_space, br.latitude, br.longitude, br.is_fenced, br.photo, br.date_added,
               pg.id AS grid_id, pg.name AS grid_name,
               pgc.row_idx AS cell_row, pgc.col_idx AS cell_col,
               pg.center_lat AS grid_lat, pg.center_lng AS grid_lng,
               pg.rows_count AS grid_rows, pg.cols_count AS grid_cols
        FROM burial_records br
        LEFT JOIN (
            SELECT c.record_id, c.grid_id, c.row_idx, c.col_idx
            FROM plot_grid_cells c
            INNER JOIN (
                SELECT record_id, MIN(id) AS min_id
                FROM plot_grid_cells
                WHERE record_id IS NOT NULL
                GROUP BY record_id
            ) m ON m.min_id = c.id
        ) pgc ON pgc.record_id = br.id
        LEFT JOIN plot_grids pg ON pg.id = pgc.grid_id
        WHERE br.id = ?
    ");
    
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    
    if ($record) {
        echo json_encode([
            'success' => true,
            'record' => $record
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Record not found'
        ]);
    }
} catch (PDOException $e) {
    error_log("Get record error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
