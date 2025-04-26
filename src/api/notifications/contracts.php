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
    $today = date('Y-m-d');
    $thirtyDaysLater = date('Y-m-d', strtotime('+30 days'));
    
    // Get contracts expiring soon (next 30 days)
    $sql = "SELECT 
                k.id,
                k.nik,
                k.nama_lengkap,
                k.jabatan,
                k.wilayah,
                k.tanggal_kontrak,
                k.tanggal_hbs_kontrak,
                DATEDIFF(k.tanggal_hbs_kontrak, CURDATE()) as days_remaining,
                k.status_kerja,
                k.kinerja
            FROM karyawan k
            WHERE k.status_kerja = 'Kontrak'
            AND k.tanggal_hbs_kontrak BETWEEN ? AND ?
            AND k.deleted_at IS NULL
            ORDER BY k.tanggal_hbs_kontrak ASC";
    
    $expiringContracts = fetchAll($sql, [$today, $thirtyDaysLater]);
    
    // Get expired contracts (past 7 days)
    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
    $sql = "SELECT 
                k.id,
                k.nik,
                k.nama_lengkap,
                k.jabatan,
                k.wilayah,
                k.tanggal_kontrak,
                k.tanggal_hbs_kontrak,
                DATEDIFF(CURDATE(), k.tanggal_hbs_kontrak) as days_expired,
                k.status_kerja,
                k.kinerja
            FROM karyawan k
            WHERE k.status_kerja = 'Kontrak'
            AND k.tanggal_hbs_kontrak BETWEEN ? AND ?
            AND k.deleted_at IS NULL
            ORDER BY k.tanggal_hbs_kontrak DESC";
    
    $expiredContracts = fetchAll($sql, [$sevenDaysAgo, date('Y-m-d', strtotime('-1 day'))]);
    
    // Get contracts requiring immediate attention (expiring in 7 days)
    $sevenDaysLater = date('Y-m-d', strtotime('+7 days'));
    $urgentContracts = array_filter($expiringContracts, function($contract) use ($sevenDaysLater) {
        return $contract['tanggal_hbs_kontrak'] <= $sevenDaysLater;
    });
    
    // Prepare response data
    $data = [
        'urgent' => array_map(function($contract) {
            return [
                'id' => $contract['id'],
                'nik' => $contract['nik'],
                'nama_lengkap' => $contract['nama_lengkap'],
                'jabatan' => $contract['jabatan'],
                'wilayah' => $contract['wilayah'],
                'tanggal_kontrak' => $contract['tanggal_kontrak'],
                'tanggal_hbs_kontrak' => $contract['tanggal_hbs_kontrak'],
                'days_remaining' => (int)$contract['days_remaining'],
                'kinerja' => $contract['kinerja']
            ];
        }, $urgentContracts),
        
        'expiring' => array_map(function($contract) {
            return [
                'id' => $contract['id'],
                'nik' => $contract['nik'],
                'nama_lengkap' => $contract['nama_lengkap'],
                'jabatan' => $contract['jabatan'],
                'wilayah' => $contract['wilayah'],
                'tanggal_kontrak' => $contract['tanggal_kontrak'],
                'tanggal_hbs_kontrak' => $contract['tanggal_hbs_kontrak'],
                'days_remaining' => (int)$contract['days_remaining'],
                'kinerja' => $contract['kinerja']
            ];
        }, $expiringContracts),
        
        'expired' => array_map(function($contract) {
            return [
                'id' => $contract['id'],
                'nik' => $contract['nik'],
                'nama_lengkap' => $contract['nama_lengkap'],
                'jabatan' => $contract['jabatan'],
                'wilayah' => $contract['wilayah'],
                'tanggal_kontrak' => $contract['tanggal_kontrak'],
                'tanggal_hbs_kontrak' => $contract['tanggal_hbs_kontrak'],
                'days_expired' => (int)$contract['days_expired'],
                'kinerja' => $contract['kinerja']
            ];
        }, $expiredContracts)
    ];
    
    // Add summary counts
    $data['counts'] = [
        'urgent' => count($urgentContracts),
        'expiring' => count($expiringContracts),
        'expired' => count($expiredContracts)
    ];
    
    // Get contract statistics by location
    $sql = "SELECT 
                wilayah,
                COUNT(CASE 
                    WHEN tanggal_hbs_kontrak BETWEEN CURDATE() AND ? 
                    THEN 1 
                END) as expiring_count
            FROM karyawan
            WHERE status_kerja = 'Kontrak'
            AND deleted_at IS NULL
            GROUP BY wilayah";
    
    $locationStats = fetchAll($sql, [$thirtyDaysLater]);
    
    $data['location_stats'] = array_map(function($stat) {
        return [
            'wilayah' => $stat['wilayah'],
            'expiring_count' => (int)$stat['expiring_count']
        ];
    }, $locationStats);
    
    // Log activity if there are urgent contracts
    if (count($urgentContracts) > 0) {
        logActivity(
            $_SESSION['user_id'],
            'notification',
            'Melihat notifikasi kontrak (' . count($urgentContracts) . ' kontrak mendesak)',
            'success'
        );
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    // Log the error
    error_log("Error in contract notifications: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
