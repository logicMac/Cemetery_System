<?php
/**
 * Database Content Checker
 * Verifies what data is in the database
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #0a0a0a;
            color: white;
        }
        .section {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        th {
            background: rgba(0, 230, 118, 0.3);
        }
        .success { color: #5a9b6f; }
        .error { color: #b55a5a; }
        .warning { color: #c9a86c; }
    </style>
</head>
<body>
    <h1>Cemetery Database Content Check</h1>
    
    <?php
    // Check burial records
    echo '<div class="section">';
    echo '<h2>Burial Records</h2>';
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM burial_records");
        $total = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as with_coords FROM burial_records WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
        $withCoords = $stmt->fetch()['with_coords'];
        
        echo "<p><strong>Total records:</strong> $total</p>";
        echo "<p><strong>Records with coordinates:</strong> <span class='success'>$withCoords</span></p>";
        
        if ($withCoords == 0) {
            echo "<p class='warning'>⚠ No burial records have coordinates! They won't show on the map.</p>";
            echo "<p>Add coordinates to your records in the admin panel.</p>";
        } else {
            // Show sample records
            $stmt = $pdo->query("
                SELECT id, decedent_name, plot_number, latitude, longitude, is_fenced 
                FROM burial_records 
                WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
                LIMIT 5
            ");
            $records = $stmt->fetchAll();
            
            echo "<h3>Sample Records (first 5 with coordinates):</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Plot</th><th>Latitude</th><th>Longitude</th><th>Type</th></tr>";
            foreach ($records as $record) {
                $type = $record['is_fenced'] ? 'Premium' : 'Standard';
                echo "<tr>";
                echo "<td>{$record['id']}</td>";
                echo "<td>{$record['decedent_name']}</td>";
                echo "<td>{$record['plot_number']}</td>";
                echo "<td>{$record['latitude']}</td>";
                echo "<td>{$record['longitude']}</td>";
                echo "<td>{$type}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
    
    echo '</div>';
    
    // Check available plots
    echo '<div class="section">';
    echo '<h2>Available Plots</h2>';
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM available_plots");
        $total = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as with_coords FROM available_plots WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
        $withCoords = $stmt->fetch()['with_coords'];
        
        echo "<p><strong>Total plots:</strong> $total</p>";
        echo "<p><strong>Plots with coordinates:</strong> <span class='success'>$withCoords</span></p>";
        
        if ($withCoords == 0) {
            echo "<p class='warning'>⚠ No available plots have coordinates! They won't show on the map.</p>";
            echo "<p>Add plots with coordinates in the admin panel.</p>";
        } else {
            // Show sample plots
            $stmt = $pdo->query("
                SELECT id, plot_number, latitude, longitude, has_grid, grid_rows, grid_cols 
                FROM available_plots 
                WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
                LIMIT 5
            ");
            $plots = $stmt->fetchAll();
            
            echo "<h3>Sample Plots (first 5 with coordinates):</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Plot Number</th><th>Latitude</th><th>Longitude</th><th>Grid</th></tr>";
            foreach ($plots as $plot) {
                $grid = $plot['has_grid'] ? "{$plot['grid_rows']}×{$plot['grid_cols']}" : 'No';
                echo "<tr>";
                echo "<td>{$plot['id']}</td>";
                echo "<td>{$plot['plot_number']}</td>";
                echo "<td>{$plot['latitude']}</td>";
                echo "<td>{$plot['longitude']}</td>";
                echo "<td>{$grid}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
    
    echo '</div>';
    
    // Summary
    echo '<div class="section">';
    echo '<h2>Summary</h2>';
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM burial_records WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
        $burialCount = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM available_plots WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
        $plotCount = $stmt->fetch()['total'];
        
        $totalMarkers = $burialCount + $plotCount;
        
        echo "<p><strong>Total markers that should appear on map:</strong> <span class='success'>{$totalMarkers}</span></p>";
        echo "<ul>";
        echo "<li>{$burialCount} burial record markers</li>";
        echo "<li>{$plotCount} available plot markers</li>";
        echo "</ul>";
        
        if ($totalMarkers == 0) {
            echo "<p class='error'><strong>⚠ NO MARKERS WILL SHOW ON MAP!</strong></p>";
            echo "<p>You need to:</p>";
            echo "<ol>";
            echo "<li>Go to Admin → Records</li>";
            echo "<li>Add a burial record with latitude and longitude</li>";
            echo "<li>Or go to Admin → Available Plots and add a plot with coordinates</li>";
            echo "</ol>";
        } else {
            echo "<p class='success'><strong>✓ Map should display {$totalMarkers} markers</strong></p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
    
    echo '</div>';
    ?>
    
    <div class="section">
        <h2>Quick Actions</h2>
        <p><a href="admin/dashboard.php" style="color: #00c853;">→ Go to Admin Dashboard</a></p>
        <p><a href="admin/records.php" style="color: #00c853;">→ Manage Records</a></p>
        <p><a href="admin/available-plots.php" style="color: #00c853;">→ Manage Available Plots</a></p>
        <p><a href="admin/map-view.php" style="color: #00c853;">→ View Map</a></p>
        <p><a href="test_api.html" style="color: #00c853;">→ Test APIs</a></p>
    </div>
</body>
</html>
