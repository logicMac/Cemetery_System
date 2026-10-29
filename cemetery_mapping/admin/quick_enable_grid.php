<?php
/**
 * Quick Enable Grid Layout on Existing Plot
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    die("Must be admin. <a href='login.php'>Login</a>");
}

echo "<h1>🔧 Quick Enable Grid Layout</h1>";
echo "<style>
body { font-family: system-ui; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; border: 1px solid #e5e7eb; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #dee2e6; padding: 10px; text-align: left; }
th { background: #f3f4f6; font-weight: 600; }
.btn { display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
.btn-success { background: #10b981; }
.success { background: #f0fff4; border-left: 4px solid #10b981; padding: 15px; margin: 10px 0; }
.error { background: #fff5f5; border-left: 4px solid #ef4444; padding: 15px; margin: 10px 0; }
</style>";

// Get all plots
$stmt = $pdo->query("SELECT id, plot_number, latitude, longitude, has_grid, grid_rows, grid_cols FROM available_plots ORDER BY id DESC");
$plots = $stmt->fetchAll();

if (empty($plots)) {
    echo "<div class='error'>No plots found in the database. Please create one first.</div>";
    echo "<p><a href='available-plots.php' class='btn btn-success'>Create New Plot</a></p>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plot_id'])) {
    $plotId = (int)$_POST['plot_id'];
    $rows = (int)$_POST['rows'];
    $cols = (int)$_POST['cols'];
    
    if ($rows < 1 || $rows > 20 || $cols < 1 || $cols > 20) {
        echo "<div class='error'>Invalid rows/columns. Must be between 1 and 20.</div>";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Enable grid on the plot
            $stmt = $pdo->prepare("UPDATE available_plots SET has_grid = 1, grid_rows = ?, grid_cols = ?, compartment_count = ? WHERE id = ?");
            $stmt->execute([$rows, $cols, $rows * $cols, $plotId]);
            
            // Delete old compartments if any
            $pdo->prepare("DELETE FROM plot_compartments WHERE plot_id = ?")->execute([$plotId]);
            
            // Create compartments
            $insertStmt = $pdo->prepare("
                INSERT INTO plot_compartments 
                (plot_id, compartment_number, row_index, col_index, capacity, is_occupied, occupied_count)
                VALUES (?, ?, ?, ?, 1, 0, 0)
            ");
            
            for ($r = 0; $r < $rows; $r++) {
                for ($c = 0; $c < $cols; $c++) {
                    $compartmentNumber = chr(65 + $r) . ($c + 1); // A1, A2, B1, B2, etc.
                    $insertStmt->execute([$plotId, $compartmentNumber, $r, $c]);
                }
            }
            
            $pdo->commit();
            
            $totalComps = $rows * $cols;
            echo "<div class='success'>";
            echo "<h3>✓ Success!</h3>";
            echo "<p>Grid layout enabled on plot and {$totalComps} compartments created.</p>";
            echo "<p><a href='compartment-manager.php' class='btn btn-success'>Go to Compartment Manager</a></p>";
            echo "</div>";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "<div class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

echo "<div class='box'>";
echo "<h2>Select a Plot to Enable Grid Layout</h2>";
echo "<p>Choose a plot and set the grid dimensions. Compartments will be created automatically.</p>";

echo "<form method='POST'>";
echo "<table>";
echo "<tr>";
echo "<th>Select</th>";
echo "<th>ID</th>";
echo "<th>Plot Number</th>";
echo "<th>Current Grid Status</th>";
echo "<th>Rows</th>";
echo "<th>Columns</th>";
echo "</tr>";

foreach ($plots as $plot) {
    $gridStatus = $plot['has_grid'] ? 
        "<span style='color:#059669;font-weight:600;'>✓ Enabled ({$plot['grid_rows']}x{$plot['grid_cols']})</span>" : 
        "<span style='color:#6b7280;'>Not enabled</span>";
    
    echo "<tr>";
    echo "<td><input type='radio' name='plot_id' value='{$plot['id']}' required></td>";
    echo "<td>{$plot['id']}</td>";
    echo "<td>" . htmlspecialchars($plot['plot_number'] ?: 'Plot #'.$plot['id']) . "</td>";
    echo "<td>{$gridStatus}</td>";
    echo "<td><input type='number' name='rows' value='" . ($plot['grid_rows'] ?: 3) . "' min='1' max='20' style='width:60px;padding:5px;border:1px solid #d1d5db;border-radius:4px;'></td>";
    echo "<td><input type='number' name='cols' value='" . ($plot['grid_cols'] ?: 4) . "' min='1' max='20' style='width:60px;padding:5px;border:1px solid #d1d5db;border-radius:4px;'></td>";
    echo "</tr>";
}

echo "</table>";
echo "<p style='margin-top:20px;'>";
echo "<button type='submit' class='btn btn-success'>Enable Grid & Create Compartments</button>";
echo " <span style='margin-left:10px;color:#6b7280;font-size:14px;'>This will create compartments based on the rows×columns you specify</span>";
echo "</p>";
echo "</form>";

echo "</div>";

echo "<div class='box'>";
echo "<h2>📝 Notes</h2>";
echo "<ul>";
echo "<li>Compartments are labeled A1, A2, B1, B2, etc. (Row letter + Column number)</li>";
echo "<li>Each compartment starts with capacity=1 (can be changed later)</li>";
echo "<li>If grid is already enabled on a plot, this will recreate all compartments</li>";
echo "<li>Make sure the plot has valid latitude/longitude coordinates</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='debug_compartments.php' class='btn'>← Back to Debug</a> <a href='compartment-manager.php' class='btn'>Go to Compartment Manager</a></p>";
?>
