<?php
/**
 * Debug Compartment Manager - Plot Loading
 * Tests why plots aren't populating in compartment-manager.php
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    die("Must be admin. <a href='login.php'>Login</a>");
}

echo "<h1>🔍 Compartment Manager Debug</h1>";
echo "<style>
body { font-family: system-ui; padding: 20px; background: #f5f5f5; }
.section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; border: 1px solid #e5e7eb; }
.success { border-left: 4px solid #10b981; background: #f0fff4; }
.error { border-left: 4px solid #ef4444; background: #fff5f5; }
.warning { border-left: 4px solid #f59e0b; background: #fffbeb; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; font-size: 13px; }
th { background: #f3f4f6; font-weight: 600; }
pre { background: #f8f9fa; padding: 12px; border-radius: 4px; overflow-x: auto; font-size: 11px; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-error { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }
</style>";

// Step 1: Check available_plots table structure
echo "<div class='section'>";
echo "<h2>Step 1: Database Table Check</h2>";

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM available_plots");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredCols = ['has_grid', 'grid_rows', 'grid_cols', 'grid_data'];
    $missingCols = [];
    
    foreach ($requiredCols as $col) {
        if (!array_filter($columns, fn($c) => $c['Field'] === $col)) {
            $missingCols[] = $col;
        }
    }
    
    if (empty($missingCols)) {
        echo "<p class='badge badge-success'>✓ All required columns exist</p>";
    } else {
        echo "<p class='badge badge-error'>✗ Missing columns: " . implode(', ', $missingCols) . "</p>";
    }
    
    echo "<details><summary>Show all columns</summary>";
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>" . ($col['Default'] ?? 'NULL') . "</td></tr>";
    }
    echo "</table></details>";
    
} catch (PDOException $e) {
    echo "<p class='badge badge-error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Step 2: Check plot_compartments table
echo "<div class='section'>";
echo "<h2>Step 2: Compartments Table Check</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'plot_compartments'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p class='badge badge-success'>✓ plot_compartments table exists</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM plot_compartments");
        $count = $stmt->fetch()['count'];
        echo "<p>Total compartments in database: <strong>{$count}</strong></p>";
    } else {
        echo "<p class='badge badge-error'>✗ plot_compartments table does NOT exist!</p>";
        echo "<p>Run the migration: <code>add_compartment_records.sql</code></p>";
    }
} catch (PDOException $e) {
    echo "<p class='badge badge-error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Step 3: Test the API directly
echo "<div class='section'>";
echo "<h2>Step 3: API Test (get_available_plots.php)</h2>";

ob_start();
include __DIR__ . '/../api/get_available_plots.php';
$apiResponse = ob_get_clean();

echo "<p><strong>Raw API Response:</strong></p>";
echo "<pre>" . htmlspecialchars(substr($apiResponse, 0, 500)) . (strlen($apiResponse) > 500 ? "...\n[truncated, showing first 500 chars]" : "") . "</pre>";

$apiData = json_decode($apiResponse, true);

if ($apiData && isset($apiData['success'])) {
    if ($apiData['success']) {
        echo "<p class='badge badge-success'>✓ API returned success</p>";
        
        $total = count($apiData['plots'] ?? []);
        echo "<p>Total plots returned: <strong>{$total}</strong></p>";
        
        // Check plots with grids
        $withGrids = array_filter($apiData['plots'] ?? [], fn($p) => $p['has_grid'] ?? false);
        $withCompartments = array_filter($withGrids, fn($p) => ($p['total_compartments'] ?? 0) > 0);
        
        echo "<p>Plots with has_grid=true: <strong>" . count($withGrids) . "</strong></p>";
        echo "<p>Plots with compartments: <strong>" . count($withCompartments) . "</strong></p>";
        
        if (count($withCompartments) === 0) {
            echo "<div class='section warning'>";
            echo "<h4>⚠ No Plots with Compartments Found!</h4>";
            echo "<p>This is why the dropdown is empty. Possible reasons:</p>";
            echo "<ul>";
            echo "<li><code>has_grid</code> is not set to 1 for any plots</li>";
            echo "<li><code>grid_rows</code> and <code>grid_cols</code> are not set</li>";
            echo "<li>No compartments created in <code>plot_compartments</code> table</li>";
            echo "</ul>";
            echo "</div>";
        }
        
    } else {
        echo "<p class='badge badge-error'>✗ API returned error: " . htmlspecialchars($apiData['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p class='badge badge-error'>✗ Invalid JSON response from API</p>";
}

echo "</div>";

// Step 4: Check actual database data
echo "<div class='section'>";
echo "<h2>Step 4: Database Query - Plots with Grids</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            ap.id,
            ap.plot_number,
            ap.has_grid,
            ap.grid_rows,
            ap.grid_cols,
            COUNT(pc.id) as compartment_count
        FROM available_plots ap
        LEFT JOIN plot_compartments pc ON pc.plot_id = ap.id
        WHERE ap.has_grid = 1
        GROUP BY ap.id
        ORDER BY ap.id DESC
    ");
    $plots = $stmt->fetchAll();
    
    if (count($plots) > 0) {
        echo "<p class='badge badge-success'>Found " . count($plots) . " plot(s) with has_grid=1</p>";
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Plot Number</th>";
        echo "<th>has_grid</th>";
        echo "<th>Rows</th>";
        echo "<th>Cols</th>";
        echo "<th>Compartments</th>";
        echo "<th>Status</th>";
        echo "</tr>";
        
        foreach ($plots as $plot) {
            $expected = ($plot['grid_rows'] ?? 0) * ($plot['grid_cols'] ?? 0);
            $actual = $plot['compartment_count'];
            $status = $actual > 0 ? 
                "<span class='badge badge-success'>✓ Has compartments</span>" : 
                "<span class='badge badge-warning'>⚠ No compartments</span>";
            
            echo "<tr>";
            echo "<td>{$plot['id']}</td>";
            echo "<td>" . htmlspecialchars($plot['plot_number'] ?: 'N/A') . "</td>";
            echo "<td>" . ($plot['has_grid'] ? '✓' : '✗') . "</td>";
            echo "<td>{$plot['grid_rows']}</td>";
            echo "<td>{$plot['grid_cols']}</td>";
            echo "<td>{$actual} / {$expected} expected</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='badge badge-warning'>⚠ No plots found with has_grid=1</p>";
        echo "<p>To fix this, you need to:</p>";
        echo "<ol>";
        echo "<li>Go to <a href='available-plots.php'>Available Plots</a></li>";
        echo "<li>Create or edit a plot</li>";
        echo "<li>Enable 'Enable Grid Layout'</li>";
        echo "<li>Set rows and columns</li>";
        echo "<li>Save the plot</li>";
        echo "</ol>";
    }
    
} catch (PDOException $e) {
    echo "<p class='badge badge-error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Step 5: Sample compartment data
echo "<div class='section'>";
echo "<h2>Step 5: Sample Compartment Data</h2>";

try {
    $stmt = $pdo->query("
        SELECT pc.*, ap.plot_number
        FROM plot_compartments pc
        LEFT JOIN available_plots ap ON ap.id = pc.plot_id
        LIMIT 10
    ");
    $compartments = $stmt->fetchAll();
    
    if (count($compartments) > 0) {
        echo "<p class='badge badge-success'>Found " . count($compartments) . " compartment(s) (showing max 10)</p>";
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Plot</th>";
        echo "<th>Compartment #</th>";
        echo "<th>Row</th>";
        echo "<th>Col</th>";
        echo "<th>Capacity</th>";
        echo "<th>Occupied</th>";
        echo "</tr>";
        
        foreach ($compartments as $comp) {
            echo "<tr>";
            echo "<td>{$comp['id']}</td>";
            echo "<td>" . htmlspecialchars($comp['plot_number'] ?: 'Plot #'.$comp['plot_id']) . "</td>";
            echo "<td>" . htmlspecialchars($comp['compartment_number'] ?? 'N/A') . "</td>";
            echo "<td>{$comp['row_index']}</td>";
            echo "<td>{$comp['col_index']}</td>";
            echo "<td>{$comp['capacity']}</td>";
            echo "<td>" . ($comp['is_occupied'] ? 'Yes' : 'No') . " ({$comp['occupied_count']})</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='badge badge-warning'>⚠ No compartments found in database</p>";
        echo "<p>Compartments should be created automatically when you save a plot with grid enabled.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='badge badge-error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Step 6: Recommendations
echo "<div class='section'>";
echo "<h2>📋 Diagnosis & Recommendations</h2>";

$issues = [];
$recommendations = [];

// Check if tables exist
try {
    $pdo->query("SELECT 1 FROM plot_compartments LIMIT 1");
} catch (PDOException $e) {
    $issues[] = "plot_compartments table doesn't exist";
    $recommendations[] = "Run the migration: <code>add_compartment_records.sql</code>";
}

// Check if any plots have grids
$stmt = $pdo->query("SELECT COUNT(*) as count FROM available_plots WHERE has_grid = 1");
if ($stmt->fetch()['count'] == 0) {
    $issues[] = "No plots have grid layout enabled";
    $recommendations[] = "Go to <a href='available-plots.php'>Available Plots</a> and enable grid layout on at least one plot";
}

// Check if compartments exist
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM plot_compartments");
    if ($stmt->fetch()['count'] == 0) {
        $issues[] = "No compartments in database";
        $recommendations[] = "Compartments are created when you save a plot with grid enabled. Try editing an existing plot or creating a new one with grid layout.";
    }
} catch (PDOException $e) {
    // Table doesn't exist, already handled above
}

if (empty($issues)) {
    echo "<div class='success' style='padding:20px;'>";
    echo "<h3 style='color:#047857;margin-top:0;'>✓ All Checks Passed!</h3>";
    echo "<p>The compartment manager should be working. If plots still don't appear:</p>";
    echo "<ul>";
    echo "<li>Clear your browser cache and reload</li>";
    echo "<li>Check browser console for JavaScript errors (F12 → Console tab)</li>";
    echo "<li>Make sure the plot has <code>total_compartments > 0</code> in the API response</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error' style='padding:20px;'>";
    echo "<h3 style='color:#991b1b;margin-top:0;'>✗ Issues Found</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>{$issue}</li>";
    }
    echo "</ul>";
    
    echo "<h4>Recommendations:</h4>";
    echo "<ol>";
    foreach ($recommendations as $rec) {
        echo "<li>{$rec}</li>";
    }
    echo "</ol>";
    echo "</div>";
}

echo "</div>";

// Quick links
echo "<div class='section'>";
echo "<h2>🔗 Quick Links</h2>";
echo "<p>";
echo "<a href='compartment-manager.php' style='display:inline-block;padding:8px 16px;background:#3b82f6;color:white;text-decoration:none;border-radius:6px;margin-right:10px;'>← Back to Compartment Manager</a>";
echo "<a href='available-plots.php' style='display:inline-block;padding:8px 16px;background:#10b981;color:white;text-decoration:none;border-radius:6px;margin-right:10px;'>Manage Plots</a>";
echo "</p>";
echo "</div>";
?>
