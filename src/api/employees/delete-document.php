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

// Check if user has permission
if (!isAdmin() && !isHRD()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

try {
    // Get document ID
    $docId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if (!$docId) {
        throw new Exception('Invalid document ID');
    }
    
    // Get document info before deletion
    $sql = "SELECT d.*, k.nama_lengkap, k.nik 
            FROM dokumen_karyawan d
            JOIN karyawan k ON d.karyawan_id = k.id
            WHERE d.id = ? AND d.deleted_at IS NULL";
    
    $document = fetchOne($sql, [$docId]);
    
    if (!$document) {
        throw new Exception('Document not found or already deleted');
    }
    
    // Start transaction
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    try {
        // Soft delete the document
        $sql = "UPDATE dokumen_karyawan 
                SET deleted_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        execute($sql, [$docId]);
        
        // Move file to archive if it exists
        $sourcePath = '../../' . $document['file_path'];
        if (file_exists($sourcePath)) {
            // Create archive directory
            $archiveDir = '../../uploads/archive/' . date('Y/m/d') . '/' . $document['nik'];
            if (!file_exists($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }
            
            // Move file to archive
            $fileName = basename($document['file_path']);
            $archivePath = $archiveDir . '/' . $fileName;
            
            if (!rename($sourcePath, $archivePath)) {
                // If move fails, just copy
                copy($sourcePath, $archivePath);
                unlink($sourcePath);
            }
        }
        
        // Log the activity
        logActivity(
            $_SESSION['user_id'],
            'delete',
            'Menghapus dokumen: ' . $document['nama_dokumen'] . ' (' . $document['nama_lengkap'] . ')',
            'success',
            'dokumen_karyawan',
            $docId
        );
        
        // Create deletion record
        $deletionRecord = [
            'document_id' => $docId,
            'document_name' => $document['nama_dokumen'],
            'document_type' => $document['jenis_dokumen'],
            'employee_nik' => $document['nik'],
            'employee_name' => $document['nama_lengkap'],
            'deleted_by' => $_SESSION['user_id'],
            'deleted_at' => date('Y-m-d H:i:s'),
            'reason' => $_POST['reason'] ?? 'No reason provided'
        ];
        
        $deletionFile = '../../uploads/archive/document_deletions.json';
        $deletions = file_exists($deletionFile) ? 
                    json_decode(file_get_contents($deletionFile), true) : [];
        $deletions[] = $deletionRecord;
        file_put_contents($deletionFile, json_encode($deletions, JSON_PRETTY_PRINT));
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Dokumen berhasil dihapus',
            'data' => [
                'id' => $docId,
                'name' => $document['nama_dokumen']
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in document deletion: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
