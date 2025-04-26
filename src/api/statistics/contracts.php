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
    
    // Get contract statistics
    $sql = "SELECT 
                COUNT(CASE 
                    WHEN status_kerja = 'Kontrak' 
                    AND tanggal_hbs_kontrak > ? 
                    THEN 1 
                END) as active,
                COUNT(CASE 
                    WHEN status_kerja = 'Kontrak' 
                    AND tanggal_hbs_kontrak BETWEEN ? AND ? 
                    THEN 1 
                END) as expiring,
                COUNT(CASE 
                    WHEN status_kerja = 'Kontrak' 
                    AND tanggal_hbs_kontrak < ? 
                    THEN 1 
                END) as expired
            FROM karyawan
            WHERE deleted_at IS NULL";
    
    $result = fetchOne($sql, [$thirtyDaysLater, $today, $thirtyDaysLater, $today]);
    
    // Get detailed contract information
    $sql = "SELECT 
                k.id,
                k.nik,
                k.nama_lengkap,
                k.jabatan,
                k.wilayah,
                k.tanggal_kontrak,
                k.tanggal_hbs_kontrak,
                DATEDIFF(k.tanggal_hbs_kontrak, CURDATE()) as days_remaining
            FROM karyawan k
            WHERE k.status_kerja = 'Kontrak'
            AND k.tanggal_hbs_kontrak BETWEEN ? AND ?
            AND k.deleted_at IS NULL
            ORDER BY k.tanggal_hbs_kontrak ASC";
    
    $expiringContracts = fetchAll($sql, [$today, $thirtyDaysLater]);
    
    $data = [
        'summary' => [
            'active' => (int)$result['active'],
            'expiring' => (int)$result['expiring'],
            'expired' => (int)$result['expired']
        ],
        'expiring_contracts' => array_map(function($contract) {
            return [
                'id' => $contract['id'],
                'nik' => $contract['nik'],
                'nama_lengkap' => $contract['nama_lengkap'],
                'jabatan' => $contract['jabatan'],
                'wilayah' => $contract['wilayah'],
                'tanggal_kontrak' => $contract['tanggal_kontrak'],
                'tanggal_hbs_kontrak' => $contract['tanggal_hbs_kontrak'],
                'days_remaining' => (int)$contract['days_remaining']
            ];
        }, $expiringContracts)
    ];
    
    // Get contract status by location
    $sql = "SELECT 
                wilayah,
                COUNT(CASE WHEN status_kerja = 'Kontrak' THEN 1 END) as kontrak,
                COUNT(CASE WHEN status_kerja = 'Kartap' THEN 1 END) as tetap,
                COUNT(CASE WHEN status_kerja = 'Freelance' THEN 1 END) as freelance
            FROM karyawan
            WHERE deleted_at IS NULL
            GROUP BY wilayah";
    
    $locationStats = fetchAll($sql);
    
    $data['location_stats'] = $locationStats;
    
    // Get contract renewal history
    $sql = "SELECT 
                k.nama_lengkap,
                k.wilayah,
                k.tanggal_kontrak as renewed_date,
                k.tanggal_hbs_kontrak as expiry_date
            FROM karyawan k
            WHERE k.status_kerja = 'Kontrak'
            AND k.deleted_at IS NULL
            ORDER BY k.tanggal_kontrak DESC
            LIMIT 5";
    
    $renewalHistory = fetchAll($sql);
    
    $data['renewal_history'] = $renewalHistory;
    
    // Log the activity
    logActivity($_SESSION['user_id'], 'view', 'Melihat statistik kontrak', 'success');
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    // Log the error
    error_log("Error in contract statistics: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
