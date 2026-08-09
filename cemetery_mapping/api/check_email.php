<?php
/**
 * Check Email Availability API
 * Returns whether an email is already registered
 */

header('Content-Type: application/json');
require_once '../config/database.php';

$email = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM visitors WHERE email = ?");
    $stmt->execute([$email]);
    
    echo json_encode(['exists' => $stmt->fetch() !== false]);
} catch (PDOException $e) {
    error_log("Email check error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
