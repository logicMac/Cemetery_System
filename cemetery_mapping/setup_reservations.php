<?php
/**
 * Quick Reservation System Setup
 * This will create all necessary tables for the reservation system
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Reservation System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            color: white;
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        .box {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 24px;
            margin: 20px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .box.success {
            border-color: #5a9b6f;
            background: rgba(0, 200, 83, 0.1);
        }
        .box.error {
            border-color: #b55a5a;
            background: rgba(181, 90, 90, 0.1);
        }
        .box.warning {
            border-color: #a68b52;
            background: rgba(166, 139, 82, 0.1);
        }
        .box h3 {
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        .box p {
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.8);
        }
        .btn {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, #00c853 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 230, 118, 0.5);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            box-shadow: none;
        }
        ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        li {
            margin: 8px 0;
            line-height: 1.5;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            margin: 8px 0;
        }
        .icon {
            font-size: 1.5rem;
        }
        code {
            background: rgba(0, 0, 0, 0.4);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Reservation System Setup</h1>
        <p class="subtitle">Quick setup for plot reservation and payment system</p>

        <?php
        if (!isset($_POST['setup'])) {
            // Show setup form
            ?>
            <div class="box">
                <h3>📋 What will be created:</h3>
                <ul>
                    <li><strong>plot_reservations</strong> - Stores visitor plot reservation requests</li>
                    <li><strong>payments</strong> - Tracks payment transactions and proof uploads</li>
                    <li><strong>plot_pricing</strong> - Manages pricing for different plot types</li>
                </ul>
            </div>

            <div class="box warning">
                <h3>⚠️ Important Notes:</h3>
                <ul>
                    <li>This will DROP existing reservation tables if they exist</li>
                    <li>Compartment ID is stored as a simple reference number (not a foreign key)</li>
                    <li>Default pricing will be set for all plot types</li>
                    <li>The <code>uploads/payments/</code> directory will be created</li>
                </ul>
            </div>

            <div class="box">
                <h3>💰 Default Pricing:</h3>
                <ul>
                    <li><strong>Standard:</strong> ₱5,000.00</li>
                    <li><strong>Premium:</strong> ₱15,000.00</li>
                    <li><strong>Family:</strong> ₱25,000.00</li>
                    <li><strong>Lawn:</strong> ₱8,000.00</li>
                </ul>
            </div>

            <form method="POST">
                <input type="hidden" name="setup" value="1">
                <button type="submit" class="btn">✓ Create Tables Now</button>
                <a href="index.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
            </form>

            <?php
        } else {
            // Run setup
            echo '<div class="box">';
            echo '<h3>🔧 Running Setup...</h3>';
            
            $errors = [];
            $success = [];
            
            try {
                // Drop existing tables
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                $pdo->exec("DROP TABLE IF EXISTS payments");
                $success[] = "Dropped old payments table (if existed)";
                
                $pdo->exec("DROP TABLE IF EXISTS plot_reservations");
                $success[] = "Dropped old plot_reservations table (if existed)";
                
                $pdo->exec("DROP TABLE IF EXISTS plot_pricing");
                $success[] = "Dropped old plot_pricing table (if existed)";
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                // Create plot_reservations
                $pdo->exec("
                    CREATE TABLE plot_reservations (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        visitor_id INT NOT NULL,
                        plot_id INT NOT NULL,
                        compartment_id INT DEFAULT NULL COMMENT 'Simple reference to compartment number',
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
                $success[] = "Created plot_reservations table";
                
                // Create payments
                $pdo->exec("
                    CREATE TABLE payments (
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
                $success[] = "Created payments table";
                
                // Create plot_pricing
                $pdo->exec("
                    CREATE TABLE plot_pricing (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        plot_type ENUM('standard', 'premium', 'family', 'lawn') NOT NULL,
                        price DECIMAL(10, 2) NOT NULL,
                        description TEXT,
                        is_active TINYINT(1) DEFAULT 1,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_plot_type (plot_type),
                        INDEX idx_active (is_active)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                $success[] = "Created plot_pricing table";
                
                // Insert pricing
                $pdo->exec("
                    INSERT INTO plot_pricing (plot_type, price, description, is_active) VALUES
                    ('standard', 5000.00, 'Standard burial plot - single compartment', 1),
                    ('premium', 15000.00, 'Premium fenced burial plot with marker', 1),
                    ('family', 25000.00, 'Family plot - multiple compartments', 1),
                    ('lawn', 8000.00, 'Lawn-type burial plot', 1)
                ");
                $success[] = "Inserted default pricing data";
                
                // Create uploads directory
                $uploadsDir = __DIR__ . '/uploads/payments';
                if (!file_exists($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                    $success[] = "Created uploads/payments directory";
                } else {
                    $success[] = "uploads/payments directory already exists";
                }
                
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = "Error: " . $e->getMessage();
            }
            
            // Display results
            if (empty($errors)) {
                echo '<div class="box success">';
                echo '<h3>✅ Setup Completed Successfully!</h3>';
                echo '<p>All tables have been created and configured.</p>';
                echo '</div>';
                
                echo '<div class="box">';
                echo '<h3>📝 Steps Completed:</h3>';
                foreach ($success as $msg) {
                    echo '<div class="status-item"><span class="icon">✓</span><span>' . htmlspecialchars($msg) . '</span></div>';
                }
                echo '</div>';
                
                echo '<div class="box">';
                echo '<h3>🎯 Next Steps:</h3>';
                echo '<ul>';
                echo '<li>Go to the <a href="visitor/dashboard.php" style="color: #00c853; text-decoration: underline;">Visitor Dashboard</a> to test reservations</li>';
                echo '<li>Go to <a href="admin/reservations.php" style="color: #00c853; text-decoration: underline;">Admin Reservations</a> to manage requests</li>';
                echo '<li>Modify pricing in the admin settings if needed</li>';
                echo '</ul>';
                echo '</div>';
                
                echo '<a href="visitor/dashboard.php" class="btn">Go to Dashboard</a>';
                echo '<a href="admin/reservations.php" class="btn btn-secondary" style="margin-left: 10px;">Admin Panel</a>';
                
            } else {
                echo '<div class="box error">';
                echo '<h3>❌ Setup Failed</h3>';
                echo '<p>The following errors occurred:</p>';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li style="color: #b55a5a;">' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
                
                if (!empty($success)) {
                    echo '<div class="box">';
                    echo '<h3>Partial Success:</h3>';
                    echo '<ul>';
                    foreach ($success as $msg) {
                        echo '<li>' . htmlspecialchars($msg) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                echo '<a href="setup_reservations.php" class="btn">Try Again</a>';
            }
            
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
