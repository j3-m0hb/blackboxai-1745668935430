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
    $today = date('m-d'); // Format: MM-DD
    
    // Get employees with birthdays today
    $sql = "SELECT 
                k.id,
                k.nama_lengkap,
                k.jabatan,
                k.wilayah,
                k.tanggal_lahir,
                YEAR(CURDATE()) - YEAR(k.tanggal_lahir) as age
            FROM karyawan k
            WHERE DATE_FORMAT(k.tanggal_lahir, '%m-%d') = ?
            AND k.deleted_at IS NULL
            AND k.status_kerja NOT IN ('PHK', 'MD')
            ORDER BY k.nama_lengkap";
    
    $birthdays = fetchAll($sql, [$today]);
    
    // Get upcoming birthdays (next 7 days)
    $sql = "SELECT 
                k.id,
                k.nama_lengkap,
                k.jabatan,
                k.wilayah,
                k.tanggal_lahir,
                YEAR(CURDATE()) - YEAR(k.tanggal_lahir) as age,
                DATEDIFF(
                    DATE_FORMAT(CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(k.tanggal_lahir, '%m-%d')), '%Y-%m-%d'),
                    CURDATE()
                ) as days_until
            FROM karyawan k
            WHERE DATE_FORMAT(k.tanggal_lahir, '%m-%d') 
                BETWEEN DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '%m-%d')
                AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')
            AND k.deleted_at IS NULL
            AND k.status_kerja NOT IN ('PHK', 'MD')
            ORDER BY days_until, k.nama_lengkap";
    
    $upcomingBirthdays = fetchAll($sql);
    
    $data = [
        'today' => array_map(function($employee) {
            return [
                'id' => $employee['id'],
                'nama_lengkap' => $employee['nama_lengkap'],
                'jabatan' => $employee['jabatan'],
                'wilayah' => $employee['wilayah'],
                'tanggal_lahir' => $employee['tanggal_lahir'],
                'age' => (int)$employee['age']
            ];
        }, $birthdays),
        'upcoming' => array_map(function($employee) {
            return [
                'id' => $employee['id'],
                'nama_lengkap' => $employee['nama_lengkap'],
                'jabatan' => $employee['jabatan'],
                'wilayah' => $employee['wilayah'],
                'tanggal_lahir' => $employee['tanggal_lahir'],
                'age' => (int)$employee['age'],
                'days_until' => (int)$employee['days_until']
            ];
        }, $upcomingBirthdays)
    ];
    
    // Add total counts
    $data['counts'] = [
        'today' => count($birthdays),
        'upcoming' => count($upcomingBirthdays)
    ];
    
    // Log the activity only if there are birthdays today
    if (count($birthdays) > 0) {
        logActivity(
            $_SESSION['user_id'], 
            'notification', 
            'Melihat notifikasi ulang tahun (' . count($birthdays) . ' karyawan)', 
            'success'
        );
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    // Log the error
    error_log("Error in birthday notifications: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
