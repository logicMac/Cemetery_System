<?php
/**
 * Check and fix the plot_renewal_requests schema
 * Run this once to apply the schema fix
 */

require_once 'config/database.php';

echo "<h2>Checking plot_renewal_requests Schema</h2>";

// Check if or_number column exists
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM plot_renewal_requests LIKE 'or_number'");
    $hasOrNumber = $stmt->fetch();
    
    echo "<p><strong>or_number column:</strong> " . ($hasOrNumber ? "✓ EXISTS" : "✗ MISSING") . "</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error checking or_number column: " . $e->getMessage() . "</p>";
}

// Check visitor_id nullability
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM plot_renewal_requests LIKE 'visitor_id'");
    $col = $stmt->fetch();
    $isNullable = ($col && $col['Null'] === 'YES');
    
    echo "<p><strong>visitor_id nullable:</strong> " . ($isNullable ? "✓ YES (can be NULL)" : "✗ NO (must be NOT NULL)") . "</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error checking visitor_id: " . $e->getMessage() . "</p>";
}

// Show recent renewal request records
echo "<h3>Recent Renewal Requests (last 10):</h3>";
try {
    $stmt = $pdo->query("
        SELECT id, visitor_id, plot_type, record_id, plot_id, 
               or_number, status, reviewed_at, created_at
        FROM plot_renewal_requests 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $rows = $stmt->fetchAll();
    
    if (empty($rows)) {
        echo "<p>No renewal requests found.</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Visitor ID</th><th>Type</th><th>Record/Plot ID</th><th>OR Number</th><th>Status</th><th>Reviewed At</th><th>Created At</th></tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>" . ($row['visitor_id'] ?: 'NULL') . "</td>";
            echo "<td>{$row['plot_type']}</td>";
            echo "<td>" . ($row['plot_type'] === 'burial' ? 'R:'.$row['record_id'] : 'P:'.$row['plot_id']) . "</td>";
            echo "<td>" . ($row['or_number'] ?? 'N/A') . "</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>" . ($row['reviewed_at'] ?: '—') . "</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error fetching records: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Apply Schema Fix</h3>";
echo "<p>To fix the schema issues, run the following SQL migration:</p>";
echo "<pre>fix_renewal_requests_schema.sql</pre>";
echo "<p>Or click the button below to apply it now:</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_fix'])) {
    echo "<div style='background:#fffbcc;border:1px solid #e6db00;padding:10px;margin:10px 0;'>";
    echo "<strong>Applying schema fix...</strong><br><br>";
    
    try {
        // Step 1: Make visitor_id nullable (CRITICAL)
        echo "Step 1: Making visitor_id nullable...<br>";
        try {
            $pdo->exec("ALTER TABLE plot_renewal_requests MODIFY COLUMN visitor_id INT DEFAULT NULL");
            echo "✓ visitor_id is now nullable<br><br>";
        } catch (PDOException $e) {
            echo "⚠ Error (may be ok if already nullable): " . $e->getMessage() . "<br><br>";
        }
        
        // Step 2: Add or_number column
        echo "Step 2: Adding or_number column...<br>";
        try {
            $pdo->exec("ALTER TABLE plot_renewal_requests ADD COLUMN or_number VARCHAR(100) DEFAULT NULL AFTER requested_years");
            echo "✓ or_number column added<br><br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "✓ or_number column already exists<br><br>";
            } else {
                echo "⚠ Error: " . $e->getMessage() . "<br><br>";
            }
        }
        
        // Step 3: Find and drop old foreign key
        echo "Step 3: Updating foreign key constraint...<br>";
        try {
            $stmt = $pdo->query("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'plot_renewal_requests' 
                AND COLUMN_NAME = 'visitor_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ");
            $fk = $stmt->fetchColumn();
            
            if ($fk) {
                echo "Found foreign key: {$fk}<br>";
                $pdo->exec("ALTER TABLE plot_renewal_requests DROP FOREIGN KEY {$fk}");
                echo "✓ Dropped old foreign key<br>";
            } else {
                echo "No existing foreign key found<br>";
            }
        } catch (PDOException $e) {
            echo "⚠ Error dropping FK: " . $e->getMessage() . "<br>";
        }
        
        // Step 4: Add new foreign key with SET NULL
        try {
            $pdo->exec("
                ALTER TABLE plot_renewal_requests
                ADD CONSTRAINT plot_renewal_requests_ibfk_1
                FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE SET NULL
            ");
            echo "✓ Added new foreign key with SET NULL<br><br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "✓ Foreign key already exists<br><br>";
            } else {
                echo "⚠ Error adding FK: " . $e->getMessage() . "<br><br>";
            }
        }
        
        echo "<strong style='color:green;'>✓ Schema fix completed!</strong><br>";
        echo "<a href='check_renewal_working.php' style='color:#0066cc;font-weight:bold;'>← Go back to diagnostic page</a>";
        
    } catch (Exception $e) {
        echo "<strong style='color:red;'>✗ Unexpected error: " . $e->getMessage() . "</strong>";
    }
    
    echo "</div>";
}

echo "<form method='POST'>";
echo "<button type='submit' name='apply_fix' style='background:#0066cc;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;'>Apply Fix Now</button>";
echo "</form>";
?>
