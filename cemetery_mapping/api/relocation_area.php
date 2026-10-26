<?php
/**
 * Relocation Area API
 * GET  — returns the relocation point {lat, lng} (or null if unset)
 * POST — saves the relocation point (admin only)
 */

session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $lat = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'relocation_lat'")->fetchColumn();
        $lng = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'relocation_lng'")->fetchColumn();
        echo json_encode([
            'success' => true,
            'set' => ($lat !== false && $lng !== false && $lat !== '' && $lng !== ''),
            'lat' => $lat !== false && $lat !== '' ? (float)$lat : null,
            'lng' => $lng !== false && $lng !== '' ? (float)$lng : null,
        ]);
    } catch (PDOException $e) {
        error_log('Relocation area get error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);

if ($lat === false || $lng === false || $lat === null || $lng === null
    || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute(['relocation_lat', (string)$lat]);
    $stmt->execute(['relocation_lng', (string)$lng]);
    echo json_encode(['success' => true, 'message' => 'Relocation area saved', 'lat' => $lat, 'lng' => $lng]);
} catch (PDOException $e) {
    error_log('Relocation area save error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
