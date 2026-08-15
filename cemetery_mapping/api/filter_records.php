<?php
/**
 * Filter Burial Records API
 * Returns filtered burial records as JSON for AJAX requests
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$search = trim($_GET['search'] ?? '');
$barangay = trim($_GET['barangay'] ?? '');
$type = trim($_GET['type'] ?? 'all');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(decedent_name LIKE ? OR family_name LIKE ? OR plot_number LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($barangay)) {
    $where[] = "barangay = ?";
    $params[] = $barangay;
}

if ($type === 'premium') {
    $where[] = "is_fenced = 1";
} elseif ($type === 'standard') {
    $where[] = "is_fenced = 0";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Total count
    $countSql = "SELECT COUNT(*) FROM burial_records $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    $total_pages = ceil($total / $per_page);

    // Get records
    $sql = "
        SELECT id, decedent_name, family_name, birth_date, death_date, plot_number,
               barangay, is_fenced, photo, date_added
        FROM burial_records
        $whereClause
        ORDER BY date_added DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'records' => $records,
        'total' => $total,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);
} catch (PDOException $e) {
    error_log("Filter records error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
