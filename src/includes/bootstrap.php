<?php
/**
 * Application Bootstrap
 * 
 * This file initializes the application and loads all required dependencies.
 * Include this file at the start of every PHP script.
 */

// Define application root path
define('APP_ROOT', realpath(__DIR__ . '/..'));

// Define environment (development/production)
define('DEVELOPMENT_MODE', true); // Change to false in production

// Load configuration files
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';

// Load error handler
require_once APP_ROOT . '/includes/error_handler.php';

// Load helper functions
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/functions.php';

// Initialize autoloader for vendor packages
if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

// Set default timezone
$settings = getSettings();
date_default_timezone_set($settings['timezone'] ?? 'Asia/Jakarta');

// Set locale
setlocale(LC_ALL, $settings['locale'] ?? 'id_ID.utf8');

// Initialize database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch (PDOException $e) {
    // Log the error
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show error page
    if (DEVELOPMENT_MODE) {
        throw $e;
    } else {
        require_once APP_ROOT . '/includes/error_pages/db_error.php';
        exit();
    }
}

// Create required directories if they don't exist
$directories = [
    APP_ROOT . '/logs',
    APP_ROOT . '/uploads',
    APP_ROOT . '/uploads/documents',
    APP_ROOT . '/uploads/photos',
    APP_ROOT . '/uploads/system',
    APP_ROOT . '/backup',
    APP_ROOT . '/backup/database',
    APP_ROOT . '/backup/settings',
    APP_ROOT . '/temp'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Clean old files from temp directory (files older than 24 hours)
$tempFiles = glob(APP_ROOT . '/temp/*');
foreach ($tempFiles as $file) {
    if (is_file($file) && time() - filemtime($file) > 86400) {
        unlink($file);
    }
}

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.gc_maxlifetime', ($settings['session_timeout'] ?? 60) * 60);
    
    session_name('kepeg_sbe_session');
    session_start();
}

// Check if system is installed
if (!file_exists(APP_ROOT . '/.installed') && 
    basename($_SERVER['SCRIPT_NAME']) !== 'install.php') {
    header('Location: install.php');
    exit();
}

// Load middleware
require_once APP_ROOT . '/includes/middleware.php';

/**
 * Function to get global PDO instance
 */
function getConnection() {
    global $pdo;
    return $pdo;
}

/**
 * Function to check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Function to check if request is POST
 */
function isPostRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Function to get current script name
 */
function getCurrentScript() {
    return basename($_SERVER['SCRIPT_NAME']);
}

/**
 * Function to check if development mode is enabled
 */
function isDevelopmentMode() {
    return DEVELOPMENT_MODE;
}

/**
 * Function to get application version
 */
function getAppVersion() {
    return APP_VERSION;
}

/**
 * Function to get application environment
 */
function getEnvironment() {
    return DEVELOPMENT_MODE ? 'development' : 'production';
}

// Set common headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

if (!empty($settings['security']['force_ssl'])) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Set content security policy
$csp = "default-src 'self'; " .
       "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; " .
       "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
       "img-src 'self' data: https:; " .
       "font-src 'self' https://cdn.jsdelivr.net; " .
       "connect-src 'self';";

header("Content-Security-Policy: $csp");
