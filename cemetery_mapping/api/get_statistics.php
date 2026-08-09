<?php
/**
 * Get Statistics API
 * Returns comprehensive cemetery statistics
 */

header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Total counts
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn();
    $totalPlots = $pdo->query("SELECT COUNT(*) FROM available_plots")->fetchColumn();
    $totalVisitors = $pdo->query("SELECT COUNT(*) FROM visitors WHERE is_active = 1")->fetchColumn();
    $premiumPlots = $pdo->query("SELECT COUNT(*) FROM burial_records WHERE is_fenced = 1")->fetchColumn();
    $standardPlots = $totalRecords - $premiumPlots;
    
    // This month
    $thisMonth = $pdo->query("
        SELECT COUNT(*) FROM burial_records 
        WHERE MONTH(date_added) = MONTH(CURRENT_DATE()) 
        AND YEAR(date_added) = YEAR(CURRENT_DATE())
    ")->fetchColumn();
    
    // This year
    $thisYear = $pdo->query("
        SELECT COUNT(*) FROM burial_records 
        WHERE YEAR(date_added) = YEAR(CURRENT_DATE())
    ")->fetchColumn();
    
    // By barangay
    $byBarangay = $pdo->query("
        SELECT barangay, COUNT(*) as count 
        FROM burial_records 
        WHERE barangay IS NOT NULL 
        GROUP BY barangay 
        ORDER BY count DESC
    ")->fetchAll();
    
    // By year (last 5 years)
    $byYear = $pdo->query("
        SELECT YEAR(death_date) as year, COUNT(*) as count 
        FROM burial_records 
        WHERE death_date IS NOT NULL 
        AND YEAR(death_date) >= YEAR(CURRENT_DATE()) - 5
        GROUP BY YEAR(death_date) 
        ORDER BY year DESC
    ")->fetchAll();
    
    // Monthly trend (last 12 months)
    $monthlyTrend = $pdo->query("
        SELECT DATE_FORMAT(date_added, '%Y-%m') as month, COUNT(*) as count 
        FROM burial_records 
        WHERE date_added >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(date_added, '%Y-%m')
        ORDER BY month ASC
    ")->fetchAll();
    
    // Recent activity
    $recentRecords = $pdo->query("
        SELECT id, decedent_name, plot_number, date_added 
        FROM burial_records 
        ORDER BY date_added DESC 
        LIMIT 10
    ")->fetchAll();
    
    echo json_encode([
        'success' => true,
        'statistics' => [
            'totals' => [
                'records' => $totalRecords,
                'available_plots' => $totalPlots,
                'visitors' => $totalVisitors,
                'premium_plots' => $premiumPlots,
                'standard_plots' => $standardPlots
            ],
            'periods' => [
                'this_month' => $thisMonth,
                'this_year' => $thisYear
            ],
            'by_barangay' => $byBarangay,
            'by_year' => $byYear,
            'monthly_trend' => $monthlyTrend,
            'recent_records' => $recentRecords
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Get statistics error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
