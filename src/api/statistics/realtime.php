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
    $data = [];
    
    // Get employee counts by status
    $sql = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status_kerja = 'Kontrak' THEN 1 END) as contract,
                COUNT(CASE WHEN status_kerja = 'Kartap' THEN 1 END) as permanent,
                COUNT(CASE WHEN status_kerja = 'Freelance' THEN 1 END) as freelance,
                COUNT(CASE WHEN status_kerja = 'Magang' THEN 1 END) as intern,
                COUNT(CASE WHEN status_kerja = 'PHK' THEN 1 END) as terminated
            FROM karyawan
            WHERE deleted_at IS NULL";
    
    $employeeCounts = fetchOne($sql);
    
    $data['employee_counts'] = [
        'total' => (int)$employeeCounts['total'],
        'contract' => (int)$employeeCounts['contract'],
        'permanent' => (int)$employeeCounts['permanent'],
        'freelance' => (int)$employeeCounts['freelance'],
        'intern' => (int)$employeeCounts['intern'],
        'terminated' => (int)$employeeCounts['terminated']
    ];
    
    // Get today's attendance statistics
    $today = date('Y-m-d');
    $sql = "SELECT 
                COUNT(DISTINCT karyawan_id) as total_present,
                COUNT(CASE WHEN status_hadir = 'masuk' AND TIME(waktu) > '08:00:00' THEN 1 END) as late,
                COUNT(CASE WHEN status_hadir = 'ijin' THEN 1 END) as permission,
                COUNT(CASE WHEN status_hadir = 'sakit' THEN 1 END) as sick,
                COUNT(CASE WHEN status_hadir = 'cuti' THEN 1 END) as leave
            FROM absensi
            WHERE DATE(tanggal) = ?
            AND deleted_at IS NULL";
    
    $todayAttendance = fetchOne($sql, [$today]);
    
    // Calculate absent (total employees - present - permission - sick - leave)
    $absent = $employeeCounts['total'] - 
              $todayAttendance['total_present'] - 
              $todayAttendance['permission'] - 
              $todayAttendance['sick'] - 
              $todayAttendance['leave'];
    
    $data['today_attendance'] = [
        'present' => (int)$todayAttendance['total_present'],
        'late' => (int)$todayAttendance['late'],
        'permission' => (int)$todayAttendance['permission'],
        'sick' => (int)$todayAttendance['sick'],
        'leave' => (int)$todayAttendance['leave'],
        'absent' => max(0, $absent) // Ensure not negative
    ];
    
    // Get active users (logged in within last 30 minutes)
    $thirtyMinutesAgo = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    $sql = "SELECT COUNT(DISTINCT user_id) as active_users
            FROM activity_log
            WHERE created_at >= ?
            AND activity_type IN ('login', 'view')
            AND status = 'success'";
    
    $activeUsers = fetchOne($sql, [$thirtyMinutesAgo]);
    
    $data['active_users'] = (int)$activeUsers['active_users'];
    
    // Get attendance by department/location
    $sql = "SELECT 
                k.wilayah,
                COUNT(DISTINCT a.karyawan_id) as present,
                COUNT(DISTINCT k.id) as total
            FROM karyawan k
            LEFT JOIN absensi a ON k.id = a.karyawan_id 
                AND DATE(a.tanggal) = ?
                AND a.deleted_at IS NULL
            WHERE k.deleted_at IS NULL
            GROUP BY k.wilayah";
    
    $locationAttendance = fetchAll($sql, [$today]);
    
    $data['location_attendance'] = array_map(function($location) {
        return [
            'location' => $location['wilayah'],
            'present' => (int)$location['present'],
            'total' => (int)$location['total'],
            'percentage' => $location['total'] > 0 
                ? round(($location['present'] / $location['total']) * 100, 1)
                : 0
        ];
    }, $locationAttendance);
    
    // Get recent activities
    $sql = "SELECT 
                al.created_at,
                al.activity_type,
                al.description,
                u.username,
                k.wilayah
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN karyawan k ON u.karyawan_id = k.id
            WHERE al.created_at >= ?
            ORDER BY al.created_at DESC
            LIMIT 5";
    
    $recentActivities = fetchAll($sql, [$thirtyMinutesAgo]);
    
    $data['recent_activities'] = array_map(function($activity) {
        return [
            'time' => date('H:i', strtotime($activity['created_at'])),
            'username' => $activity['username'] ?? 'System',
            'location' => $activity['wilayah'] ?? '-',
            'activity' => $activity['description']
        ];
    }, $recentActivities);
    
    // Log the activity
    logActivity($_SESSION['user_id'], 'view', 'Melihat statistik realtime', 'success');
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    // Log the error
    error_log("Error in realtime statistics: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
