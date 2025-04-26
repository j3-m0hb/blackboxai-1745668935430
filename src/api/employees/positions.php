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
    // Get distinct positions from database
    $sql = "SELECT DISTINCT jabatan 
            FROM karyawan 
            WHERE deleted_at IS NULL 
            AND jabatan IS NOT NULL 
            ORDER BY 
                CASE jabatan
                    WHEN 'Direksi' THEN 1
                    WHEN 'Dir. Pelaksana' THEN 2
                    WHEN 'Manager' THEN 3
                    WHEN 'HRD' THEN 4
                    ELSE 5
                END,
                jabatan ASC";
    
    $positions = fetchAll($sql);
    
    // Extract position names
    $positionList = array_map(function($row) {
        return $row['jabatan'];
    }, $positions);
    
    // Add position count information
    $sql = "SELECT jabatan, COUNT(*) as count
            FROM karyawan
            WHERE deleted_at IS NULL
            AND jabatan IS NOT NULL
            GROUP BY jabatan";
    
    $counts = fetchAll($sql);
    
    // Create position statistics
    $statistics = [];
    foreach ($counts as $row) {
        $statistics[$row['jabatan']] = [
            'total' => (int)$row['count']
        ];
    }
    
    // Get position distribution by location
    $sql = "SELECT jabatan, wilayah, COUNT(*) as count
            FROM karyawan
            WHERE deleted_at IS NULL
            AND jabatan IS NOT NULL
            GROUP BY jabatan, wilayah";
    
    $distribution = fetchAll($sql);
    
    // Add distribution to statistics
    foreach ($distribution as $row) {
        if (!isset($statistics[$row['jabatan']]['distribution'])) {
            $statistics[$row['jabatan']]['distribution'] = [];
        }
        $statistics[$row['jabatan']]['distribution'][$row['wilayah']] = (int)$row['count'];
    }
    
    // Get position status distribution
    $sql = "SELECT jabatan, status_kerja, COUNT(*) as count
            FROM karyawan
            WHERE deleted_at IS NULL
            AND jabatan IS NOT NULL
            GROUP BY jabatan, status_kerja";
    
    $statusDistribution = fetchAll($sql);
    
    // Add status distribution to statistics
    foreach ($statusDistribution as $row) {
        if (!isset($statistics[$row['jabatan']]['status'])) {
            $statistics[$row['jabatan']]['status'] = [];
        }
        $statistics[$row['jabatan']]['status'][$row['status_kerja']] = (int)$row['count'];
    }
    
    // Prepare response data
    $data = [
        'positions' => $positionList,
        'statistics' => $statistics
    ];
    
    // Add hierarchy information
    $hierarchy = [
        'Direksi' => [],
        'Dir. Pelaksana' => ['Direksi'],
        'Manager' => ['Dir. Pelaksana'],
        'HRD' => ['Manager'],
        'Cashier' => ['Manager'],
        'Admin' => ['Manager'],
        'Accounting' => ['Manager'],
        'IT Support' => ['Manager'],
        'Driver LT' => ['Manager'],
        'Kurir' => ['Manager'],
        'Outbound' => ['Manager'],
        'Inbound' => ['Manager'],
        'Adm. Inbound' => ['Inbound'],
        'SCO' => ['Manager'],
        'Undel' => ['Manager'],
        'Staff' => ['Manager'],
        'Marketing' => ['Manager']
    ];
    
    $data['hierarchy'] = $hierarchy;
    
    // Log the activity
    logActivity(
        $_SESSION['user_id'],
        'view',
        'Mengambil daftar jabatan',
        'success'
    );
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    // Log the error
    error_log("Error in positions list: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
