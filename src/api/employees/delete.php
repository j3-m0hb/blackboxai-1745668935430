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
    // Get employee ID
    $employeeId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if (!$employeeId) {
        throw new Exception('Invalid employee ID');
    }
    
    // Get employee details before deletion
    $sql = "SELECT * FROM karyawan WHERE id = ? AND deleted_at IS NULL";
    $employee = fetchOne($sql, [$employeeId]);
    
    if (!$employee) {
        throw new Exception('Employee not found or already deleted');
    }
    
    // Start transaction
    $pdo = getConnection();
    $pdo->beginTransaction();
    
    try {
        // Soft delete employee
        $sql = "UPDATE karyawan 
                SET deleted_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        execute($sql, [$employeeId]);
        
        // Soft delete related records
        
        // 1. User account
        $sql = "UPDATE users 
                SET deleted_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE karyawan_id = ?";
        execute($sql, [$employeeId]);
        
        // 2. Documents
        $sql = "UPDATE dokumen_karyawan 
                SET deleted_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE karyawan_id = ?";
        execute($sql, [$employeeId]);
        
        // 3. Attendance records
        $sql = "UPDATE absensi 
                SET deleted_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE karyawan_id = ?";
        execute($sql, [$employeeId]);
        
        // 4. Monthly attendance
        $sql = "UPDATE absensi_bulanan 
                SET deleted_at = CURRENT_TIMESTAMP
                WHERE karyawan_id = ?";
        execute($sql, [$employeeId]);
        
        // 5. Salary records
        $sql = "UPDATE pendapatan_gaji 
                SET deleted_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE karyawan_id = ?";
        execute($sql, [$employeeId]);
        
        $sql = "UPDATE potongan_gaji 
                SET deleted_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE karyawan_id = ?";
        execute($sql, [$employeeId]);
        
        // Commit transaction
        $pdo->commit();
        
        // Log the activity
        logActivity(
            $_SESSION['user_id'],
            'delete',
            'Menghapus data pegawai: ' . $employee['nama_lengkap'] . ' (' . $employee['nik'] . ')',
            'success',
            'karyawan',
            $employeeId
        );
        
        // Archive employee files
        $archiveDir = UPLOAD_PATH . '/archive/' . date('Y/m/d') . '/' . $employee['nik'];
        if (!file_exists($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }
        
        // Move employee documents to archive
        $sql = "SELECT file_path FROM dokumen_karyawan WHERE karyawan_id = ?";
        $documents = fetchAll($sql, [$employeeId]);
        
        foreach ($documents as $doc) {
            $sourcePath = $doc['file_path'];
            if (file_exists($sourcePath)) {
                $fileName = basename($sourcePath);
                $targetPath = $archiveDir . '/' . $fileName;
                copy($sourcePath, $targetPath);
            }
        }
        
        // Create deletion record
        $deletionRecord = [
            'employee_id' => $employeeId,
            'nik' => $employee['nik'],
            'nama_lengkap' => $employee['nama_lengkap'],
            'jabatan' => $employee['jabatan'],
            'wilayah' => $employee['wilayah'],
            'deleted_by' => $_SESSION['user_id'],
            'deleted_at' => date('Y-m-d H:i:s'),
            'reason' => $_POST['reason'] ?? 'No reason provided'
        ];
        
        $deletionFile = UPLOAD_PATH . '/archive/deletions.json';
        $deletions = file_exists($deletionFile) ? 
                    json_decode(file_get_contents($deletionFile), true) : [];
        $deletions[] = $deletionRecord;
        file_put_contents($deletionFile, json_encode($deletions, JSON_PRETTY_PRINT));
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Data pegawai berhasil dihapus',
            'data' => [
                'id' => $employeeId,
                'nik' => $employee['nik'],
                'nama_lengkap' => $employee['nama_lengkap']
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in employee deletion: " . $e->getMessage());
    
    // Log the failed activity
    if (isset($employee)) {
        logActivity(
            $_SESSION['user_id'],
            'delete',
            'Gagal menghapus data pegawai: ' . $employee['nama_lengkap'] . ' (' . $employee['nik'] . ')',
            'failure',
            'karyawan',
            $employeeId
        );
    }
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghapus data pegawai',
        'error' => $e->getMessage()
    ]);
}
