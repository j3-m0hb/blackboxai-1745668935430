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
    // Get current settings
    $settingsFile = '../../config/settings.json';
    $currentSettings = file_exists($settingsFile) ? 
                      json_decode(file_get_contents($settingsFile), true) : [];
    
    // Process logo upload if provided
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logo = $_FILES['logo'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($logo['type'], $allowedTypes)) {
            throw new Exception('Invalid logo file type');
        }
        
        // Validate file size (max 2MB)
        if ($logo['size'] > 2097152) {
            throw new Exception('Logo file size must be less than 2MB');
        }
        
        // Generate safe filename
        $ext = strtolower(pathinfo($logo['name'], PATHINFO_EXTENSION));
        $filename = 'company_logo_' . date('YmdHis') . '.' . $ext;
        
        // Create uploads directory if not exists
        $uploadDir = '../../uploads/system';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        $logoPath = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($logo['tmp_name'], $logoPath)) {
            throw new Exception('Failed to save logo file');
        }
        
        // Delete old logo if exists
        if (!empty($currentSettings['logo']) && file_exists('../../' . $currentSettings['logo'])) {
            unlink('../../' . $currentSettings['logo']);
        }
        
        $_POST['logo'] = 'uploads/system/' . $filename;
    }
    
    // Process settings
    $settings = [
        // Company Settings
        'company_name' => $_POST['company_name'] ?? '',
        'legal_name' => $_POST['legal_name'] ?? '',
        'address' => $_POST['address'] ?? '',
        'contact_info' => $_POST['contact_info'] ?? '',
        'tax_id' => $_POST['tax_id'] ?? '',
        'logo' => $_POST['logo'] ?? $currentSettings['logo'] ?? '',
        
        // System Settings
        'default_language' => $_POST['default_language'] ?? 'id',
        'timezone' => $_POST['timezone'] ?? 'Asia/Jakarta',
        'date_format' => $_POST['date_format'] ?? 'd/m/Y',
        'items_per_page' => intval($_POST['items_per_page'] ?? 25),
        'session_timeout' => intval($_POST['session_timeout'] ?? 60),
        'maintenance_mode' => !empty($_POST['maintenance_mode']),
        
        // Email Settings
        'smtp_host' => $_POST['smtp_host'] ?? '',
        'smtp_port' => intval($_POST['smtp_port'] ?? 587),
        'smtp_username' => $_POST['smtp_username'] ?? '',
        'smtp_password' => $_POST['smtp_password'] ?? $currentSettings['smtp_password'] ?? '',
        'from_email' => $_POST['from_email'] ?? '',
        'from_name' => $_POST['from_name'] ?? '',
        
        // Security Settings
        'min_password_length' => intval($_POST['min_password_length'] ?? 8),
        'require_uppercase' => !empty($_POST['require_uppercase']),
        'require_numbers' => !empty($_POST['require_numbers']),
        'require_symbols' => !empty($_POST['require_symbols']),
        'max_login_attempts' => intval($_POST['max_login_attempts'] ?? 5),
        'lockout_duration' => intval($_POST['lockout_duration'] ?? 30),
        'password_expiry' => intval($_POST['password_expiry'] ?? 90)
    ];
    
    // Validate settings
    if (empty($settings['company_name'])) {
        throw new Exception('Company name is required');
    }
    
    if ($settings['min_password_length'] < 6 || $settings['min_password_length'] > 32) {
        throw new Exception('Invalid minimum password length');
    }
    
    if ($settings['max_login_attempts'] < 3 || $settings['max_login_attempts'] > 10) {
        throw new Exception('Invalid maximum login attempts');
    }
    
    // Save settings
    if (!file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save settings');
    }
    
    // Update PHP settings that can be changed at runtime
    date_default_timezone_set($settings['timezone']);
    ini_set('session.gc_maxlifetime', $settings['session_timeout'] * 60);
    
    // Log the activity
    logActivity(
        $_SESSION['user_id'],
        'update',
        'Updated system settings',
        'success'
    );
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully',
        'data' => $settings
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Error saving settings: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
