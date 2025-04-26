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
    // Get courier counts by location
    $locations = [
        'Kantor Utama',
        'KP Patrol',
        'KP Cibereng',
        'KP Eretan',
        'KP Widasari',
        'KP Karangampel'
    ];
    
    $data = [
        'labels' => [],
        'values' => []
    ];
    
    foreach ($locations as $location) {
        $sql = "SELECT COUNT(*) as count 
                FROM karyawan 
                WHERE wilayah = ? 
                AND jabatan = 'Kurir' 
                AND deleted_at IS NULL";
        
        $result = fetchOne($sql, [$location]);
        
        $data['labels'][] = $location;
        $data['values'][] = (int)$result['count'];
    }
    
    // Log the activity
    logActivity($_SESSION['user_id'], 'view', 'Melihat statistik kurir', 'success');
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    // Log the error
    error_log("Error in courier statistics: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
