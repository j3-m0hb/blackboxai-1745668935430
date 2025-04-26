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
    $employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
    
    if (!$employeeId) {
        throw new Exception('Invalid employee ID');
    }
    
    // Get DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Base query
    $baseQuery = "FROM dokumen_karyawan 
                  WHERE karyawan_id = ? 
                  AND deleted_at IS NULL";
    
    $params = [$employeeId];
    
    // Add search
    if ($search) {
        $baseQuery .= " AND (nama_dokumen LIKE ? OR jenis_dokumen LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Get total count
    $sql = "SELECT COUNT(*) as total " . $baseQuery;
    $result = fetchOne($sql, $params);
    $recordsTotal = $result['total'];
    $recordsFiltered = $recordsTotal;
    
    // Get data
    $sql = "SELECT * " . $baseQuery;
    
    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 2;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = [
        'nama_dokumen',
        'jenis_dokumen',
        'created_at'
    ];
    
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
    }
    
    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $length;
    $params[] = $start;
    
    $data = fetchAll($sql, $params);
    
    // Check file existence and add file info
    foreach ($data as &$doc) {
        $filePath = '../../' . $doc['file_path'];
        if (file_exists($filePath)) {
            $doc['file_size'] = filesize($filePath);
            $doc['file_exists'] = true;
            
            // Get file extension
            $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
            $doc['file_type'] = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : 'document';
        } else {
            $doc['file_exists'] = false;
        }
    }
    
    // Log the activity
    logActivity(
        $_SESSION['user_id'],
        'view',
        'Melihat dokumen pegawai',
        'success',
        'dokumen_karyawan',
        $employeeId
    );
    
    // Return JSON response
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Error in employee documents: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
