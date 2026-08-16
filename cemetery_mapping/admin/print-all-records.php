<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get all burial records
try {
    $stmt = $pdo->query("
        SELECT 
            id,
            decedent_name,
            birth_date,
            death_date,
            barangay,
            plot_number,
            latitude,
            longitude,
            is_fenced,
            family_name,
            date_added
        FROM burial_records 
        ORDER BY date_added DESC, decedent_name ASC
    ");
    $records = $stmt->fetchAll();
    $totalRecords = count($records);
} catch (PDOException $e) {
    error_log("Records error: " . $e->getMessage());
    exit('Error generating report');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Burial Records Report</title>
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
        
        .summary-box {
            background: #f9fafb;
            border: 2px solid #22c55e;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            text-align: center;
        }
        
        .summary-box .count {
            font-size: 24px;
            font-weight: bold;
            color: #22c55e;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        
        th {
            background: #22c55e;
            color: white;
            padding: 8px 4px;
            text-align: left;
            font-weight: 600;
            font-size: 9px;
        }
        
        td {
            padding: 6px 4px;
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
        
        .badge-premium {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-standard {
            background: #dbeafe;
            color: #1e40af;
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
                size: A4 landscape;
                margin: 12mm;
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
        <h2>All Burial Records Report</h2>
        <p>Generated on <?php echo date('F d, Y'); ?> at <?php echo date('h:i A'); ?></p>
    </div>
    
    <div class="summary-box">
        <div class="count"><?php echo number_format($totalRecords); ?></div>
        <div style="font-size: 11px; color: #666; margin-top: 3px;">Total Burial Records</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th style="width: 22%;">Deceased Name</th>
                <th style="width: 10%;">Birth Date</th>
                <th style="width: 10%;">Death Date</th>
                <th style="width: 10%;">Plot Number</th>
                <th style="width: 13%;">Barangay</th>
                <th style="width: 12%;">Coordinates</th>
                <th style="width: 8%;">Type</th>
                <th style="width: 10%;">Date Added</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px; color: #666;">
                        No burial records found
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $record): ?>
                    <?php
                    // Calculate age if birth_date and death_date exist
                    $age = '-';
                    if ($record['birth_date'] && $record['death_date']) {
                        $birthDate = new DateTime($record['birth_date']);
                        $deathDate = new DateTime($record['death_date']);
                        $age = $birthDate->diff($deathDate)->y;
                    }
                    ?>
                    <tr>
                        <td style="text-align: center;"><?php echo htmlspecialchars($record['id']); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($record['decedent_name']); ?></strong>
                            <?php if ($record['family_name']): ?>
                                <br><span style="font-size: 8px; color: #666;">Family: <?php echo htmlspecialchars($record['family_name']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $record['birth_date'] ? date('M d, Y', strtotime($record['birth_date'])) : '-'; ?></td>
                        <td>
                            <?php echo $record['death_date'] ? date('M d, Y', strtotime($record['death_date'])) : '-'; ?>
                            <?php if ($age !== '-'): ?>
                                <br><span style="font-size: 8px; color: #666;">(Age: <?php echo $age; ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($record['plot_number'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($record['barangay']); ?></td>
                        <td style="font-size: 8px;">
                            <?php 
                            if ($record['latitude'] && $record['longitude']) {
                                echo number_format($record['latitude'], 5) . ', ' . number_format($record['longitude'], 5);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($record['is_fenced']): ?>
                                <span class="badge badge-premium">PREMIUM</span>
                            <?php else: ?>
                                <span class="badge badge-standard">STANDARD</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($record['date_added'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p><strong>Matinao Memorial Cemetery Management System</strong></p>
        <p>This report contains all burial records as of <?php echo date('F d, Y'); ?>. Total records: <?php echo number_format($totalRecords); ?></p>
    </div>
</body>
</html>
