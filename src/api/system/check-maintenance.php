<?php
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

try {
    // Get current settings
    $settingsFile = '../../config/settings.json';
    $settings = file_exists($settingsFile) ? 
                json_decode(file_get_contents($settingsFile), true) : [];
    
    // Check maintenance mode status
    $maintenanceMode = !empty($settings['maintenance_mode']);
    
    // Get additional maintenance information
    $response = [
        'maintenance_mode' => $maintenanceMode,
        'message' => $settings['maintenance_message'] ?? null,
        'end_time' => $settings['maintenance_end_time'] ?? null
    ];
    
    // If maintenance mode is enabled but end time has passed, automatically disable it
    if ($maintenanceMode && !empty($response['end_time'])) {
        if (strtotime($response['end_time']) < time()) {
            // Disable maintenance mode
            $settings['maintenance_mode'] = false;
            unset($settings['maintenance_message']);
            unset($settings['maintenance_end_time']);
            
            // Save updated settings
            file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
            
            // Update response
            $response['maintenance_mode'] = false;
            $response['message'] = null;
            $response['end_time'] = null;
            
            // Log the automatic disable
            if (function_exists('logActivity')) {
                logActivity(
                    null,
                    'system',
                    'Maintenance mode automatically disabled (end time reached)',
                    'success'
                );
            }
        }
    }
    
    // Add system status information
    $response['system_status'] = [
        'database' => checkDatabaseConnection(),
        'file_system' => checkFileSystem(),
        'required_directories' => checkRequiredDirectories(),
        'php_extensions' => checkPhpExtensions()
    ];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    // Log the error
    error_log("Error checking maintenance status: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Check database connection
 */
function checkDatabaseConnection() {
    try {
        $pdo = getConnection();
        $pdo->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check file system
 */
function checkFileSystem() {
    $testFile = '../../temp/test_' . uniqid() . '.txt';
    try {
        // Try to create a test file
        if (!file_put_contents($testFile, 'test')) {
            return false;
        }
        
        // Try to read the test file
        if (!file_get_contents($testFile)) {
            return false;
        }
        
        // Try to delete the test file
        if (!unlink($testFile)) {
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        if (file_exists($testFile)) {
            @unlink($testFile);
        }
        return false;
    }
}

/**
 * Check required directories
 */
function checkRequiredDirectories() {
    $directories = [
        '../../uploads',
        '../../backup',
        '../../logs',
        '../../temp'
    ];
    
    $status = [];
    foreach ($directories as $dir) {
        $status[basename($dir)] = [
            'exists' => file_exists($dir),
            'writable' => is_writable($dir)
        ];
    }
    
    return $status;
}

/**
 * Check required PHP extensions
 */
function checkPhpExtensions() {
    $required = [
        'pdo',
        'pdo_mysql',
        'mbstring',
        'json',
        'gd',
        'zip'
    ];
    
    $status = [];
    foreach ($required as $ext) {
        $status[$ext] = extension_loaded($ext);
    }
    
    return $status;
}
