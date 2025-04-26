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

try {
    // Get employee ID
    $employeeId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$employeeId) {
        throw new Exception('Invalid employee ID');
    }
    
    // Get current contract details
    $sql = "SELECT k.*,
                   dp.tempat,
                   dp.tanggal_lahir as dp_tanggal_lahir,
                   dp.alamat as dp_alamat,
                   dp.pend_terakhir,
                   dp.status_person,
                   dp.email
            FROM karyawan k
            LEFT JOIN data_personal dp ON k.nama_lengkap = dp.nama_lengkap
            WHERE k.id = ? AND k.deleted_at IS NULL";
    
    $employee = fetchOne($sql, [$employeeId]);
    
    if (!$employee) {
        throw new Exception('Employee not found');
    }
    
    // Get contract history from activity log
    $sql = "SELECT al.*
            FROM activity_log al
            WHERE al.table_name = 'karyawan'
            AND al.record_id = ?
            AND al.activity_type IN ('create', 'update')
            AND al.description LIKE '%kontrak%'
            ORDER BY al.created_at DESC";
    
    $contractLogs = fetchAll($sql, [$employeeId]);
    
    // Process contract history
    $contractHistory = [];
    foreach ($contractLogs as $log) {
        // Extract dates from log description using regex
        if (preg_match('/(\d{4}-\d{2}-\d{2})\s*-\s*(\d{4}-\d{2}-\d{2})/', $log['description'], $matches)) {
            $startDate = $matches[1];
            $endDate = $matches[2];
            
            // Calculate duration
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $interval = $start->diff($end);
            
            $duration = '';
            if ($interval->y > 0) $duration .= $interval->y . ' tahun ';
            if ($interval->m > 0) $duration .= $interval->m . ' bulan';
            if ($duration === '') $duration = 'Kurang dari 1 bulan';
            
            $contractHistory[] = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration' => $duration,
                'status' => strtotime($endDate) < time() ? 'Selesai' : 'Aktif'
            ];
        }
    }
    
    // Calculate remaining time
    $now = new DateTime();
    $endDate = new DateTime($employee['tanggal_hbs_kontrak']);
    $daysRemaining = $now->diff($endDate)->days;
    
    // Get performance history
    $sql = "SELECT al.*
            FROM activity_log al
            WHERE al.table_name = 'karyawan'
            AND al.record_id = ?
            AND al.activity_type = 'update'
            AND al.description LIKE '%kinerja%'
            ORDER BY al.created_at DESC
            LIMIT 5";
    
    $performanceLogs = fetchAll($sql, [$employeeId]);
    
    // Prepare response data
    $data = [
        'id' => $employee['id'],
        'nik' => $employee['nik'],
        'nama_lengkap' => $employee['nama_lengkap'],
        'jabatan' => $employee['jabatan'],
        'wilayah' => $employee['wilayah'],
        'status_kerja' => $employee['status_kerja'],
        'tanggal_kontrak' => $employee['tanggal_kontrak'],
        'tanggal_hbs_kontrak' => $employee['tanggal_hbs_kontrak'],
        'days_remaining' => $daysRemaining,
        'kinerja' => $employee['kinerja'],
        'tindakan' => $employee['tindakan'],
        'cat_tindakan' => $employee['cat_tindakan'],
        'history' => $contractHistory,
        'performance_history' => array_map(function($log) {
            return [
                'date' => $log['created_at'],
                'description' => $log['description'],
                'status' => $log['status']
            ];
        }, $performanceLogs)
    ];
    
    // Add warning flags
    $data['warnings'] = [
        'contract_expiring' => $daysRemaining <= 30,
        'performance_issues' => in_array($employee['kinerja'], ['Teguran 1', 'Teguran 2', 'Teguran 3']),
        'documentation_incomplete' => checkIncompleteDocumentation($employeeId)
    ];
    
    // Log the activity
    logActivity(
        $_SESSION['user_id'],
        'view',
        'Melihat detail kontrak pegawai: ' . $employee['nama_lengkap'],
        'success'
    );
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    // Log the error
    error_log("Error in contract details: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Check if employee has incomplete documentation
 */
function checkIncompleteDocumentation($employeeId) {
    $sql = "SELECT COUNT(*) as doc_count
            FROM dokumen_karyawan
            WHERE karyawan_id = ?
            AND deleted_at IS NULL";
    
    $result = fetchOne($sql, [$employeeId]);
    
    // Consider documentation incomplete if less than 3 documents
    return $result['doc_count'] < 3;
}
