<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get all available plots
try {
    $stmt = $pdo->query("
        SELECT 
            id,
            plot_number,
            latitude,
            longitude,
            notes,
            added_by,
            date_added,
            compartment_count
        FROM available_plots 
        ORDER BY plot_number ASC, id ASC
    ");
    $plots = $stmt->fetchAll();
    $totalPlots = count($plots);
    
    // Count total compartments
    $totalCompartments = 0;
    foreach ($plots as $plot) {
        $totalCompartments += $plot['compartment_count'] ?: 1;
    }
    
} catch (PDOException $e) {
    error_log("Plots error: " . $e->getMessage());
    exit('Error generating report');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Plots Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: white;
            color: #000;
            font-size: 10px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #22c55e;
        }
        
        .report-header h1 {
            font-size: 20px;
            color: #22c55e;
            margin-bottom: 3px;
        }
        
        .report-header h2 {
            font-size: 16px;
            color: #333;
            margin: 3px 0;
        }
        
        .report-header p {
            font-size: 10px;
            color: #666;
        }
        
        .summary-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-box {
            background: #f9fafb;
            border: 2px solid #22c55e;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        
        .summary-box .count {
            font-size: 28px;
            font-weight: bold;
            color: #22c55e;
        }
        
        .summary-box .label {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }
        
        .section-breakdown {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            padding: 12px;
            border-radius: 6px;
        }
        
        .section-breakdown h3 {
            font-size: 12px;
            margin-bottom: 8px;
            color: #333;
        }
        
        .section-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .section-item:last-child {
            border-bottom: none;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        
        th {
            background: #22c55e;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: 600;
            font-size: 9px;
        }
        
        td {
            padding: 6px;
            border: 1px solid #e5e7eb;
        }
        
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        tbody tr:hover {
            background: #f3f4f6;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 600;
        }
        
        .badge-available {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-reserved {
            background: #fef3c7;
            color: #92400e;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        
        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #22c55e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #5568d3;
        }
        
        @media print {
            .print-button {
                display: none;
            }
            
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
            
            body {
                padding: 0;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Print Report</button>
    
    <div class="report-header">
        <h1>MATINAO MEMORIAL CEMETERY</h1>
        <h2>Available Plots Report</h2>
        <p>Generated on <?php echo date('F d, Y'); ?> at <?php echo date('h:i A'); ?></p>
    </div>
    
    <div class="summary-section">
        <div class="summary-box">
            <div class="count"><?php echo number_format($totalPlots); ?></div>
            <div class="label">Total Available Plots</div>
        </div>
        
        <div class="summary-box" style="border-color: #5a9b6f;">
            <div class="count" style="color: #5a9b6f;"><?php echo number_format($totalCompartments); ?></div>
            <div class="label">Total Compartments/Spaces</div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">ID</th>
                <th style="width: 18%;">Plot Number</th>
                <th style="width: 22%;">Coordinates</th>
                <th style="width: 12%;">Compartments</th>
                <th style="width: 15%;">Added By</th>
                <th style="width: 15%;">Date Added</th>
                <th style="width: 10%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($plots)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #666;">
                        No available plots found
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($plots as $plot): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo htmlspecialchars($plot['id']); ?></td>
                        <td><strong><?php echo htmlspecialchars($plot['plot_number'] ?: 'Plot #' . $plot['id']); ?></strong></td>
                        <td style="font-size: 8px;">
                            <?php 
                            if ($plot['latitude'] && $plot['longitude']) {
                                echo number_format($plot['latitude'], 6) . ', ' . number_format($plot['longitude'], 6);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-available"><?php echo $plot['compartment_count'] ?: 1; ?> spaces</span>
                        </td>
                        <td><?php echo htmlspecialchars($plot['added_by'] ?: 'Admin'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($plot['date_added'])); ?></td>
                        <td style="font-size: 8px;">
                            <?php echo htmlspecialchars(substr($plot['notes'] ?: '-', 0, 30)); ?>
                            <?php if (strlen($plot['notes']) > 30) echo '...'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p><strong>Matinao Memorial Cemetery Management System</strong></p>
        <p>This report contains all available plots as of <?php echo date('F d, Y'); ?>. Total plots: <?php echo number_format($totalPlots); ?></p>
    </div>
</body>
</html>
