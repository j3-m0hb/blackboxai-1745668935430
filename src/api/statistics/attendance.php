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
    // Get the last 6 months
    $months = [];
    $present = [];
    $permission = [];
    $sick = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $date = new DateTime();
        $date->modify("-$i month");
        $month = $date->format('n');
        $year = $date->format('Y');
        
        // Get monthly statistics
        $sql = "SELECT 
                    COUNT(CASE WHEN status_hadir = 'masuk' THEN 1 END) as present_count,
                    COUNT(CASE WHEN status_hadir = 'ijin' THEN 1 END) as permission_count,
                    COUNT(CASE WHEN status_hadir = 'sakit' THEN 1 END) as sick_count
                FROM absensi 
                WHERE MONTH(tanggal) = ? 
                AND YEAR(tanggal) = ?
                AND deleted_at IS NULL";
        
        $result = fetchOne($sql, [$month, $year]);
        
        // Add to arrays
        $months[] = getMonthName($month);
        $present[] = (int)$result['present_count'];
        $permission[] = (int)$result['permission_count'];
        $sick[] = (int)$result['sick_count'];
    }
    
    $data = [
        'labels' => $months,
        'present' => $present,
        'permission' => $permission,
        'sick' => $sick
    ];
    
    // Get attendance by location for floating names
    $sql = "SELECT 
                k.wilayah,
                k.nama_lengkap,
                a.status_hadir,
                a.tanggal,
                a.waktu
            FROM absensi a
            JOIN karyawan k ON a.karyawan_id = k.id
            WHERE DATE(a.tanggal) = CURDATE()
            AND a.deleted_at IS NULL
            ORDER BY a.waktu DESC";
    
    $todayAttendance = fetchAll($sql);
    
    $locationAttendance = [];
    foreach ($todayAttendance as $record) {
        if (!isset($locationAttendance[$record['wilayah']])) {
            $locationAttendance[$record['wilayah']] = [];
        }
        $locationAttendance[$record['wilayah']][] = [
            'nama' => $record['nama_lengkap'],
            'status' => $record['status_hadir'],
            'waktu' => $record['waktu']
        ];
    }
    
    $data['locationAttendance'] = $locationAttendance;
    
    // Log the activity
    logActivity($_SESSION['user_id'], 'view', 'Melihat statistik kehadiran', 'success');
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    // Log the error
    error_log("Error in attendance statistics: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function getLastSixMonths() {
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = new DateTime();
        $date->modify("-$i month");
        $months[] = [
            'month' => $date->format('n'),
            'year' => $date->format('Y'),
            'name' => $date->format('F')
        ];
    }
    return $months;
}
