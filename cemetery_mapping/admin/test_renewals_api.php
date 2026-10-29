<?php
/**
 * Test the renewals API endpoint
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    die("Must be admin. <a href='login.php'>Login</a>");
}

echo "<h1>🔍 Testing Renewals API</h1>";
echo "<style>
body { font-family: system-ui; padding: 20px; background: #f5f5f5; }
pre { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6; overflow-x: auto; }
.success { background: #f0fff4; border-color: #10b981; }
.error { background: #fff5f5; border-color: #ef4444; }
</style>";

// Test the exact API call that renewals.php makes
echo "<h2>Testing: /api/renew_plot.php?action=admin_list&status=approved</h2>";

// Simulate the API call
$_GET['action'] = 'admin_list';
$_GET['status'] = 'approved';

ob_start();
include __DIR__ . '/../api/renew_plot.php';
$response = ob_get_clean();

echo "<h3>API Response:</h3>";
$data = json_decode($response, true);

if ($data) {
    echo "<pre class='success'>";
    echo json_encode($data, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    if ($data['success']) {
        $count = count($data['requests'] ?? []);
        echo "<p style='color:#10b981;font-weight:bold;font-size:18px;'>✓ API returned success with {$count} renewal(s)</p>";
        
        if ($count > 0) {
            echo "<h3>Renewal Details:</h3>";
            echo "<table style='width:100%;border-collapse:collapse;'>";
            echo "<tr style='background:#f3f4f6;'>";
            echo "<th style='border:1px solid #dee2e6;padding:8px;'>ID</th>";
            echo "<th style='border:1px solid #dee2e6;padding:8px;'>Plot Type</th>";
            echo "<th style='border:1px solid #dee2e6;padding:8px;'>Record/Plot ID</th>";
            echo "<th style='border:1px solid #dee2e6;padding:8px;'>OR Number</th>";
            echo "<th style='border:1px solid #dee2e6;padding:8px;'>Status</th>";
            echo "<th style='border:1px solid #dee2e6;padding:8px;'>Created</th>";
            echo "</tr>";
            
            foreach ($data['requests'] as $r) {
                echo "<tr>";
                echo "<td style='border:1px solid #dee2e6;padding:8px;'>{$r['id']}</td>";
                echo "<td style='border:1px solid #dee2e6;padding:8px;'>{$r['plot_type']}</td>";
                echo "<td style='border:1px solid #dee2e6;padding:8px;'>";
                echo $r['plot_type'] === 'burial' ? "Record: {$r['record_id']}" : "Plot: {$r['plot_id']}";
                echo "</td>";
                echo "<td style='border:1px solid #dee2e6;padding:8px;'>" . htmlspecialchars($r['or_number'] ?? 'N/A') . "</td>";
                echo "<td style='border:1px solid #dee2e6;padding:8px;'>{$r['status']}</td>";
                echo "<td style='border:1px solid #dee2e6;padding:8px;'>{$r['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:#f59e0b;font-weight:bold;'>⚠ No renewals found in database</p>";
        }
    } else {
        echo "<p style='color:#ef4444;font-weight:bold;'>✗ API returned error: {$data['message']}</p>";
    }
} else {
    echo "<pre class='error'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    echo "<p style='color:#ef4444;font-weight:bold;'>✗ Invalid JSON response</p>";
}

// Also check database directly
echo "<hr>";
echo "<h2>Direct Database Query</h2>";

$stmt = $pdo->query("
    SELECT r.*,
           b.decedent_name, b.plot_number as burial_plot,
           ap.plot_number as available_plot
    FROM plot_renewal_requests r
    LEFT JOIN burial_records b ON b.id = r.record_id
    LEFT JOIN available_plots ap ON ap.id = r.plot_id
    WHERE r.status = 'approved'
    ORDER BY r.created_at DESC
");
$rows = $stmt->fetchAll();

echo "<p><strong>Found " . count($rows) . " approved renewal(s) in database</strong></p>";

if (count($rows) > 0) {
    echo "<table style='width:100%;border-collapse:collapse;'>";
    echo "<tr style='background:#f3f4f6;'>";
    echo "<th style='border:1px solid #dee2e6;padding:8px;'>ID</th>";
    echo "<th style='border:1px solid #dee2e6;padding:8px;'>Type</th>";
    echo "<th style='border:1px solid #dee2e6;padding:8px;'>Plot Name</th>";
    echo "<th style='border:1px solid #dee2e6;padding:8px;'>OR Number</th>";
    echo "<th style='border:1px solid #dee2e6;padding:8px;'>Status</th>";
    echo "<th style='border:1px solid #dee2e6;padding:8px;'>Date</th>";
    echo "</tr>";
    
    foreach ($rows as $r) {
        $plotName = $r['plot_type'] === 'burial' 
            ? ($r['decedent_name'] ?: 'Unknown') 
            : ($r['available_plot'] ?: 'Plot #'.$r['plot_id']);
        
        echo "<tr>";
        echo "<td style='border:1px solid #dee2e6;padding:8px;'>{$r['id']}</td>";
        echo "<td style='border:1px solid #dee2e6;padding:8px;'>{$r['plot_type']}</td>";
        echo "<td style='border:1px solid #dee2e6;padding:8px;'>" . htmlspecialchars($plotName) . "</td>";
        echo "<td style='border:1px solid #dee2e6;padding:8px;'>" . htmlspecialchars($r['or_number'] ?? 'N/A') . "</td>";
        echo "<td style='border:1px solid #dee2e6;padding:8px;'>{$r['status']}</td>";
        echo "<td style='border:1px solid #dee2e6;padding:8px;'>{$r['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><a href='renewals.php' style='color:#3b82f6;font-weight:600;'>← Go to Renewals Page</a></p>";
?>
