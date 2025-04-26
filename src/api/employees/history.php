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
    
    // Get employee history from activity log
    $sql = "SELECT al.*,
                   u.username,
                   k.nama_lengkap as user_name
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN karyawan k ON u.karyawan_id = k.id
            WHERE (al.table_name = 'karyawan' AND al.record_id = ?)
               OR (al.table_name = 'dokumen_karyawan' AND al.record_id IN 
                   (SELECT id FROM dokumen_karyawan WHERE karyawan_id = ?))
               OR (al.table_name = 'absensi' AND al.record_id IN 
                   (SELECT id FROM absensi WHERE karyawan_id = ?))
            ORDER BY al.created_at DESC
            LIMIT 100";
    
    $history = fetchAll($sql, [$employeeId, $employeeId, $employeeId]);
    
    // Process and format history items
    $formattedHistory = array_map(function($item) {
        // Format activity description
        $description = $item['description'];
        
        // Add user info
        $userInfo = $item['user_name'] ?? $item['username'] ?? 'System';
        
        // Format based on activity type
        switch ($item['activity_type']) {
            case 'create':
                if ($item['table_name'] === 'karyawan') {
                    $description = "Data pegawai dibuat oleh $userInfo";
                } elseif ($item['table_name'] === 'dokumen_karyawan') {
                    $description = "Dokumen baru diupload oleh $userInfo";
                } elseif ($item['table_name'] === 'absensi') {
                    $description = "Absensi dicatat oleh $userInfo";
                }
                break;
                
            case 'update':
                // Extract changed fields from description if available
                if (preg_match('/Changed: (.+)/', $description, $matches)) {
                    $changes = $matches[1];
                    $description = "Data diubah oleh $userInfo: $changes";
                } else {
                    $description = "Data diubah oleh $userInfo";
                }
                break;
                
            case 'delete':
                $description = "Data dihapus oleh $userInfo";
                break;
                
            case 'upload':
                $description = "Dokumen diupload oleh $userInfo";
                break;
                
            case 'download':
                $description = "Dokumen didownload oleh $userInfo";
                break;
        }
        
        // Add status info if available
        if ($item['status'] && $item['status'] !== 'success') {
            $description .= " (Status: " . ucfirst($item['status']) . ")";
        }
        
        return [
            'id' => $item['id'],
            'activity_type' => $item['activity_type'],
            'description' => $description,
            'created_at' => $item['created_at'],
            'status' => $item['status'],
            'user' => [
                'username' => $item['username'],
                'name' => $item['user_name']
            ],
            'metadata' => [
                'table' => $item['table_name'],
                'record_id' => $item['record_id'],
                'ip_address' => $item['ip_address'],
                'user_agent' => $item['user_agent']
            ]
        ];
    }, $history);
    
    // Group history by date
    $groupedHistory = [];
    foreach ($formattedHistory as $item) {
        $date = date('Y-m-d', strtotime($item['created_at']));
        if (!isset($groupedHistory[$date])) {
            $groupedHistory[$date] = [];
        }
        $groupedHistory[$date][] = $item;
    }
    
    // Format final response
    $response = [];
    foreach ($groupedHistory as $date => $items) {
        $response[] = [
            'date' => $date,
            'formatted_date' => date('d F Y', strtotime($date)),
            'items' => $items
        ];
    }
    
    // Log the activity
    logActivity(
        $_SESSION['user_id'],
        'view',
        'Melihat riwayat pegawai',
        'success',
        'karyawan',
        $employeeId
    );
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    // Log the error
    error_log("Error in employee history: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
