<?php
/**
 * Search Burial Records API
 * Searches by name, plot number, family name, or barangay
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$query = strip_tags((string)filter_input(INPUT_GET, 'q'));

if (empty($query) || strlen($query) < 2) {
    echo json_encode([
        'success' => false,
        'error' => 'Query too short'
    ]);
    exit;
}

try {
    $searchTerm = '%' . $query . '%';
    
    $stmt = $pdo->prepare("
        SELECT br.id, br.decedent_name, br.birth_date, br.death_date, br.burial_date, br.burial_time, br.expiration_date, br.barangay, br.plot_number,
               br.memory_space, br.latitude, br.longitude, br.polygon, br.is_fenced, br.family_name, br.photo,
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
        WHERE (br.decedent_name LIKE ?
           OR br.plot_number LIKE ?
           OR br.family_name LIKE ?
           OR br.barangay LIKE ?)
          AND br.latitude IS NOT NULL
          AND br.longitude IS NOT NULL
        ORDER BY br.decedent_name ASC
        LIMIT 50
    ");
    
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ]);
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
