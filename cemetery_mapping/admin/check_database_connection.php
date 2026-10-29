<?php
/**
 * Database Connection & Content Check
 * Shows what database we're connected to and what data exists
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    die("Must be admin. <a href='login.php'>Login</a>");
}

echo "<h1>🔍 Database Connection Check</h1>";
echo "<style>
body { font-family: system-ui; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; border: 1px solid #e5e7eb; }
.success { background: #f0fff4; border-left: 4px solid #10b981; }
.error { background: #fff5f5; border-left: 4px solid #ef4444; }
.warning { background: #fffbeb; border-left: 4px solid #f59e0b; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #dee2e6; padding: 10px; text-align: left; }
th { background: #f3f4f6; font-weight: 600; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

require_once '../config/database.php';

// Check 1: Connection Info
echo "<div class='box success'>";
echo "<h2>✓ Database Connection</h2>";
echo "<table>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>Host</td><td>" . DB_HOST . "</td></tr>";
echo "<tr><td>Database Name</td><td><strong>" . DB_NAME . "</strong></td></tr>";
echo "<tr><td>Username</td><td>" . DB_USER . "</td></tr>";
echo "<tr><td>Connection Status</td><td style='color:#059669;font-weight:bold;'>✓ Connected</td></tr>";
echo "</table>";
echo "</div>";

// Check 2: Database Name Verification
echo "<div class='box'>";
echo "<h2>Current Database</h2>";
try {
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $currentDb = $stmt->fetch()['db_name'];
    echo "<p>Currently connected to database: <strong style='font-size:18px;color:#3b82f6;'>{$currentDb}</strong></p>";
    
    if ($currentDb !== DB_NAME) {
        echo "<p style='color:#dc2626;'>⚠ WARNING: Connected to '{$currentDb}' but config says '{DB_NAME}'</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:#dc2626;'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check 3: Tables in Database
echo "<div class='box'>";
echo "<h2>Tables in Database</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Found <strong>" . count($tables) . "</strong> table(s):</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    
    $requiredTables = ['available_plots', 'burial_records', 'plot_compartments', 'visitors', 'admin_users'];
    $missingTables = array_diff($requiredTables, $tables);
    
    if (!empty($missingTables)) {
        echo "<p style='color:#dc2626;'>⚠ Missing required tables: " . implode(', ', $missingTables) . "</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:#dc2626;'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check 4: Row Counts
echo "<div class='box'>";
echo "<h2>Data Summary</h2>";
try {
    $tables = [
        'available_plots' => 'Available Plots',
        'burial_records' => 'Burial Records',
        'plot_compartments' => 'Compartments',
        'plot_reservations' => 'Reservations',
        'visitors' => 'Visitors',
        'admin_users' => 'Admin Users'
    ];
    
    echo "<table>";
    echo "<tr><th>Table</th><th>Row Count</th></tr>";
    
    foreach ($tables as $table => $label) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $stmt->fetch()['count'];
            $style = $count > 0 ? 'color:#059669;font-weight:bold;' : 'color:#6b7280;';
            echo "<tr><td>{$label}</td><td style='{$style}'>{$count}</td></tr>";
        } catch (PDOException $e) {
            echo "<tr><td>{$label}</td><td style='color:#dc2626;'>Table not found</td></tr>";
        }
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color:#dc2626;'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check 5: Available Plots Details
echo "<div class='box'>";
echo "<h2>Available Plots Details</h2>";
try {
    $stmt = $pdo->query("
        SELECT id, plot_number, has_grid, grid_rows, grid_cols, 
               latitude, longitude, date_added
        FROM available_plots 
        ORDER BY id DESC 
        LIMIT 10
    ");
    $plots = $stmt->fetchAll();
    
    if (count($plots) > 0) {
        echo "<p>Showing latest 10 plot(s):</p>";
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Plot Number</th>";
        echo "<th>Has Grid</th>";
        echo "<th>Rows</th>";
        echo "<th>Cols</th>";
        echo "<th>Lat/Lng</th>";
        echo "<th>Date Added</th>";
        echo "</tr>";
        
        foreach ($plots as $plot) {
            echo "<tr>";
            echo "<td>{$plot['id']}</td>";
            echo "<td>" . htmlspecialchars($plot['plot_number'] ?: 'N/A') . "</td>";
            echo "<td>" . ($plot['has_grid'] ? '✓ Yes' : '✗ No') . "</td>";
            echo "<td>" . ($plot['grid_rows'] ?: '-') . "</td>";
            echo "<td>" . ($plot['grid_cols'] ?: '-') . "</td>";
            echo "<td>" . ($plot['latitude'] && $plot['longitude'] ? '✓' : '✗') . "</td>";
            echo "<td>" . date('M d, Y', strtotime($plot['date_added'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:#dc2626;font-weight:bold;'>⚠ NO PLOTS FOUND IN available_plots TABLE!</p>";
        echo "<p>This explains why the dropdown is empty. Your plots might be in a different database.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:#dc2626;'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check 6: Burial Records Count
echo "<div class='box'>";
echo "<h2>Burial Records Sample</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM burial_records");
    $count = $stmt->fetch()['count'];
    
    echo "<p>Total burial records: <strong>{$count}</strong></p>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, decedent_name, plot_number, date_of_death FROM burial_records ORDER BY id DESC LIMIT 5");
        $records = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Plot Number</th><th>Date of Death</th></tr>";
        foreach ($records as $r) {
            echo "<tr>";
            echo "<td>{$r['id']}</td>";
            echo "<td>" . htmlspecialchars($r['decedent_name']) . "</td>";
            echo "<td>" . htmlspecialchars($r['plot_number'] ?: 'N/A') . "</td>";
            echo "<td>" . ($r['date_of_death'] ?: 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color:#dc2626;'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Summary
echo "<div class='box warning'>";
echo "<h2>💡 What This Means</h2>";
echo "<p><strong>If you see 'NO PLOTS FOUND' above but you know you have plots:</strong></p>";
echo "<ul>";
echo "<li>Your production database might have a different name than the local one</li>";
echo "<li>The plots might be in a different database on your hosting</li>";
echo "<li>Check your hosting control panel (cPanel/DirectAdmin) to verify the database name</li>";
echo "<li>Update <code>config/database.php</code> with the correct production database credentials</li>";
echo "</ul>";
echo "<p><strong>If you DO see plots above:</strong></p>";
echo "<ul>";
echo "<li>The connection is working correctly</li>";
echo "<li>Use the <a href='quick_enable_grid.php'>Quick Enable Grid tool</a> to enable grid layout on plots</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='quick_enable_grid.php' style='display:inline-block;padding:10px 20px;background:#3b82f6;color:white;text-decoration:none;border-radius:6px;'>Try Quick Enable Grid Again</a></p>";
?>
