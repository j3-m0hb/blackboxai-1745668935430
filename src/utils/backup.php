<?php
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

/**
 * Create database backup
 */
function createBackup() {
    try {
        // Create backup directory if not exists
        $backupDir = __DIR__ . '/../backup/' . date('Y/m');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Generate backup filename
        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $filepath = $backupDir . '/' . $filename;
        
        // Get database credentials
        $host = DB_HOST;
        $dbname = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;
        
        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s',
            escapeshellarg($host),
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($dbname),
            escapeshellarg($filepath)
        );
        
        // Execute backup command
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception('Database backup failed');
        }
        
        // Create backup record
        $backupRecord = [
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['user_id']
        ];
        
        // Save backup record
        $recordFile = __DIR__ . '/../backup/backup_records.json';
        $records = file_exists($recordFile) ? 
                  json_decode(file_get_contents($recordFile), true) : [];
        $records[] = $backupRecord;
        file_put_contents($recordFile, json_encode($records, JSON_PRETTY_PRINT));
        
        // Compress backup file
        $zip = new ZipArchive();
        $zipPath = $filepath . '.zip';
        
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $zip->addFile($filepath, $filename);
            $zip->close();
            
            // Remove original SQL file
            unlink($filepath);
            
            // Update backup record
            $backupRecord['filename'] .= '.zip';
            $backupRecord['filepath'] = $zipPath;
            $backupRecord['size'] = filesize($zipPath);
            array_pop($records);
            $records[] = $backupRecord;
            file_put_contents($recordFile, json_encode($records, JSON_PRETTY_PRINT));
        }
        
        // Log the activity
        logActivity(
            $_SESSION['user_id'],
            'backup',
            'Created database backup: ' . $filename,
            'success'
        );
        
        // Clean old backups (keep last 10)
        cleanOldBackups();
        
        return [
            'success' => true,
            'message' => 'Database backup created successfully',
            'data' => $backupRecord
        ];
        
    } catch (Exception $e) {
        // Log the error
        error_log("Error in database backup: " . $e->getMessage());
        
        // Log the failed activity
        logActivity(
            $_SESSION['user_id'],
            'backup',
            'Failed to create database backup: ' . $e->getMessage(),
            'failure'
        );
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Restore database from backup
 */
function restoreBackup($backupFile) {
    try {
        // Validate backup file
        if (!file_exists($backupFile)) {
            throw new Exception('Backup file not found');
        }
        
        // Extract if it's a zip file
        if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($backupFile) === true) {
                $extractPath = sys_get_temp_dir() . '/' . uniqid('backup_');
                $zip->extractTo($extractPath);
                $zip->close();
                
                // Get SQL file
                $files = glob($extractPath . '/*.sql');
                if (empty($files)) {
                    throw new Exception('No SQL file found in backup');
                }
                $backupFile = $files[0];
            } else {
                throw new Exception('Failed to open backup archive');
            }
        }
        
        // Get database credentials
        $host = DB_HOST;
        $dbname = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;
        
        // Build mysql restore command
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            escapeshellarg($host),
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($dbname),
            escapeshellarg($backupFile)
        );
        
        // Execute restore command
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception('Database restore failed');
        }
        
        // Clean up extracted files
        if (isset($extractPath)) {
            array_map('unlink', glob($extractPath . '/*'));
            rmdir($extractPath);
        }
        
        // Log the activity
        logActivity(
            $_SESSION['user_id'],
            'restore',
            'Restored database from backup: ' . basename($backupFile),
            'success'
        );
        
        return [
            'success' => true,
            'message' => 'Database restored successfully'
        ];
        
    } catch (Exception $e) {
        // Log the error
        error_log("Error in database restore: " . $e->getMessage());
        
        // Log the failed activity
        logActivity(
            $_SESSION['user_id'],
            'restore',
            'Failed to restore database: ' . $e->getMessage(),
            'failure'
        );
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Clean old backups (keep last 10)
 */
function cleanOldBackups() {
    $recordFile = __DIR__ . '/../backup/backup_records.json';
    if (!file_exists($recordFile)) {
        return;
    }
    
    $records = json_decode(file_get_contents($recordFile), true);
    if (count($records) <= 10) {
        return;
    }
    
    // Sort by creation date
    usort($records, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Keep only last 10 backups
    $toDelete = array_slice($records, 10);
    $toKeep = array_slice($records, 0, 10);
    
    // Delete old backup files
    foreach ($toDelete as $backup) {
        if (file_exists($backup['filepath'])) {
            unlink($backup['filepath']);
        }
    }
    
    // Update records file
    file_put_contents($recordFile, json_encode($toKeep, JSON_PRETTY_PRINT));
}

// Handle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'backup':
            $result = createBackup();
            break;
            
        case 'restore':
            if (!isset($_POST['file'])) {
                $result = ['success' => false, 'message' => 'No backup file specified'];
                break;
            }
            $result = restoreBackup($_POST['file']);
            break;
            
        default:
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
}
