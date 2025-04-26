<?php
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

try {
    // Define default settings
    $defaultSettings = [
        // Company Settings
        'company_name' => 'PT. Sejahtera Bersama Express',
        'legal_name' => 'PT. Sejahtera Bersama Express',
        'address' => '',
        'contact_info' => '',
        'tax_id' => '',
        'logo' => '',
        
        // System Settings
        'default_language' => 'id',
        'timezone' => 'Asia/Jakarta',
        'date_format' => 'd/m/Y',
        'items_per_page' => 25,
        'session_timeout' => 60,
        'maintenance_mode' => false,
        
        // Email Settings
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'from_email' => '',
        'from_name' => '',
        
        // Security Settings
        'min_password_length' => 8,
        'require_uppercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'max_login_attempts' => 5,
        'lockout_duration' => 30,
        'password_expiry' => 90
    ];
    
    // Get current settings to preserve sensitive data if needed
    $settingsFile = '../../config/settings.json';
    $currentSettings = file_exists($settingsFile) ? 
                      json_decode(file_get_contents($settingsFile), true) : [];
    
    // Preserve existing logo if any
    if (!empty($currentSettings['logo'])) {
        $defaultSettings['logo'] = $currentSettings['logo'];
    }
    
    // Save default settings
    if (!file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save default settings');
    }
    
    // Update PHP settings that can be changed at runtime
    date_default_timezone_set($defaultSettings['timezone']);
    ini_set('session.gc_maxlifetime', $defaultSettings['session_timeout'] * 60);
    
    // Create backup of old settings
    $backupDir = '../../backup/settings';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $backupFile = $backupDir . '/settings_backup_' . date('Y-m-d_His') . '.json';
    file_put_contents($backupFile, json_encode($currentSettings, JSON_PRETTY_PRINT));
    
    // Log the activity
    logActivity(
        $_SESSION['user_id'],
        'reset',
        'Reset system settings to default values',
        'success'
    );
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Settings reset to default values successfully',
        'data' => [
            'settings' => $defaultSettings,
            'backup_file' => basename($backupFile)
        ]
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Error resetting settings: " . $e->getMessage());
    
    // Log the failed activity
    logActivity(
        $_SESSION['user_id'],
        'reset',
        'Failed to reset system settings: ' . $e->getMessage(),
        'failure'
    );
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Helper function to recursively delete a directory
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}
