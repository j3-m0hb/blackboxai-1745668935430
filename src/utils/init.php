<?php
/**
 * System Initialization Script
 * 
 * This script performs initial setup tasks for the application:
 * - Creates necessary directories
 * - Sets up configuration files
 * - Sets proper permissions
 * - Validates system requirements
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base paths
define('BASE_PATH', realpath(__DIR__ . '/..'));
define('CONFIG_PATH', BASE_PATH . '/config');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('BACKUP_PATH', BASE_PATH . '/backup');
define('LOG_PATH', BASE_PATH . '/logs');
define('TEMP_PATH', BASE_PATH . '/temp');

// Required directories with permissions
$directories = [
    [UPLOAD_PATH, 0755],
    [UPLOAD_PATH . '/documents', 0755],
    [UPLOAD_PATH . '/photos', 0755],
    [UPLOAD_PATH . '/system', 0755],
    [BACKUP_PATH, 0755],
    [BACKUP_PATH . '/database', 0755],
    [BACKUP_PATH . '/settings', 0755],
    [LOG_PATH, 0755],
    [TEMP_PATH, 0755]
];

// Required configuration files
$configFiles = [
    [
        'source' => CONFIG_PATH . '/settings.default.json',
        'destination' => CONFIG_PATH . '/settings.json',
        'required' => true
    ]
];

// System requirements
$requirements = [
    'php' => '7.4.0',
    'extensions' => [
        'pdo',
        'pdo_mysql',
        'mbstring',
        'json',
        'gd',
        'zip'
    ],
    'functions' => [
        'exec',
        'shell_exec'
    ],
    'writable_paths' => [
        UPLOAD_PATH,
        BACKUP_PATH,
        LOG_PATH,
        TEMP_PATH,
        CONFIG_PATH
    ]
];

/**
 * Main initialization function
 */
function initializeSystem() {
    echo "Starting system initialization...\n\n";
    
    try {
        // Check system requirements
        checkRequirements();
        
        // Create directories
        createDirectories();
        
        // Set up configuration files
        setupConfigFiles();
        
        // Create success file
        touch(BASE_PATH . '/.initialized');
        
        echo "\nSystem initialization completed successfully!\n";
        
    } catch (Exception $e) {
        die("\nError: " . $e->getMessage() . "\n");
    }
}

/**
 * Check system requirements
 */
function checkRequirements() {
    global $requirements;
    
    echo "Checking system requirements...\n";
    
    // Check PHP version
    if (version_compare(PHP_VERSION, $requirements['php'], '<')) {
        throw new Exception("PHP version {$requirements['php']} or higher is required");
    }
    
    // Check required extensions
    foreach ($requirements['extensions'] as $ext) {
        if (!extension_loaded($ext)) {
            throw new Exception("Required PHP extension missing: $ext");
        }
    }
    
    // Check required functions
    foreach ($requirements['functions'] as $func) {
        if (!function_exists($func)) {
            throw new Exception("Required PHP function not available: $func");
        }
    }
    
    echo "System requirements met.\n";
}

/**
 * Create required directories
 */
function createDirectories() {
    global $directories;
    
    echo "Creating required directories...\n";
    
    foreach ($directories as [$path, $perms]) {
        if (!file_exists($path)) {
            if (!mkdir($path, $perms, true)) {
                throw new Exception("Failed to create directory: $path");
            }
            chmod($path, $perms);
        }
        
        if (!is_writable($path)) {
            throw new Exception("Directory not writable: $path");
        }
        
        echo "Created directory: $path\n";
    }
}

/**
 * Set up configuration files
 */
function setupConfigFiles() {
    global $configFiles;
    
    echo "Setting up configuration files...\n";
    
    foreach ($configFiles as $file) {
        if (!file_exists($file['destination'])) {
            if (!file_exists($file['source'])) {
                if ($file['required']) {
                    throw new Exception("Required source file missing: {$file['source']}");
                }
                continue;
            }
            
            if (!copy($file['source'], $file['destination'])) {
                throw new Exception("Failed to copy file: {$file['source']} to {$file['destination']}");
            }
            
            echo "Created config file: {$file['destination']}\n";
        }
    }
}

/**
 * Check if system is already initialized
 */
function isInitialized() {
    return file_exists(BASE_PATH . '/.initialized');
}

// Run initialization if not already done
if (!isInitialized()) {
    initializeSystem();
} else {
    echo "System is already initialized.\n";
    echo "To reinitialize, delete the .initialized file and run this script again.\n";
}
