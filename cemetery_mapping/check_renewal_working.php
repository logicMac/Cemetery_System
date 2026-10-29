<?php
/**
 * Comprehensive Renewal System Check
 * Tests if renewals are working and diagnoses any issues
 */

session_start();
require_once 'config/database.php';

if (!isset($_SESSION['admin_id'])) {
    die("Please log in as admin first. <a href='admin/login.php'>Login</a>");
}

$issues = [];
$warnings = [];

echo "<style>
body { font-family: system-ui; padding: 20px; background: #f8f9fa; }
.box { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; border: 1px solid #dee2e6; }
h2 { margin-top: 0; color: #212529; }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
th { background: #e9ecef; font-weight: 600; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-weight: 600; }
.btn:hover { background: #0056b3; }
</style>";

echo "<h1>🔍 Renewal System Diagnostic</h1>";

// ===== Check 1: Table Schema =====
echo "<div class='box'>";
echo "<h2>1. Database Schema Check</h2>";

try {
    // Check or_number column
    $stmt = $pdo->query("SHOW COLUMNS FROM plot_renewal_requests LIKE 'or_number'");
    $hasOrNumber = $stmt->fetch();
    
    if ($hasOrNumber) {
        echo "<p class='success'>✓ or_number column exists</p>";
    } else {
        echo "<p class='error'>✗ or_number column is MISSING</p>";
        $issues[] = "Missing or_number column in plot_renewal_requests table";
    }
    
    // Check visitor_id nullability
    $stmt = $pdo->query("SHOW COLUMNS FROM plot_renewal_requests LIKE 'visitor_id'");
    $col = $stmt->fetch();
    $isNullable = ($col && $col['Null'] === 'YES');
    
    if ($isNullable) {
        echo "<p class='success'>✓ visitor_id is nullable (can be NULL)</p>";
    } else {
        echo "<p class='error'>✗ visitor_id is NOT NULL (must have a value)</p>";
        $issues[] = "visitor_id column must be nullable for admin-direct renewals";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
    $issues[] = "Cannot check database schema: " . $e->getMessage();
}

if (!empty($issues)) {
    echo "<p><strong class='error'>⚠ Schema issues found!</strong> Run the fix:</p>";
    echo "<pre>Open check_renewal_schema.php and click 'Apply Fix Now'</pre>";
} else {
    echo "<p class='success'><strong>✓ Schema is correct</strong></p>";
}

echo "</div>";

// ===== Check 2: Error Log =====
echo "<div class='box'>";
echo "<h2>2. Recent Error Log</h2>";

$errorLogPath = __DIR__ . '/renewal_errors.log';
if (file_exists($errorLogPath)) {
    $errors = file_get_contents($errorLogPath);
    if (trim($errors)) {
        echo "<p class='warning'>⚠ Errors detected in log:</p>";
        echo "<pre style='max-height: 200px; overflow-y: auto;'>" . htmlspecialchars($errors) . "</pre>";
        echo "<p><a href='?clear_log=1' class='btn'>Clear Error Log</a></p>";
        $warnings[] = "Error log contains entries";
    } else {
        echo "<p class='success'>✓ No errors in log</p>";
    }
} else {
    echo "<p>No error log file found (this is normal if no errors occurred)</p>";
}

if (isset($_GET['clear_log']) && file_exists($errorLogPath)) {
    file_put_contents($errorLogPath, '');
    echo "<p class='success'>✓ Error log cleared</p>";
    echo "<meta http-equiv='refresh' content='1;url=check_renewal_working.php'>";
}

echo "</div>";

// ===== Check 3: Sample Expired Plot =====
echo "<div class='box'>";
echo "<h2>3. Test Data</h2>";

try {
    // Find an expired plot
    $stmt = $pdo->query("
        SELECT 'burial' as type, id, decedent_name as name, plot_number, expiration_date, renewal_count
        FROM burial_records 
        WHERE expiration_date IS NOT NULL AND expiration_date < CURDATE()
        LIMIT 1
    ");
    $testPlot = $stmt->fetch();
    
    if (!$testPlot) {
        $stmt = $pdo->query("
            SELECT 'available' as type, id, plot_number as name, plot_number, expiration_date, renewal_count
            FROM available_plots 
            WHERE expiration_date IS NOT NULL AND expiration_date < CURDATE()
            LIMIT 1
        ");
        $testPlot = $stmt->fetch();
    }
    
    if ($testPlot) {
        echo "<p class='success'>✓ Found expired plot for testing</p>";
        echo "<table>";
        echo "<tr><th>Type</th><td>{$testPlot['type']}</td></tr>";
        echo "<tr><th>ID</th><td>{$testPlot['id']}</td></tr>";
        echo "<tr><th>Name</th><td>" . htmlspecialchars($testPlot['name']) . "</td></tr>";
        echo "<tr><th>Plot Number</th><td>" . ($testPlot['plot_number'] ?: 'N/A') . "</td></tr>";
        echo "<tr><th>Expiration Date</th><td>{$testPlot['expiration_date']}</td></tr>";
        echo "<tr><th>Renewal Count</th><td>{$testPlot['renewal_count']}</td></tr>";
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ No expired plots found to test with</p>";
        $warnings[] = "No expired plots available for testing";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Error finding test plot: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ===== Check 4: Recent Renewals =====
echo "<div class='box'>";
echo "<h2>4. Recent Renewal Activity</h2>";

try {
    $stmt = $pdo->query("
        SELECT r.*, 
               b.decedent_name, b.plot_number as burial_plot,
               ap.plot_number as available_plot,
               au.username as admin_name
        FROM plot_renewal_requests r
        LEFT JOIN burial_records b ON b.id = r.record_id
        LEFT JOIN available_plots ap ON ap.id = r.plot_id
        LEFT JOIN admin_users au ON au.id = r.reviewed_by
        WHERE r.status = 'approved'
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $renewals = $stmt->fetchAll();
    
    if (!empty($renewals)) {
        echo "<p class='success'>✓ Found " . count($renewals) . " recent renewal(s)</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Plot</th><th>OR Number</th><th>Type</th><th>Status</th><th>Date</th></tr>";
        foreach ($renewals as $r) {
            $plotName = $r['plot_type'] === 'burial' 
                ? htmlspecialchars($r['decedent_name'] ?: 'Unknown')
                : htmlspecialchars($r['available_plot'] ?: 'Plot #'.$r['plot_id']);
            echo "<tr>";
            echo "<td>#{$r['id']}</td>";
            echo "<td>{$plotName}</td>";
            echo "<td>" . htmlspecialchars($r['or_number'] ?? 'N/A') . "</td>";
            echo "<td>{$r['plot_type']}</td>";
            echo "<td>{$r['status']}</td>";
            echo "<td>{$r['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ No approved renewals found in database</p>";
        $warnings[] = "No renewal history found";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Error checking renewals: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ===== Summary =====
echo "<div class='box'>";
echo "<h2>📊 Summary</h2>";

if (empty($issues)) {
    echo "<p class='success' style='font-size: 18px;'>✓ No critical issues found!</p>";
    
    if (!empty($warnings)) {
        echo "<p class='warning'>⚠ " . count($warnings) . " warning(s):</p>";
        echo "<ul>";
        foreach ($warnings as $w) {
            echo "<li>{$w}</li>";
        }
        echo "</ul>";
    }
    
    if (isset($testPlot)) {
        echo "<hr>";
        echo "<h3>Test the Renewal System</h3>";
        echo "<p>Go to the expiring plots page and try renewing the expired plot found above:</p>";
        echo "<a href='admin/expiring-plots.php' class='btn'>Go to Expiring Plots</a>";
    }
    
} else {
    echo "<p class='error' style='font-size: 18px;'>✗ " . count($issues) . " issue(s) found:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li class='error'>{$issue}</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>Fix Required</h3>";
    echo "<p>Run the schema fix to resolve these issues:</p>";
    echo "<a href='check_renewal_schema.php' class='btn'>Open Schema Fix Tool</a>";
}

echo "</div>";

// ===== Quick Links =====
echo "<div class='box'>";
echo "<h2>🔗 Quick Links</h2>";
echo "<p>";
echo "<a href='check_renewal_schema.php' class='btn'>Schema Fix Tool</a> ";
echo "<a href='admin/expiring-plots.php' class='btn'>Expiring Plots</a> ";
echo "<a href='admin/renewals.php' class='btn'>Renewal History</a> ";
echo "<a href='test_renewal_debug.php' class='btn'>Debug Test</a>";
echo "</p>";
echo "</div>";
?>
