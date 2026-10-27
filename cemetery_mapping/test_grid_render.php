<?php
// Debug page: does the visitor-side grid rendering apply grid_scale?
// Open: /test_grid_render.php on the same host as the app (e.g. /cemetery_mapping/test_grid_render.php)
require_once __DIR__ . '/config/database.php';

$dbInfo = [];
try {
    $col = $pdo->query("SHOW COLUMNS FROM plot_grids LIKE 'grid_scale'")->fetch();
    $dbInfo['column'] = $col ? "EXISTS ({$col['Type']})" : 'MISSING';
    $dbInfo['grids'] = $pdo->query("SELECT id, name, rows_count, cols_count, grid_scale, center_lat, center_lng FROM plot_grids WHERE status='active'")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dbInfo['error'] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Grid Scale Debug</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    body { font-family: monospace; margin: 0; display: flex; height: 100vh; }
    #panel { width: 420px; padding: 16px; overflow-y: auto; font-size: 13px; }
    #map { flex: 1; }
    table { border-collapse: collapse; width: 100%; margin: 8px 0; }
    td, th { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
    .ok { color: green; } .bad { color: red; font-weight: bold; }
    #console { background: #111; color: #0f0; padding: 8px; white-space: pre-wrap; font-size: 11px; max-height: 260px; overflow-y: auto; }
</style>
</head>
<body>
<div id="panel">
    <h3>Grid scale debug</h3>
    <h4>1. Database (server-side)</h4>
    <p>grid_scale column: <b><?php echo htmlspecialchars($dbInfo['column'] ?? $dbInfo['error'] ?? '?'); ?></b></p>
    <?php if (!empty($dbInfo['grids'])): ?>
    <table>
        <tr><th>id</th><th>name</th><th>rows</th><th>cols</th><th>scale in DB</th></tr>
        <?php foreach ($dbInfo['grids'] as $g): ?>
        <tr>
            <td><?php echo $g['id']; ?></td>
            <td><?php echo htmlspecialchars($g['name']); ?></td>
            <td><?php echo $g['rows_count']; ?></td>
            <td><?php echo $g['cols_count']; ?></td>
            <td class="<?php echo ((float)$g['grid_scale'] !== 1.0) ? 'ok' : ''; ?>"><?php echo $g['grid_scale']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <h4>2. API response + rendered size (browser-side)</h4>
    <div id="console">fetching grids.php?action=list ...</div>
</div>
<div id="map"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const log = m => { document.getElementById('console').textContent += '\n' + m; };
const map = L.map('map').setView([6.18344118743717, 125.08457146469357], 19);
L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', { maxZoom: 22, subdomains: ['mt0','mt1','mt2','mt3'] }).addTo(map);

// Exact same geometry as visitor.js drawStandaloneGrid
function drawGrid(grid) {
    const rows = parseInt(grid.rows);
    const cols = parseInt(grid.cols);
    const centerLat = parseFloat(grid.center_lat);
    const centerLng = parseFloat(grid.center_lng);
    const cellSize = 2 * (parseFloat(grid.scale) || 1);
    const angleRad = 45 * Math.PI / 180;
    const metersPerLat = 111320;
    const metersPerLng = 111320 * Math.cos(centerLat * Math.PI / 180);

    log(`grid #${grid.id} "${grid.name}": scale field = ${grid.scale} -> cellSize = ${cellSize.toFixed(2)}m, cells = ${rows}x${cols}, total width = ${(cols*cellSize).toFixed(1)}m`);

    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const offsetX = (col - cols / 2 + 0.5) * cellSize;
            const offsetY = ((rows - 1) / 2 - row) * cellSize;
            const rotatedX = offsetX * Math.cos(angleRad) - offsetY * Math.sin(angleRad);
            const rotatedY = offsetX * Math.sin(angleRad) + offsetY * Math.cos(angleRad);
            const cellLat = centerLat + (rotatedY / metersPerLat);
            const cellLng = centerLng + (rotatedX / metersPerLng);
            const h = cellSize / 2;
            const latLngs = [[-h,-h],[h,-h],[h,h],[-h,h]].map(c => {
                const rx = c[0] * Math.cos(angleRad) - c[1] * Math.sin(angleRad);
                const ry = c[0] * Math.sin(angleRad) + c[1] * Math.cos(angleRad);
                return [cellLat + ry / metersPerLat, cellLng + rx / metersPerLng];
            });
            L.polygon(latLngs, { color: '#10b981', weight: 2, fillOpacity: 0.2 }).addTo(map);
        }
    }
    L.marker([centerLat, centerLng]).addTo(map).bindTooltip(`${grid.name} (scale ${grid.scale})`, {permanent:true, direction:'top'});
}

fetch('api/grids.php?action=list')
    .then(r => { log('HTTP ' + r.status); return r.json(); })
    .then(data => {
        if (!data.success) { log('API error: ' + JSON.stringify(data)); return; }
        log('grids returned: ' + data.grids.length);
        data.grids.forEach(drawGrid);
    })
    .catch(e => log('fetch failed: ' + e));
</script>
</body>
</html>
