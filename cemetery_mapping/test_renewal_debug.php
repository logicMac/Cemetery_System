<?php
/**
 * Debug script to test if renewals are actually working
 * Shows what happens when direct_renew.php is called
 */

require_once 'config/database.php';
session_start();

// Must be admin
if (!isset($_SESSION['admin_id'])) {
    die("Must be logged in as admin");
}

echo "<h2>Renewal Debug Test</h2>";
echo "<style>table { border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; } th { background: #f2f2f2; }</style>";

// Get a sample expired plot
$stmt = $pdo->query("
    SELECT id, decedent_name, plot_number, expiration_date, renewal_count
    FROM burial_records 
    WHERE expiration_date IS NOT NULL AND expiration_date < CURDATE()
    LIMIT 1
");
$expiredPlot = $stmt->fetch();

if (!$expiredPlot) {
    echo "<p>No expired burial plots found to test with.</p>";
    
    // Try available plots
    $stmt = $pdo->query("
        SELECT id, plot_number, expiration_date, renewal_count
        FROM available_plots 
        WHERE expiration_date IS NOT NULL AND expiration_date < CURDATE()
        LIMIT 1
    ");
    $expiredPlot = $stmt->fetch();
    $isAvailable = true;
} else {
    $isAvailable = false;
}

if (!$expiredPlot) {
    die("<p>No expired plots found to test with. Create one manually or wait for a plot to expire.</p>");
}

$plotType = $isAvailable ? 'available' : 'burial';
$plotId = $expiredPlot['id'];
$plotName = $isAvailable ? ($expiredPlot['plot_number'] ?: 'Plot #'.$plotId) : $expiredPlot['decedent_name'];

echo "<h3>Test Plot Selected:</h3>";
echo "<table>";
echo "<tr><th>Type</th><td>{$plotType}</td></tr>";
echo "<tr><th>ID</th><td>{$plotId}</td></tr>";
echo "<tr><th>Name</th><td>{$plotName}</td></tr>";
echo "<tr><th>Current Expiration</th><td>{$expiredPlot['expiration_date']}</td></tr>";
echo "<tr><th>Renewal Count</th><td>{$expiredPlot['renewal_count']}</td></tr>";
echo "</table>";

// Test renewal
if (isset($_POST['test_renewal'])) {
    echo "<hr><h3>Testing Renewal...</h3>";
    
    $orNumber = "TEST-OR-" . date('Ymd-His');
    
    echo "<p><strong>OR Number:</strong> {$orNumber}</p>";
    
    // Simulate the API call
    $_POST = [
        'plot_type' => $plotType,
        'record_id' => $isAvailable ? '' : $plotId,
        'plot_id' => $isAvailable ? $plotId : '',
        'or_number' => $orNumber,
        'notes' => 'Test renewal from debug script'
    ];
    
    echo "<p>Calling direct_renew.php API...</p>";
    
    // Capture the API response
    ob_start();
    include 'api/direct_renew.php';
    $response = ob_get_clean();
    
    echo "<p><strong>API Response:</strong></p>";
    echo "<pre style='background:#f5f5f5;padding:10px;border-radius:5px;'>{$response}</pre>";
    
    $responseData = json_decode($response, true);
    
    if ($responseData && $responseData['success']) {
        echo "<p style='color:green;font-weight:bold;'>✓ API returned success</p>";
        
        // Check if database was actually updated
        if ($plotType === 'burial') {
            $check = $pdo->prepare("SELECT expiration_date, renewal_count FROM burial_records WHERE id = ?");
        } else {
            $check = $pdo->prepare("SELECT expiration_date, renewal_count FROM available_plots WHERE id = ?");
        }
        $check->execute([$plotId]);
        $updated = $check->fetch();
        
        echo "<h4>Database After Renewal:</h4>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Before</th><th>After</th><th>Status</th></tr>";
        echo "<tr><td>Expiration Date</td><td>{$expiredPlot['expiration_date']}</td><td>{$updated['expiration_date']}</td><td>" . 
             ($updated['expiration_date'] !== $expiredPlot['expiration_date'] ? '<span style="color:green;">✓ Changed</span>' : '<span style="color:red;">✗ Not changed</span>') . "</td></tr>";
        echo "<tr><td>Renewal Count</td><td>{$expiredPlot['renewal_count']}</td><td>{$updated['renewal_count']}</td><td>" . 
             ($updated['renewal_count'] > $expiredPlot['renewal_count'] ? '<span style="color:green;">✓ Incremented</span>' : '<span style="color:red;">✗ Not incremented</span>') . "</td></tr>";
        echo "</table>";
        
        // Check renewal log
        $logCheck = $pdo->prepare("
            SELECT * FROM plot_renewal_requests 
            WHERE or_number = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $logCheck->execute([$orNumber]);
        $logEntry = $logCheck->fetch();
        
        if ($logEntry) {
            echo "<h4>Renewal Log Entry Created:</h4>";
            echo "<table>";
            echo "<tr><th>ID</th><td>{$logEntry['id']}</td></tr>";
            echo "<tr><th>Visitor ID</th><td>" . ($logEntry['visitor_id'] ?: 'NULL') . "</td></tr>";
            echo "<tr><th>Plot Type</th><td>{$logEntry['plot_type']}</td></tr>";
            echo "<tr><th>Status</th><td>{$logEntry['status']}</td></tr>";
            echo "<tr><th>OR Number</th><td>{$logEntry['or_number']}</td></tr>";
            echo "<tr><th>Created At</th><td>{$logEntry['created_at']}</td></tr>";
            echo "</table>";
        } else {
            echo "<p style='color:red;'>✗ No renewal log entry found!</p>";
        }
        
    } else {
        echo "<p style='color:red;font-weight:bold;'>✗ API returned error</p>";
        if ($responseData) {
            echo "<p>Error message: {$responseData['message']}</p>";
        }
    }
    
    echo "<hr><p><a href='test_renewal_debug.php'>Run test again</a> | <a href='admin/expiring-plots.php'>Go to Expiring Plots</a></p>";
} else {
    echo "<hr>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='test_renewal' style='background:#0066cc;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;'>Test Renewal</button>";
    echo "</form>";
    echo "<p><small>This will renew the plot above and show detailed debug information.</small></p>";
}
?>
