<?php
/**
 * Check Reservation Table Structure
 */
session_start();
require_once 'config/database.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Reservation Structure</title>
    <style>
        body { font-family: monospace; background: #0a0a0a; color: white; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #00c853; padding: 10px; text-align: left; }
        th { background: #00c853; }
        .success { color: #5a9b6f; }
        .error { color: #b55a5a; }
        .warning { color: #c9a86c; }
        pre { background: rgba(0,0,0,0.5); padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Reservation Table Structure Check</h1>
    
    <?php
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'plot_reservations'");
        $tableExists = $stmt->fetch();
        
        if (!$tableExists) {
            echo '<p class="error">❌ Table "plot_reservations" does not exist!</p>';
            exit;
        }
        
        echo '<p class="success">✓ Table "plot_reservations" exists</p>';
        
        // Get table structure
        echo '<h2>Table Structure:</h2>';
        $stmt = $pdo->query("DESCRIBE plot_reservations");
        $columns = $stmt->fetchAll();
        
        echo '<table>';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        foreach ($columns as $col) {
            echo '<tr>';
            echo '<td>' . $col['Field'] . '</td>';
            echo '<td>' . $col['Type'] . '</td>';
            echo '<td>' . $col['Null'] . '</td>';
            echo '<td>' . $col['Key'] . '</td>';
            echo '<td>' . ($col['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . $col['Extra'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Check for required columns
        $requiredColumns = ['id', 'status', 'approved_by', 'approved_date'];
        echo '<h2>Required Columns Check:</h2>';
        $columnNames = array_column($columns, 'Field');
        foreach ($requiredColumns as $req) {
            if (in_array($req, $columnNames)) {
                echo '<p class="success">✓ Column "' . $req . '" exists</p>';
            } else {
                echo '<p class="error">❌ Column "' . $req . '" is missing!</p>';
            }
        }
        
        // Get sample data
        echo '<h2>Sample Reservations:</h2>';
        $stmt = $pdo->query("SELECT id, visitor_id, plot_id, compartment_id, status, reservation_type, total_amount, reservation_date FROM plot_reservations ORDER BY id DESC LIMIT 5");
        $reservations = $stmt->fetchAll();
        
        if (count($reservations) == 0) {
            echo '<p class="warning">⚠ No reservations found in database</p>';
        } else {
            echo '<table>';
            echo '<tr><th>ID</th><th>Visitor ID</th><th>Plot ID</th><th>Compartment</th><th>Status</th><th>Type</th><th>Amount</th><th>Date</th></tr>';
            foreach ($reservations as $res) {
                echo '<tr>';
                echo '<td>' . $res['id'] . '</td>';
                echo '<td>' . $res['visitor_id'] . '</td>';
                echo '<td>' . $res['plot_id'] . '</td>';
                echo '<td>' . ($res['compartment_id'] ?? 'NULL') . '</td>';
                echo '<td>' . $res['status'] . '</td>';
                echo '<td>' . $res['reservation_type'] . '</td>';
                echo '<td>₱' . number_format($res['total_amount'], 2) . '</td>';
                echo '<td>' . $res['reservation_date'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        // Test UPDATE query
        echo '<h2>Test UPDATE Query:</h2>';
        $testId = $reservations[0]['id'] ?? 1;
        $testAdminId = $_SESSION['admin_id'] ?? 1;
        
        echo '<p>Testing with:</p>';
        echo '<pre>';
        echo "Reservation ID: $testId\n";
        echo "Admin ID: $testAdminId\n";
        echo "Current Status: " . ($reservations[0]['status'] ?? 'unknown') . "\n";
        echo '</pre>';
        
        if (($reservations[0]['status'] ?? '') === 'pending') {
            echo '<p class="warning">⚠ This is a pending reservation. Testing UPDATE (DRY RUN - not actually updating):</p>';
            
            // Prepare but don't execute
            $sql = "UPDATE plot_reservations SET status = 'approved', approved_by = ?, approved_date = NOW() WHERE id = ?";
            echo '<pre>' . $sql . '</pre>';
            echo '<p>Parameters: [' . $testAdminId . ', ' . $testId . ']</p>';
            
            $stmt = $pdo->prepare($sql);
            echo '<p class="success">✓ Query prepared successfully (would update if executed)</p>';
        } else {
            echo '<p class="warning">⚠ No pending reservations to test with</p>';
        }
        
        // Check admin session
        echo '<h2>Admin Session Check:</h2>';
        echo '<pre>';
        echo 'Session admin_id: ' . ($_SESSION['admin_id'] ?? 'NOT SET') . "\n";
        echo 'Session admin_username: ' . ($_SESSION['admin_username'] ?? 'NOT SET') . "\n";
        echo '</pre>';
        
    } catch (PDOException $e) {
        echo '<p class="error">❌ Database Error: ' . $e->getMessage() . '</p>';
    }
    ?>
    
    <hr style="margin: 40px 0; border-color: #00c853;">
    <p><a href="admin/reservations.php" style="color: #00c853;">← Back to Reservations</a></p>
</body>
</html>
