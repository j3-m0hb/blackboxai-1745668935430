<?php
// Application configuration

// Base URL - Update this according to your server setup
define('BASE_URL', 'http://localhost/kepeg_sbe');

// Application settings
define('APP_NAME', 'PT. Sejahtera Bersama Express');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Asia/Jakarta');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'kepeg_sbe_session');

// Upload settings
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
]);

// Pagination settings
define('ITEMS_PER_PAGE', 10);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Content-Type: text/html; charset=utf-8');

// Create required directories if they don't exist
$directories = [
    UPLOAD_PATH,
    UPLOAD_PATH . '/documents',
    UPLOAD_PATH . '/photos',
    __DIR__ . '/../logs',
    __DIR__ . '/../backup'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Clean old sessions periodically (1% chance on each request)
if (mt_rand(1, 100) === 1) {
    $session_files = glob(session_save_path() . '/sess_*');
    foreach ($session_files as $file) {
        if (filemtime($file) + SESSION_LIFETIME < time()) {
            @unlink($file);
        }
    }
}
