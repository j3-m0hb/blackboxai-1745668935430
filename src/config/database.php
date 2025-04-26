<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kepeg_sbe');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    // Log error and show generic message
    error_log("Database Connection Error: " . $e->getMessage());
    die("Maaf, terjadi kesalahan koneksi ke database. Silakan coba beberapa saat lagi.");
}

// Function to get PDO connection
function getConnection() {
    global $pdo;
    return $pdo;
}

// Function to safely execute queries
function executeQuery($sql, $params = []) {
    try {
        $stmt = getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        throw new Exception("Maaf, terjadi kesalahan dalam memproses data.");
    }
}

// Function to get single row
function fetchOne($sql, $params = []) {
    return executeQuery($sql, $params)->fetch();
}

// Function to get multiple rows
function fetchAll($sql, $params = []) {
    return executeQuery($sql, $params)->fetchAll();
}

// Function to insert and get last insert id
function insert($sql, $params = []) {
    executeQuery($sql, $params);
    return getConnection()->lastInsertId();
}

// Function to update or delete
function execute($sql, $params = []) {
    return executeQuery($sql, $params)->rowCount();
}
