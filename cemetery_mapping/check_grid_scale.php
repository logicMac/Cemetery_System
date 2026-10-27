<?php
// Diagnostic: verify plot_grids.grid_scale column + stored values
// Open: http://localhost/client_cemeteryV2/cemetery_mapping/check_grid_scale.php
require_once __DIR__ . '/config/database.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    $cols = $pdo->query("SHOW COLUMNS FROM plot_grids LIKE 'grid_scale'")->fetchAll();
    if (!$cols) {
        echo "RESULT: grid_scale column MISSING\n";
        echo "FIX: run add_grid_scale.sql (ALTER TABLE plot_grids ADD COLUMN grid_scale DECIMAL(6,3) NOT NULL DEFAULT 1.000 AFTER cols_count)\n";
        exit;
    }
    echo "grid_scale column EXISTS: " . $cols[0]['Type'] . "\n\n";

    $rows = $pdo->query("SELECT id, name, grid_scale, center_lat, center_lng, date_updated FROM plot_grids")->fetchAll();
    foreach ($rows as $r) {
        echo "grid #{$r['id']} \"{$r['name']}\" scale={$r['grid_scale']} center=({$r['center_lat']},{$r['center_lng']}) updated={$r['date_updated']}\n";
    }

    echo "\n--- API action=list output (scale field) ---\n";
    $stmt = $pdo->query("SELECT pg.* FROM plot_grids pg WHERE pg.status='active'");
    foreach ($stmt->fetchAll() as $g) {
        $scale = isset($g['grid_scale']) ? (float)$g['grid_scale'] : 1.0;
        echo "grid #{$g['id']} -> scale sent to visitor.js: {$scale}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "If this says 'Unknown column grid_scale', apply add_grid_scale.sql\n";
}
