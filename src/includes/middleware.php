<?php
/**
 * Application Middleware
 * 
 * Handles:
 * - Maintenance mode checks
 * - User authentication
 * - Session management
 * - Access control
 * - Request logging
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current script name
$currentScript = basename($_SERVER['SCRIPT_NAME']);

// List of pages that don't require authentication
$publicPages = [
    'login.php',
    'install.php',
    'maintenance.php'
];

// List of pages accessible during maintenance mode
$maintenanceAccessiblePages = [
    'login.php',
    'maintenance.php',
    'install.php',
    'api/system/check-maintenance.php'
];

// Get settings
$settingsFile = __DIR__ . '/../config/settings.json';
$settings = file_exists($settingsFile) ? 
            json_decode(file_get_contents($settingsFile), true) : [];

/**
 * Check maintenance mode
 */
function checkMaintenanceMode() {
    global $settings, $currentScript, $maintenanceAccessiblePages;
    
    // Check if maintenance mode is enabled
    if (!empty($settings['maintenance_mode'])) {
        // Allow admin access during maintenance
        if (isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'admin') {
            return;
        }
        
        // Allow access to maintenance-accessible pages
        if (in_array($currentScript, $maintenanceAccessiblePages)) {
            return;
        }
        
        // Check if it's an API request
        $isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
        
        if ($isApiRequest) {
            http_response_code(503);
            echo json_encode([
                'error' => 'System is under maintenance',
                'maintenance_mode' => true,
                'message' => $settings['maintenance_message'] ?? null,
                'end_time' => $settings['maintenance_end_time'] ?? null
            ]);
            exit();
        } else {
            // Redirect to maintenance page
            header('Location: ' . getBaseUrl() . 'maintenance.php');
            exit();
        }
    }
}

/**
 * Check authentication
 */
function checkAuthentication() {
    global $currentScript, $publicPages;
    
    // Skip authentication for public pages
    if (in_array($currentScript, $publicPages)) {
        return;
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Check if it's an API request
        $isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
        
        if ($isApiRequest) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        } else {
            // Store intended URL for redirect after login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            
            // Redirect to login page
            header('Location: ' . getBaseUrl() . 'login.php');
            exit();
        }
    }
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    global $settings;
    
    if (isset($_SESSION['user_id'])) {
        $timeout = ($settings['session_timeout'] ?? 60) * 60; // Convert minutes to seconds
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        
        if (time() - $lastActivity > $timeout) {
            // Session has expired
            session_destroy();
            
            // Check if it's an API request
            $isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
            
            if ($isApiRequest) {
                http_response_code(440); // Login Time-out
                echo json_encode(['error' => 'Session expired']);
                exit();
            } else {
                header('Location: ' . getBaseUrl() . 'login.php?expired=1');
                exit();
            }
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Log request
 */
function logRequest() {
    if (!function_exists('logActivity')) {
        return;
    }
    
    // Don't log certain requests
    $skipPaths = [
        '/api/system/check-maintenance.php',
        '/assets/',
        '/uploads/'
    ];
    
    foreach ($skipPaths as $path) {
        if (strpos($_SERVER['REQUEST_URI'], $path) !== false) {
            return;
        }
    }
    
    // Log the request
    logActivity(
        $_SESSION['user_id'] ?? null,
        'request',
        $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'],
        'success',
        null,
        null,
        [
            'method' => $_SERVER['REQUEST_METHOD'],
            'url' => $_SERVER['REQUEST_URI'],
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]
    );
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Ensure path ends with a slash
    if (substr($path, -1) !== '/') {
        $path .= '/';
    }
    
    return $protocol . $host . $path;
}

// Run middleware checks
checkMaintenanceMode();
checkAuthentication();
checkSessionTimeout();
logRequest();

// Set security headers
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
