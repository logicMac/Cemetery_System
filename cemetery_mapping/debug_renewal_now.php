<?php
/**
 * Real-time renewal debug - simulates exactly what happens when you click "Renew Now"
 */

session_start();
require_once 'config/database.php';

if (!isset($_SESSION['admin_id'])) {
    die("Must be admin. <a href='admin/login.php'>Login</a>");
}

echo "<h1>🔧 Live Renewal Debug</h1>";
echo "<style>
body { font-family: system-ui; padding: 20px; background: #f5f5f5; }
.step { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #0066cc; border-radius: 4px; }
.success { border-left-color: #28a745; background: #f0fff4; }
.error { border-left-color: #dc3545; background: #fff5f5; }
pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
th { background: #e9ecef; }
</style>";

// Step 1: Check schema
echo "<div class='step'>";
echo "<h3>Step 1: Verify Schema</h3>";

$schemaOk = true;

// Check or_number
$stmt = $pdo->query("SHOW COLUMNS FROM plot_renewal_requests LIKE 'or_number'");
$hasOrNumber = $stmt->fetch();
echo "<p>or_number column: " . ($hasOrNumber ? "✓ EXISTS" : "✗ MISSING") . "</p>";
if (!$hasOrNumber) $schemaOk = false;

// Check visitor_id nullability
$stmt = $pdo->query("SHOW COLUMNS FROM plot_renewal_requests LIKE 'visitor_id'");
$col = $stmt->fetch();
$isNullable = ($col && $col['Null'] === 'YES');
echo "<p>visitor_id nullable: " . ($isNullable ? "✓ YES" : "✗ NO (PROBLEM!)") . "</p>";
if (!$isNullable) $schemaOk = false;

if ($schemaOk) {
    echo "<p><strong style='color:green;'>✓ Schema is correct</strong></p>";
} else {
    echo "<p><strong style='color:red;'>✗ Schema has issues - fix required!</strong></p>";
    echo "<p><a href='check_renewal_schema.php'>Go to Schema Fix Tool</a></p>";
}
echo "</div>";

// Step 2: Get a test plot
echo "<div class='step'>";
echo "<h3>Step 2: Select Test Plot</h3>";

// Find an expired available plot
$stmt = $pdo->query("
    SELECT id, plot_number, expiration_date, renewal_count
    FROM available_plots 
    WHERE expiration_date IS NOT NULL AND expiration_date < CURDATE()
    LIMIT 1
");
$plot = $stmt->fetch();

if (!$plot) {
    echo "<p style='color:orange;'>No expired available plots found. Trying burial records...</p>";
    $stmt = $pdo->query("
        SELECT id, decedent_name as plot_number, expiration_date, renewal_count
        FROM burial_records 
        WHERE expiration_date IS NOT NULL AND expiration_date < CURDATE()
        LIMIT 1
    ");
    $plot = $stmt->fetch();
    $plotType = 'burial';
} else {
    $plotType = 'available';
}

if (!$plot) {
    die("<p style='color:red;'>No expired plots found to test with!</p></div>");
}

echo "<table>";
echo "<tr><th>Type</th><td>{$plotType}</td></tr>";
echo "<tr><th>ID</th><td>{$plot['id']}</td></tr>";
echo "<tr><th>Name/Number</th><td>{$plot['plot_number']}</td></tr>";
echo "<tr><th>Current Expiration</th><td>{$plot['expiration_date']}</td></tr>";
echo "<tr><th>Renewal Count</th><td>{$plot['renewal_count']}</td></tr>";
echo "</table>";
echo "</div>";

// Step 3: Test the renewal
if (isset($_POST['do_renewal'])) {
    echo "<div class='step'>";
    echo "<h3>Step 3: Execute Renewal</h3>";
    
    $orNumber = "DEBUG-" . date('YmdHis');
    echo "<p><strong>OR Number:</strong> {$orNumber}</p>";
    
    // Prepare the request exactly as the form would
    $postData = [
        'plot_type' => $plotType,
        'record_id' => $plotType === 'burial' ? $plot['id'] : '',
        'plot_id' => $plotType === 'available' ? $plot['id'] : '',
        'or_number' => $orNumber,
        'notes' => 'Debug test renewal'
    ];
    
    echo "<p><strong>POST Data:</strong></p>";
    echo "<pre>" . print_r($postData, true) . "</pre>";
    
    // Call the API directly (no curl needed since we're in same session)
    // Save current POST data
    $oldPost = $_POST;
    
    // Set up POST data for the API
    $_POST = $postData;
    
    // Capture API output
    ob_start();
    include __DIR__ . '/api/direct_renew.php';
    $response = ob_get_clean();
    
    // Restore original POST
    $_POST = $oldPost;
    
    $httpCode = 200;
    
    echo "<p><strong>HTTP Status:</strong> {$httpCode}</p>";
    echo "<p><strong>API Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        echo "<div class='step success'>";
        echo "<h4>✓ API Returned Success</h4>";
        echo "<p>{$data['message']}</p>";
        if (isset($data['new_expiration'])) {
            echo "<p><strong>New expiration:</strong> {$data['new_expiration']}</p>";
        }
        echo "</div>";
        
        // Verify database changes
        echo "<div class='step'>";
        echo "<h3>Step 4: Verify Database Changes</h3>";
        
        // Check the plot's expiration date
        if ($plotType === 'burial') {
            $check = $pdo->prepare("SELECT expiration_date, renewal_count FROM burial_records WHERE id = ?");
        } else {
            $check = $pdo->prepare("SELECT expiration_date, renewal_count FROM available_plots WHERE id = ?");
        }
        $check->execute([$plot['id']]);
        $updated = $check->fetch();
        
        echo "<table>";
        echo "<tr><th>Field</th><th>Before</th><th>After</th><th>Changed?</th></tr>";
        echo "<tr>";
        echo "<td>Expiration Date</td>";
        echo "<td>{$plot['expiration_date']}</td>";
        echo "<td>{$updated['expiration_date']}</td>";
        echo "<td>" . ($updated['expiration_date'] !== $plot['expiration_date'] ? 
            "<span style='color:green;font-weight:bold;'>✓ YES</span>" : 
            "<span style='color:red;font-weight:bold;'>✗ NO</span>") . "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Renewal Count</td>";
        echo "<td>{$plot['renewal_count']}</td>";
        echo "<td>{$updated['renewal_count']}</td>";
        echo "<td>" . ($updated['renewal_count'] > $plot['renewal_count'] ? 
            "<span style='color:green;font-weight:bold;'>✓ YES</span>" : 
            "<span style='color:red;font-weight:bold;'>✗ NO</span>") . "</td>";
        echo "</tr>";
        echo "</table>";
        
        // Check renewal log
        $logCheck = $pdo->prepare("
            SELECT * FROM plot_renewal_requests 
            WHERE or_number = ?
            ORDER BY created_at DESC LIMIT 1
        ");
        $logCheck->execute([$orNumber]);
        $logEntry = $logCheck->fetch();
        
        if ($logEntry) {
            echo "<h4>✓ Renewal Log Entry Created</h4>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>ID</td><td>{$logEntry['id']}</td></tr>";
            echo "<tr><td>Visitor ID</td><td>" . ($logEntry['visitor_id'] ?: '<em>NULL</em>') . "</td></tr>";
            echo "<tr><td>Plot Type</td><td>{$logEntry['plot_type']}</td></tr>";
            echo "<tr><td>Record ID</td><td>" . ($logEntry['record_id'] ?: '<em>NULL</em>') . "</td></tr>";
            echo "<tr><td>Plot ID</td><td>" . ($logEntry['plot_id'] ?: '<em>NULL</em>') . "</td></tr>";
            echo "<tr><td>OR Number</td><td>{$logEntry['or_number']}</td></tr>";
            echo "<tr><td>Status</td><td>{$logEntry['status']}</td></tr>";
            echo "<tr><td>Reviewed By</td><td>{$logEntry['reviewed_by']}</td></tr>";
            echo "<tr><td>Created At</td><td>{$logEntry['created_at']}</td></tr>";
            echo "</table>";
        } else {
            echo "<h4 style='color:red;'>✗ No Renewal Log Entry Found!</h4>";
            echo "<p>This means the INSERT into plot_renewal_requests failed.</p>";
        }
        
        echo "</div>";
        
        // Summary
        $allGood = ($updated['expiration_date'] !== $plot['expiration_date']) && 
                   ($updated['renewal_count'] > $plot['renewal_count']) && 
                   $logEntry;
        
        if ($allGood) {
            echo "<div class='step success'>";
            echo "<h3>🎉 SUCCESS! Renewal is working correctly!</h3>";
            echo "<p>The renewal was processed successfully:</p>";
            echo "<ul>";
            echo "<li>✓ Expiration date updated to: {$updated['expiration_date']}</li>";
            echo "<li>✓ Renewal count incremented to: {$updated['renewal_count']}</li>";
            echo "<li>✓ Log entry created with ID: {$logEntry['id']}</li>";
            echo "</ul>";
            echo "<p><a href='admin/expiring-plots.php'>Go to Expiring Plots</a> | ";
            echo "<a href='admin/renewals.php'>Go to Renewal History</a></p>";
            echo "</div>";
        } else {
            echo "<div class='step error'>";
            echo "<h3>⚠ Partial Success</h3>";
            echo "<p>API returned success but database wasn't fully updated.</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div class='step error'>";
        echo "<h4>✗ API Returned Error</h4>";
        if ($data) {
            echo "<p><strong>Error:</strong> {$data['message']}</p>";
            if (isset($data['error_code'])) {
                echo "<p><strong>Error Code:</strong> {$data['error_code']}</p>";
            }
        } else {
            echo "<p>Invalid JSON response or empty response</p>";
        }
        echo "</div>";
        
        // Check error log
        $errorLog = __DIR__ . '/renewal_errors.log';
        if (file_exists($errorLog)) {
            $errors = file_get_contents($errorLog);
            if (trim($errors)) {
                echo "<div class='step error'>";
                echo "<h4>Error Log Contents:</h4>";
                echo "<pre>" . htmlspecialchars($errors) . "</pre>";
                echo "</div>";
            }
        }
    }
    
    echo "</div>";
    
} else {
    // Show form to trigger test
    echo "<div class='step'>";
    echo "<h3>Step 3: Test Renewal</h3>";
    echo "<p>Click the button below to test renewing this plot:</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='do_renewal' style='background:#007bff;color:white;padding:12px 24px;border:none;border-radius:5px;font-size:16px;cursor:pointer;font-weight:bold;'>▶ Run Renewal Test</button>";
    echo "</form>";
    echo "</div>";
}
?>
