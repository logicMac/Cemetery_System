<?php
/**
 * Generate Report API
 * Exports data in CSV or PDF format
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

require_once '../config/database.php';

$type = $_GET['type'] ?? 'all_records';
$format = $_GET['format'] ?? 'csv';

// Custom date range parameters
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$report_type = $_GET['report_type'] ?? 'burials';
$barangay = $_GET['barangay'] ?? null;

try {
    if ($type === 'all_records') {
        $stmt = $pdo->query("
            SELECT decedent_name, family_name, birth_date, death_date, plot_number, 
                   barangay, latitude, longitude, is_fenced, date_added
            FROM burial_records 
            ORDER BY date_added DESC
        ");
        $data = $stmt->fetchAll();
        $filename = 'all_burial_records';
        $headers = ['Name', 'Family', 'Birth Date', 'Death Date', 'Plot', 'Barangay', 'Latitude', 'Longitude', 'Type', 'Date Added'];
        
    } elseif ($type === 'statistics') {
        $byBarangay = $pdo->query("
            SELECT barangay, COUNT(*) as count 
            FROM burial_records 
            WHERE barangay IS NOT NULL 
            GROUP BY barangay 
            ORDER BY count DESC
        ")->fetchAll();
        
        $data = $byBarangay;
        $filename = 'cemetery_statistics';
        $headers = ['Barangay', 'Count'];
        
    } elseif ($type === 'available_plots') {
        $stmt = $pdo->query("
            SELECT plot_number, latitude, longitude, notes, has_grid, 
                   grid_rows, grid_cols, compartment_count, date_added
            FROM available_plots 
            ORDER BY date_added DESC
        ");
        $data = $stmt->fetchAll();
        $filename = 'available_plots';
        $headers = ['Plot Number', 'Latitude', 'Longitude', 'Notes', 'Has Grid', 'Rows', 'Cols', 'Compartments', 'Date Added'];
        
    } elseif ($start_date && $end_date) {
        // Custom date range report
        $sql = "SELECT decedent_name, family_name, birth_date, death_date, plot_number, barangay, date_added
                FROM burial_records WHERE ";
        
        if ($report_type === 'burials') {
            $sql .= "date_added BETWEEN ? AND ?";
        } else {
            $sql .= "death_date BETWEEN ? AND ?";
        }
        
        if ($barangay) {
            $sql .= " AND barangay = ?";
        }
        
        $sql .= " ORDER BY date_added DESC";
        
        $stmt = $pdo->prepare($sql);
        $params = [$start_date, $end_date];
        if ($barangay) {
            $params[] = $barangay;
        }
        $stmt->execute($params);
        
        $data = $stmt->fetchAll();
        $filename = 'custom_report_' . $start_date . '_to_' . $end_date;
        $headers = ['Name', 'Family', 'Birth Date', 'Death Date', 'Plot', 'Barangay', 'Date Added'];
    } else {
        http_response_code(400);
        exit('Invalid report type');
    }
    
    if ($format === 'csv') {
        exportCSV($data, $headers, $filename);
    } else {
        exportPDF($data, $headers, $filename);
    }
    
} catch (PDOException $e) {
    error_log("Report generation error: " . $e->getMessage());
    http_response_code(500);
    exit('Error generating report');
}

function exportCSV($data, $headers, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        $rowData = array_values((array)$row);
        
        // Format is_fenced
        if (isset($row['is_fenced'])) {
            $key = array_search($row['is_fenced'], $rowData);
            if ($key !== false) {
                $rowData[$key] = $row['is_fenced'] == 1 ? 'Premium' : 'Standard';
            }
        }
        
        fputcsv($output, $rowData);
    }
    
    fclose($output);
    exit;
}

function exportPDF($data, $headers, $filename) {
    // Simple HTML to PDF conversion
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    
    // For a simple implementation, we'll create an HTML page that can be printed to PDF
    // In production, you'd use a library like TCPDF or mPDF
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($filename) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #667eea; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <h1>Matinao Memorial Cemetery - ' . htmlspecialchars($filename) . '</h1>
    <p>Generated: ' . date('F d, Y H:i:s') . '</p>
    <p>Total Records: ' . count($data) . '</p>
    
    <table>
        <thead>
            <tr>';
    
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    
    echo '</tr>
        </thead>
        <tbody>';
    
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $key => $value) {
            if ($key === 'is_fenced') {
                $value = $value == 1 ? 'Premium' : 'Standard';
            }
            echo '<td>' . htmlspecialchars($value ?? 'N/A') . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody>
    </table>
    
    <div class="footer">
        <p>Matinao Memorial Cemetery Management System</p>
        <p>This report is confidential and intended for authorized personnel only.</p>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>';
    
    exit;
}
