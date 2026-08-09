<?php
/**
 * Database Configuration
 * Establishes PDO connection to MySQL database
 * Uses utf8mb4 charset for full Unicode support
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'cemetery_mapping1');
define('DB_USER', 'root');
define('DB_PASS', '');

// PDO options for security and performance
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        $options
    );
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please contact the administrator.");
}

/**
 * Helper function to execute prepared statements safely
 * @param PDO $pdo Database connection
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement
 */
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        throw $e;
    }
}
