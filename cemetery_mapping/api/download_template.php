<?php
/**
 * Download CSV Import Template
 * Serves a ready-to-fill CSV template for bulk burial record import.
 */

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="burial_records_template.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, [
    'decedent_name', 'family_name', 'birth_date', 'death_date', 'burial_date',
    'burial_time', 'expiration_date', 'plot_number', 'barangay', 'memory_space',
    'is_fenced', 'latitude', 'longitude'
]);
// Example row
fputcsv($out, [
    'Juan Dela Cruz', 'Dela Cruz', '1950-01-15', '2026-02-20', '2026-02-25',
    '09:00', '2031-02-25', 'A-001', 'Matinao', 'Beloved father',
    '0', '6.183500', '125.084500'
]);
fclose($out);
