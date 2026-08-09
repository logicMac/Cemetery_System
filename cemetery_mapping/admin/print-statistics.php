<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get statistics
try {
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn();
    $totalPlots = $pdo->query("SELECT COUNT(*) FROM available_plots")->fetchColumn();
    $totalVisitors = $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    $premiumPlots = $pdo->query("SELECT COUNT(*) FROM burial_records WHERE is_fenced = 1")->fetchColumn();
    $standardPlots = $totalRecords - $premiumPlots;
    
    $byBarangay = $pdo->query("
        SELECT barangay, COUNT(*) as count 
        FROM burial_records 
        WHERE barangay IS NOT NULL 
        GROUP BY barangay 
        ORDER BY count DESC
    ")->fetchAll();
    
    $byYear = $pdo->query("
        SELECT YEAR(death_date) as year, COUNT(*) as count 
        FROM burial_records 
        WHERE death_date IS NOT NULL 
        GROUP BY YEAR(death_date) 
        ORDER BY year DESC 
        LIMIT 10
    ")->fetchAll();
    
} catch (PDOException $e) {
    error_log("Statistics error: " . $e->getMessage());
    exit('Error generating report');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cemetery Statistics Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background: white;
            color: #000;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        
        .report-header h1 {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .report-header p {
            font-size: 12px;
            color: #666;
        }
        
        .summary-section {
            margin-bottom: 30px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            border: 2px solid #e5e7eb;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
            background: #f9fafb;
        }
        
        .summary-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin: 5px 0;
        }
        
        .summary-card .label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 25px 0 10px 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
        }
        
        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
        }
        
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        tbody tr:hover {
            background: #f3f4f6;
        }
        
        .percentage-bar {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .bar-container {
            flex: 1;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        
        .percentage-value {
            min-width: 45px;
            text-align: right;
            font-weight: 600;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
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
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Print Report</button>
    
    <div class="report-header">
        <h1>MATINAO MEMORIAL CEMETERY</h1>
        <h2 style="font-size: 18px; margin: 5px 0;">Statistics Report</h2>
        <p>Generated on <?php echo date('F d, Y'); ?> at <?php echo date('h:i A'); ?></p>
    </div>
    
    <div class="summary-section">
        <h3 class="section-title">SUMMARY OVERVIEW</h3>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Total Records</div>
                <div class="value"><?php echo number_format($totalRecords); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Premium Plots</div>
                <div class="value"><?php echo number_format($premiumPlots); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Available Plots</div>
                <div class="value"><?php echo number_format($totalPlots); ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Registered Visitors</div>
                <div class="value"><?php echo number_format($totalVisitors); ?></div>
            </div>
        </div>
    </div>
    
    <h3 class="section-title">DETAILED STATISTICS</h3>
    
    <div class="data-grid">
        <!-- Records by Barangay -->
        <div>
            <h4 style="font-size: 13px; margin-bottom: 10px; color: #333;">Distribution by Barangay</h4>
            <table>
                <thead>
                    <tr>
                        <th>Barangay</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byBarangay as $item): ?>
                        <?php $percentage = ($item['count'] / $totalRecords) * 100; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['barangay'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="text-align: center;"><?php echo number_format($item['count']); ?></td>
                            <td>
                                <div class="percentage-bar">
                                    <div class="bar-container">
                                        <div class="bar-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                    <span class="percentage-value"><?php echo number_format($percentage, 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Plot Type Distribution -->
        <div>
            <h4 style="font-size: 13px; margin-bottom: 10px; color: #333;">Plot Type Distribution</h4>
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Standard Burial</td>
                        <td style="text-align: center;"><?php echo number_format($standardPlots); ?></td>
                        <td style="text-align: center;"><?php echo number_format(($standardPlots / $totalRecords) * 100, 1); ?>%</td>
                    </tr>
                    <tr>
                        <td>Premium/Fenced</td>
                        <td style="text-align: center;"><?php echo number_format($premiumPlots); ?></td>
                        <td style="text-align: center;"><?php echo number_format(($premiumPlots / $totalRecords) * 100, 1); ?>%</td>
                    </tr>
                    <tr>
                        <td>Available Plots</td>
                        <td style="text-align: center;"><?php echo number_format($totalPlots); ?></td>
                        <td style="text-align: center;">-</td>
                    </tr>
                    <tr style="background: #f3f4f6; font-weight: 600;">
                        <td>TOTAL CAPACITY</td>
                        <td style="text-align: center;"><?php echo number_format($totalRecords + $totalPlots); ?></td>
                        <td style="text-align: center;">100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Deaths by Year -->
    <h4 style="font-size: 13px; margin: 20px 0 10px 0; color: #333;">Deaths by Year (Last 10 Years)</h4>
    <table style="max-width: 600px;">
        <thead>
            <tr>
                <th>Year</th>
                <th>Count</th>
                <th>Percentage of Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($byYear as $item): ?>
                <?php $percentage = ($item['count'] / $totalRecords) * 100; ?>
                <tr>
                    <td><?php echo $item['year']; ?></td>
                    <td style="text-align: center;"><?php echo number_format($item['count']); ?></td>
                    <td style="text-align: center;"><?php echo number_format($percentage, 1); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p><strong>Matinao Memorial Cemetery Management System</strong></p>
        <p>This report is computer-generated and contains cemetery statistics as of <?php echo date('F d, Y'); ?>.</p>
        <p>For inquiries, please contact the cemetery office.</p>
    </div>
</body>
</html>
