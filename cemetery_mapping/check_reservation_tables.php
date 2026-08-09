<?php
/**
 * Check and Setup Reservation System Tables
 * This script verifies database tables and creates missing ones
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation System Setup Check</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        h1 {
            margin-top: 0;
            font-size: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .status-box {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .success {
            border-left-color: #22c55e;
            background: rgba(34, 197, 94, 0.1);
        }
        .error {
            border-left-color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }
        .warning {
            border-left-color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
        }
        .info {
            border-left-color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            background: rgba(102, 126, 234, 0.2);
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-success {
            background: #22c55e;
            color: white;
        }
        .badge-error {
            background: #ef4444;
            color: white;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        pre {
            background: rgba(0, 0, 0, 0.5);
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Reservation System Setup Check</h1>
        
        <?php
        $results = [];
        $hasErrors = false;
        
        // Check database connection
        try {
            $pdo->query("SELECT 1");
            $results[] = ['type' => 'success', 'message' => 'Database connection successful'];
        } catch (PDOException $e) {
            $results[] = ['type' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
            $hasErrors = true;
        }
        
        // Required tables for reservation system
        $requiredTables = [
            'visitors' => 'Required for visitor authentication',
            'available_plots' => 'Required for plot management',
            'plot_reservations' => 'Stores reservation requests',
            'payments' => 'Tracks payment transactions',
            'plot_pricing' => 'Stores pricing configuration',
            'admin_users' => 'Required for admin approval'
        ];
        
        echo '<div class="status-box info">';
        echo '<h3>📋 Checking Required Tables</h3>';
        echo '<table>';
        echo '<tr><th>Table Name</th><th>Status</th><th>Description</th></tr>';
        
        foreach ($requiredTables as $table => $description) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->fetch();
                
                if ($exists) {
                    echo "<tr><td><strong>$table</strong></td><td><span class='badge badge-success'>✓ Exists</span></td><td>$description</td></tr>";
                    
                    // Get row count
                    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                    $count = $countStmt->fetch()['count'];
                    $results[] = ['type' => 'success', 'message' => "Table '$table' exists with $count records"];
                } else {
                    echo "<tr><td><strong>$table</strong></td><td><span class='badge badge-error'>✗ Missing</span></td><td>$description</td></tr>";
                    $results[] = ['type' => 'error', 'message' => "Table '$table' is missing"];
                    $hasErrors = true;
                }
            } catch (PDOException $e) {
                echo "<tr><td><strong>$table</strong></td><td><span class='badge badge-error'>Error</span></td><td>" . $e->getMessage() . "</td></tr>";
                $hasErrors = true;
            }
        }
        
        echo '</table>';
        echo '</div>';
        
        // Check for plot_compartments table (optional but referenced)
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'plot_compartments'");
            $compartmentsExists = $stmt->fetch();
            
            if (!$compartmentsExists) {
                $results[] = ['type' => 'warning', 'message' => "Table 'plot_compartments' is missing but not critical (foreign key will be removed)"];
            }
        } catch (PDOException $e) {
            // Ignore
        }
        
        // If tables are missing, offer to create them
        if ($hasErrors) {
            echo '<div class="status-box error">';
            echo '<h3>❌ Missing Tables Detected</h3>';
            echo '<p>Some required tables are missing. Would you like to create them now?</p>';
            echo '<form method="POST" action="">';
            echo '<input type="hidden" name="create_tables" value="1">';
            echo '<button type="submit" class="btn">Create Missing Tables</button>';
            echo '</form>';
            echo '</div>';
        }
        
        // Handle table creation
        if (isset($_POST['create_tables'])) {
            echo '<div class="status-box info">';
            echo '<h3>🔧 Creating Tables...</h3>';
            
            try {
                // Create plot_reservations without compartment_id reference
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS plot_reservations (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        visitor_id INT NOT NULL,
                        plot_id INT NOT NULL,
                        compartment_id INT DEFAULT NULL,
                        reservation_type ENUM('standard', 'premium', 'family', 'lawn') DEFAULT 'standard',
                        purpose VARCHAR(255),
                        intended_for VARCHAR(255),
                        contact_number VARCHAR(20),
                        status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
                        payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
                        total_amount DECIMAL(10, 2) DEFAULT 0.00,
                        amount_paid DECIMAL(10, 2) DEFAULT 0.00,
                        reservation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        approved_by INT DEFAULT NULL,
                        approved_date TIMESTAMP NULL,
                        rejection_reason TEXT,
                        notes TEXT,
                        FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
                        FOREIGN KEY (plot_id) REFERENCES available_plots(id) ON DELETE CASCADE,
                        FOREIGN KEY (approved_by) REFERENCES admin_users(id) ON DELETE SET NULL,
                        INDEX idx_visitor (visitor_id),
                        INDEX idx_plot (plot_id),
                        INDEX idx_status (status),
                        INDEX idx_payment (payment_status),
                        INDEX idx_date (reservation_date)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                echo '<p>✓ Created plot_reservations table</p>';
                
                // Create payments table
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS payments (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        reservation_id INT NOT NULL,
                        payment_method ENUM('cash', 'bank_transfer', 'gcash', 'paymaya', 'credit_card', 'other') DEFAULT 'cash',
                        amount DECIMAL(10, 2) NOT NULL,
                        reference_number VARCHAR(100),
                        proof_of_payment VARCHAR(255),
                        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        verified_by INT DEFAULT NULL,
                        verified_date TIMESTAMP NULL,
                        verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
                        notes TEXT,
                        FOREIGN KEY (reservation_id) REFERENCES plot_reservations(id) ON DELETE CASCADE,
                        FOREIGN KEY (verified_by) REFERENCES admin_users(id) ON DELETE SET NULL,
                        INDEX idx_reservation (reservation_id),
                        INDEX idx_status (verification_status),
                        INDEX idx_date (payment_date)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                echo '<p>✓ Created payments table</p>';
                
                // Create plot_pricing table
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS plot_pricing (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        plot_type ENUM('standard', 'premium', 'family', 'lawn') NOT NULL,
                        price DECIMAL(10, 2) NOT NULL,
                        description TEXT,
                        is_active TINYINT(1) DEFAULT 1,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_type (plot_type),
                        INDEX idx_active (is_active)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                echo '<p>✓ Created plot_pricing table</p>';
                
                // Insert default pricing
                $pdo->exec("
                    INSERT INTO plot_pricing (plot_type, price, description) VALUES
                    ('standard', 5000.00, 'Standard burial plot - single compartment'),
                    ('premium', 15000.00, 'Premium fenced burial plot with marker'),
                    ('family', 25000.00, 'Family plot - multiple compartments'),
                    ('lawn', 8000.00, 'Lawn-type burial plot')
                    ON DUPLICATE KEY UPDATE
                        price = VALUES(price),
                        description = VALUES(description)
                ");
                echo '<p>✓ Inserted default pricing data</p>';
                
                // Create uploads directory
                $uploadsDir = __DIR__ . '/uploads/payments';
                if (!file_exists($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                    echo '<p>✓ Created uploads/payments directory</p>';
                } else {
                    echo '<p>✓ uploads/payments directory already exists</p>';
                }
                
                echo '<p style="margin-top: 20px; padding: 15px; background: rgba(34, 197, 94, 0.2); border-radius: 8px; border: 1px solid #22c55e;"><strong>✓ All tables created successfully!</strong></p>';
                echo '<a href="check_reservation_tables.php" class="btn">Refresh Page</a>';
                
            } catch (PDOException $e) {
                echo '<p style="color: #ef4444;">❌ Error: ' . $e->getMessage() . '</p>';
            }
            
            echo '</div>';
        }
        
        // Display current pricing if table exists
        try {
            $stmt = $pdo->query("SELECT * FROM plot_pricing WHERE is_active = 1 ORDER BY plot_type");
            $pricing = $stmt->fetchAll();
            
            if ($pricing) {
                echo '<div class="status-box success">';
                echo '<h3>💰 Current Pricing Configuration</h3>';
                echo '<table>';
                echo '<tr><th>Plot Type</th><th>Price</th><th>Description</th></tr>';
                foreach ($pricing as $p) {
                    $formattedPrice = '₱' . number_format($p['price'], 2);
                    echo "<tr><td><strong>" . ucfirst($p['plot_type']) . "</strong></td><td>$formattedPrice</td><td>{$p['description']}</td></tr>";
                }
                echo '</table>';
                echo '</div>';
            }
        } catch (PDOException $e) {
            // Table doesn't exist yet
        }
        
        // Summary
        echo '<div class="status-box ' . ($hasErrors ? 'warning' : 'success') . '">';
        echo '<h3>📊 Summary</h3>';
        
        if ($hasErrors && !isset($_POST['create_tables'])) {
            echo '<p><strong>⚠️ Action Required:</strong> Missing tables detected. Please create them using the button above.</p>';
        } elseif (!$hasErrors) {
            echo '<p><strong>✓ All systems operational!</strong> The reservation system is ready to use.</p>';
            echo '<a href="visitor/dashboard.php" class="btn">Go to Visitor Dashboard</a>';
            echo '<a href="admin/reservations.php" class="btn" style="margin-left: 10px;">Go to Admin Reservations</a>';
        }
        
        echo '</div>';
        
        // Display all results
        if (!isset($_POST['create_tables'])) {
            echo '<div class="status-box info">';
            echo '<h3>📝 Detailed Check Results</h3>';
            echo '<ul style="list-style: none; padding: 0;">';
            foreach ($results as $result) {
                $icon = $result['type'] === 'success' ? '✓' : 
                       ($result['type'] === 'error' ? '✗' : '⚠');
                echo "<li style='padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05);'>$icon {$result['message']}</li>";
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>
        
        <div class="status-box info">
            <h3>ℹ️ Need Help?</h3>
            <p>If you encounter any issues:</p>
            <ol>
                <li>Make sure your database connection is working</li>
                <li>Verify that all base tables (visitors, available_plots, admin_users) exist</li>
                <li>Check that your database user has CREATE TABLE permissions</li>
                <li>Ensure the uploads/payments directory is writable</li>
            </ol>
        </div>
    </div>
</body>
</html>
