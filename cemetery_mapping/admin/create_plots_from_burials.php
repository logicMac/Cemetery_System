<?php
/**
 * Create Available Plots from Existing Burial Records
 * Extracts unique plot locations from burial records and creates corresponding available plots
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    die("Must be admin. <a href='login.php'>Login</a>");
}

echo "<h1>🗺️ Create Available Plots from Burial Records</h1>";
echo "<style>
body { font-family: system-ui; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; border: 1px solid #e5e7eb; }
.success { background: #f0fff4; border-left: 4px solid #10b981; }
.error { background: #fff5f5; border-left: 4px solid #ef4444; }
.warning { background: #fffbeb; border-left: 4px solid #f59e0b; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; font-size: 13px; }
th { background: #f3f4f6; font-weight: 600; }
.btn { display: inline-block; padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
</style>";

// Analyze burial records
try {
    $stmt = $pdo->query("
        SELECT 
            plot_number,
            MIN(latitude) as lat,
            MIN(longitude) as lng,
            COUNT(*) as burial_count,
            GROUP_CONCAT(decedent_name SEPARATOR ', ') as names
        FROM burial_records
        WHERE plot_number IS NOT NULL 
          AND plot_number != ''
          AND latitude IS NOT NULL
          AND longitude IS NOT NULL
        GROUP BY plot_number
        ORDER BY plot_number
    ");
    $uniquePlots = $stmt->fetchAll();
    
    echo "<div class='box'>";
    echo "<h2>📊 Analysis</h2>";
    echo "<p>Found <strong>" . count($uniquePlots) . "</strong> unique plot(s) with coordinates from your 32 burial records.</p>";
    
    if (count($uniquePlots) > 0) {
        echo "<table>";
        echo "<tr>";
        echo "<th>Plot Number</th>";
        echo "<th>Burials</th>";
        echo "<th>Lat/Lng</th>";
        echo "<th>Names</th>";
        echo "</tr>";
        
        foreach ($uniquePlots as $plot) {
            $names = substr($plot['names'], 0, 100) . (strlen($plot['names']) > 100 ? '...' : '');
            echo "<tr>";
            echo "<td><strong>{$plot['plot_number']}</strong></td>";
            echo "<td>{$plot['burial_count']}</td>";
            echo "<td style='font-size:11px;'>{$plot['lat']}, {$plot['lng']}</td>";
            echo "<td style='font-size:11px;'>" . htmlspecialchars($names) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>";
        echo "<p>⚠ No burial records have both plot_number AND coordinates set.</p>";
        echo "<p>Cannot automatically create available plots without location data.</p>";
        echo "</div>";
    }
    echo "</div>";
    
    // Process creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_plots'])) {
        $rows = (int)$_POST['default_rows'];
        $cols = (int)$_POST['default_cols'];
        
        if ($rows < 1 || $cols < 1 || $rows > 20 || $cols > 20) {
            echo "<div class='box error'><p>Invalid grid size. Rows and columns must be between 1 and 20.</p></div>";
        } else {
            echo "<div class='box'>";
            echo "<h2>Creating Plots...</h2>";
            
            $created = 0;
            $skipped = 0;
            
            try {
                $pdo->beginTransaction();
                
                foreach ($uniquePlots as $plot) {
                    // Check if plot already exists
                    $check = $pdo->prepare("SELECT id FROM available_plots WHERE plot_number = ?");
                    $check->execute([$plot['plot_number']]);
                    
                    if ($check->fetch()) {
                        echo "<p style='color:#6b7280;'>⊘ Skipped {$plot['plot_number']} (already exists)</p>";
                        $skipped++;
                        continue;
                    }
                    
                    // Create the available plot
                    $insert = $pdo->prepare("
                        INSERT INTO available_plots 
                        (plot_number, latitude, longitude, notes, has_grid, grid_rows, grid_cols, compartment_count, added_by, date_added)
                        VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, NOW())
                    ");
                    $insert->execute([
                        $plot['plot_number'],
                        $plot['lat'],
                        $plot['lng'],
                        "Auto-created from {$plot['burial_count']} existing burial record(s)",
                        $rows,
                        $cols,
                        $rows * $cols,
                        $_SESSION['admin_username'] ?? 'admin'
                    ]);
                    
                    $plotId = $pdo->lastInsertId();
                    
                    // Create compartments
                    $insertComp = $pdo->prepare("
                        INSERT INTO plot_compartments 
                        (plot_id, compartment_number, row_index, col_index, capacity, is_occupied, occupied_count)
                        VALUES (?, ?, ?, ?, 1, 0, 0)
                    ");
                    
                    for ($r = 0; $r < $rows; $r++) {
                        for ($c = 0; $c < $cols; $c++) {
                            $compNumber = chr(65 + $r) . ($c + 1);
                            $insertComp->execute([$plotId, $compNumber, $r, $c]);
                        }
                    }
                    
                    echo "<p style='color:#059669;'>✓ Created plot '{$plot['plot_number']}' with " . ($rows * $cols) . " compartments</p>";
                    $created++;
                }
                
                $pdo->commit();
                
                echo "<div class='success' style='margin-top:20px;'>";
                echo "<h3>✓ Complete!</h3>";
                echo "<p><strong>{$created}</strong> plot(s) created</p>";
                if ($skipped > 0) echo "<p><strong>{$skipped}</strong> plot(s) skipped (already existed)</p>";
                echo "<p><a href='compartment-manager.php' class='btn'>Go to Compartment Manager</a></p>";
                echo "</div>";
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "<p style='color:#dc2626;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
            echo "</div>";
        }
    }
    
    // Show form if plots found
    if (count($uniquePlots) > 0 && !isset($_POST['create_plots'])) {
        echo "<div class='box'>";
        echo "<h2>Create Available Plots</h2>";
        echo "<p>This will create one 'Available Plot' entry for each unique plot number above, with grid compartments enabled.</p>";
        
        echo "<form method='POST'>";
        echo "<div style='margin:15px 0;'>";
        echo "<label style='display:block;margin-bottom:5px;font-weight:600;'>Default Grid Size for All Plots:</label>";
        echo "<label style='display:inline-block;margin-right:20px;'>";
        echo "Rows: <input type='number' name='default_rows' value='3' min='1' max='20' style='width:60px;padding:5px;border:1px solid #d1d5db;border-radius:4px;'>";
        echo "</label>";
        echo "<label style='display:inline-block;'>";
        echo "Columns: <input type='number' name='default_cols' value='4' min='1' max='20' style='width:60px;padding:5px;border:1px solid #d1d5db;border-radius:4px;'>";
        echo "</label>";
        echo "<p style='font-size:13px;color:#6b7280;margin-top:5px;'>Each plot will have a 3×4 grid (12 compartments) by default</p>";
        echo "</div>";
        echo "<button type='submit' name='create_plots' class='btn'>Create " . count($uniquePlots) . " Plot(s) with Compartments</button>";
        echo "</form>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='box error'>";
    echo "<p>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<p><a href='check_database_connection.php' style='padding:8px 16px;background:#6b7280;color:white;text-decoration:none;border-radius:6px;display:inline-block;'>← Back to Database Check</a></p>";
?>
