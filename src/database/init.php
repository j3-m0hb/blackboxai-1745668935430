<?php
require_once '../config/database.php';
require_once '../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);

    // Read and execute SQL file
    $sql = file_get_contents('schema.sql');
    $pdo->exec($sql);

    // Insert default admin user
    $defaultAdmin = [
        'username' => 'admin',
        'password' => md5('admin123'), // In production, use a more secure password
        'level' => 'admin'
    ];

    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, level) 
        VALUES (:username, :password, :level)
        ON DUPLICATE KEY UPDATE 
        password = VALUES(password),
        level = VALUES(level)
    ");
    $stmt->execute($defaultAdmin);

    // Insert sample data for testing
    $sampleData = file_get_contents('sample_data.sql');
    if ($sampleData) {
        $pdo->exec($sampleData);
    }

    echo "Database initialized successfully!\n";
    echo "Default admin credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "\nPlease change these credentials immediately after first login.\n";

} catch (PDOException $e) {
    die("Database initialization failed: " . $e->getMessage() . "\n");
}
