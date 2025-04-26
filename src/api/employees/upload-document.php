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
    // Validate request
    if (!isset($_POST['employee_id']) || !isset($_FILES['file'])) {
        throw new Exception('Invalid request parameters');
    }
    
    $employeeId = intval($_POST['employee_id']);
    $file = $_FILES['file'];
    $jenisDoc = $_POST['jenis_dokumen'] ?? 'Lainnya';
    
    // Validate employee
    $sql = "SELECT nik, nama_lengkap FROM karyawan WHERE id = ? AND deleted_at IS NULL";
    $employee = fetchOne($sql, [$employeeId]);
    
    if (!$employee) {
        throw new Exception('Employee not found');
    }
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed with error code: ' . $file['error']);
    }
    
    if ($file['size'] > 5242880) { // 5MB
        throw new Exception('File size too large. Maximum size is 5MB');
    }
    
    // Check file type
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png'
    ];
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('File type not allowed');
    }
    
    // Generate safe filename
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeName = $employee['nik'] . '_' . 
                strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $jenisDoc)) . '_' .
                date('Ymd_His') . '.' . $ext;
    
    // Create upload directory if not exists
    $uploadDir = '../../uploads/documents/' . $employee['nik'];
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filePath = $uploadDir . '/' . $safeName;
    $dbPath = 'uploads/documents/' . $employee['nik'] . '/' . $safeName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Start transaction
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    try {
        // Save document record
        $sql = "INSERT INTO dokumen_karyawan (
                    karyawan_id, nama_dokumen, jenis_dokumen, file_path
                ) VALUES (?, ?, ?, ?)";
        
        $docId = insert($sql, [
            $employeeId,
            $file['name'],
            $jenisDoc,
            $dbPath
        ]);
        
        // Log the activity
        logActivity(
            $_SESSION['user_id'],
            'upload',
            'Upload dokumen: ' . $file['name'] . ' untuk ' . $employee['nama_lengkap'],
            'success',
            'dokumen_karyawan',
            $docId
        );
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Dokumen berhasil diupload',
            'data' => [
                'id' => $docId,
                'name' => $file['name'],
                'type' => $jenisDoc,
                'path' => $dbPath
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        
        // Delete uploaded file
        @unlink($filePath);
        
        throw $e;
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in document upload: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
