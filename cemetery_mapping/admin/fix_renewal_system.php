<?php
/**
 * Renewal System Fix - Admin Tool
 * Run this once to fix the plot_renewal_requests table schema
 */

session_start();
require_once '../config/database.php';
require_once 'includes/header.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$current_page = 'settings';
require_once 'includes/sidebar.php';
?>

<style>
.admin-layout { background: var(--surface); }
.status-box { background: white; border-radius: 12px; padding: 20px; margin: 20px 0; border: 2px solid #e2e8f0; }
.success { border-color: #10b981; background: #f0fdf4; }
.error { border-color: #ef4444; background: #fef2f2; }
.warning { border-color: #f59e0b; background: #fffbeb; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; text-align: left; border: 1px solid #e5e7eb; }
th { background: #f9fafb; font-weight: 600; }
pre { background: #f3f4f6; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 12px; }
.btn { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 14px; }
.btn:hover { background: #2563eb; }
</style>

<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4 mb-2">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                <i data-lucide="wrench" class="w-5 h-5"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Renewal System Fix</h1>
                <p class="text-xs text-slate-500">Fix schema issues for plot renewals</p>
            </div>
        </div>
    </div>

    <?php
    // Check current schema status
    $hasIssues = false;
    $issues = [];
    
    try {
        // Check or_number column
        $stmt = $pdo->query("SHOW COLUMNS FROM plot_renewal_requests LIKE 'or_number'");
        $hasOrNumber = $stmt->fetch();
        
        if (!$hasOrNumber) {
            $hasIssues = true;
            $issues[] = "Missing or_number column";
        }
        
        // Check visitor_id nullability
        $stmt = $pdo->query("SHOW COLUMNS FROM plot_renewal_requests LIKE 'visitor_id'");
        $col = $stmt->fetch();
        $isNullable = ($col && $col['Null'] === 'YES');
        
        if (!$isNullable) {
            $hasIssues = true;
            $issues[] = "visitor_id must be nullable";
        }
    } catch (PDOException $e) {
        $hasIssues = true;
        $issues[] = "Cannot check schema: " . $e->getMessage();
    }
    
    // Apply fix if requested
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_fix'])) {
        echo "<div class='status-box warning'>";
        echo "<h3 style='margin-top:0;'><i data-lucide='loader' class='inline w-5 h-5'></i> Applying Fix...</h3>";
        
        $fixSuccess = true;
        $fixMessages = [];
        
        try {
            // Step 1: Make visitor_id nullable
            try {
                $pdo->exec("ALTER TABLE plot_renewal_requests MODIFY COLUMN visitor_id INT DEFAULT NULL");
                $fixMessages[] = "✓ Made visitor_id nullable";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    $fixMessages[] = "⚠ visitor_id: " . $e->getMessage();
                } else {
                    $fixMessages[] = "✓ visitor_id already nullable";
                }
            }
            
            // Step 2: Add or_number column
            try {
                $pdo->exec("ALTER TABLE plot_renewal_requests ADD COLUMN or_number VARCHAR(100) DEFAULT NULL AFTER requested_years");
                $fixMessages[] = "✓ Added or_number column";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    $fixMessages[] = "✓ or_number already exists";
                } else {
                    $fixMessages[] = "⚠ or_number: " . $e->getMessage();
                    $fixSuccess = false;
                }
            }
            
            // Step 3: Update foreign key
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
                    $pdo->exec("ALTER TABLE plot_renewal_requests DROP FOREIGN KEY {$fk}");
                    $fixMessages[] = "✓ Dropped old foreign key";
                }
                
                try {
                    $pdo->exec("
                        ALTER TABLE plot_renewal_requests
                        ADD CONSTRAINT plot_renewal_requests_ibfk_1
                        FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE SET NULL
                    ");
                    $fixMessages[] = "✓ Added new foreign key with SET NULL";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate') !== false) {
                        $fixMessages[] = "✓ Foreign key already correct";
                    } else {
                        throw $e;
                    }
                }
            } catch (PDOException $e) {
                $fixMessages[] = "⚠ Foreign key: " . $e->getMessage();
            }
            
        } catch (Exception $e) {
            $fixSuccess = false;
            $fixMessages[] = "✗ Unexpected error: " . $e->getMessage();
        }
        
        echo "<ul style='margin: 15px 0; padding-left: 20px;'>";
        foreach ($fixMessages as $msg) {
            echo "<li>{$msg}</li>";
        }
        echo "</ul>";
        
        if ($fixSuccess) {
            echo "<p style='color:#059669;font-weight:bold;font-size:16px;'>✓ Schema fix completed!</p>";
            echo "<p><a href='fix_renewal_system.php' style='color:#3b82f6;font-weight:600;'>← Refresh to verify</a></p>";
        }
        
        echo "</div>";
        
        // Re-check schema
        $stmt = $pdo->query("SHOW COLUMNS FROM plot_renewal_requests LIKE 'or_number'");
        $hasOrNumber = $stmt->fetch();
        $stmt = $pdo->query("SHOW COLUMNS FROM plot_renewal_requests LIKE 'visitor_id'");
        $col = $stmt->fetch();
        $isNullable = ($col && $col['Null'] === 'YES');
        
        $hasIssues = !$hasOrNumber || !$isNullable;
        $issues = [];
        if (!$hasOrNumber) $issues[] = "Missing or_number column";
        if (!$isNullable) $issues[] = "visitor_id must be nullable";
    }
    ?>

    <!-- Status -->
    <div class="status-box <?php echo $hasIssues ? 'error' : 'success'; ?>">
        <h3 style="margin-top:0;">
            <i data-lucide="<?php echo $hasIssues ? 'alert-circle' : 'check-circle'; ?>" class="inline w-5 h-5"></i>
            Schema Status
        </h3>
        
        <table>
            <tr>
                <th>Check</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>or_number column exists</td>
                <td><?php echo $hasOrNumber ? '<span style="color:#10b981;font-weight:bold;">✓ YES</span>' : '<span style="color:#ef4444;font-weight:bold;">✗ NO</span>'; ?></td>
            </tr>
            <tr>
                <td>visitor_id is nullable</td>
                <td><?php echo isset($isNullable) && $isNullable ? '<span style="color:#10b981;font-weight:bold;">✓ YES</span>' : '<span style="color:#ef4444;font-weight:bold;">✗ NO</span>'; ?></td>
            </tr>
        </table>
        
        <?php if ($hasIssues): ?>
            <p style="color:#ef4444;font-weight:bold;margin-top:15px;">Issues Found:</p>
            <ul style="margin:10px 0;padding-left:20px;color:#7f1d1d;">
                <?php foreach ($issues as $issue): ?>
                    <li><?php echo $issue; ?></li>
                <?php endforeach; ?>
            </ul>
            
            <form method="POST" style="margin-top:20px;">
                <button type="submit" name="apply_fix" class="btn">
                    <i data-lucide="wrench" class="inline w-4 h-4"></i> Apply Fix Now
                </button>
            </form>
        <?php else: ?>
            <p style="color:#059669;font-weight:bold;margin-top:15px;">✓ All checks passed! The renewal system is working correctly.</p>
            <p style="margin-top:10px;">
                <a href="expiring-plots.php" class="btn">Go to Expiring Plots</a>
                <a href="renewals.php" class="btn" style="background:#64748b;margin-left:10px;">View Renewal History</a>
            </p>
        <?php endif; ?>
    </div>

    <!-- Recent Renewals -->
    <div class="status-box">
        <h3 style="margin-top:0;">
            <i data-lucide="list" class="inline w-5 h-5"></i>
            Recent Renewal Activity
        </h3>
        
        <?php
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
            
            if (!empty($renewals)):
        ?>
                <p style="color:#059669;font-weight:600;">Found <?php echo count($renewals); ?> recent renewal(s)</p>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Plot</th>
                        <th>OR Number</th>
                        <th>Type</th>
                        <th>Date</th>
                    </tr>
                    <?php foreach ($renewals as $r): 
                        $plotName = $r['plot_type'] === 'burial' 
                            ? htmlspecialchars($r['decedent_name'] ?: 'Unknown')
                            : htmlspecialchars($r['available_plot'] ?: 'Plot #'.$r['plot_id']);
                    ?>
                    <tr>
                        <td>#<?php echo $r['id']; ?></td>
                        <td><?php echo $plotName; ?></td>
                        <td><?php echo htmlspecialchars($r['or_number'] ?? 'N/A'); ?></td>
                        <td><?php echo $r['plot_type']; ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($r['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
        <?php
            else:
                echo "<p style='color:#f59e0b;'>No approved renewals found yet.</p>";
            endif;
        } catch (PDOException $e) {
            echo "<p style='color:#ef4444;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <!-- Info -->
    <div class="status-box" style="border-color:#3b82f6;background:#eff6ff;">
        <h3 style="margin-top:0;color:#1e40af;">
            <i data-lucide="info" class="inline w-5 h-5"></i>
            What This Fix Does
        </h3>
        <ul style="margin:10px 0;padding-left:20px;line-height:1.8;">
            <li>Adds <code>or_number</code> column to store Official Receipt numbers</li>
            <li>Makes <code>visitor_id</code> nullable so admins can renew plots without a visitor</li>
            <li>Updates foreign key constraint to handle NULL visitor IDs</li>
        </ul>
        <p style="margin-top:15px;"><strong>After applying the fix:</strong></p>
        <ul style="margin:10px 0;padding-left:20px;line-height:1.8;">
            <li>Renewals from Expiring Plots page will work</li>
            <li>OR numbers will be recorded</li>
            <li>Renewals will appear in Renewal History</li>
            <li>Expiration dates will be extended by 5 years</li>
        </ul>
    </div>
</div>

<script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
