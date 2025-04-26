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
    // Get DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Get filters
    $wilayah = isset($_POST['wilayah']) ? $_POST['wilayah'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $jabatan = isset($_POST['jabatan']) ? $_POST['jabatan'] : '';
    
    // Base query
    $baseQuery = "FROM karyawan k 
                  LEFT JOIN data_personal dp ON k.nama_lengkap = dp.nama_lengkap
                  WHERE k.deleted_at IS NULL";
    
    // Add filters
    $params = [];
    
    if ($wilayah) {
        $baseQuery .= " AND k.wilayah = ?";
        $params[] = $wilayah;
    }
    
    if ($status) {
        $baseQuery .= " AND k.status_kerja = ?";
        $params[] = $status;
    }
    
    if ($jabatan) {
        $baseQuery .= " AND k.jabatan = ?";
        $params[] = $jabatan;
    }
    
    // Add search
    if ($search) {
        $baseQuery .= " AND (k.nik LIKE ? OR k.nama_lengkap LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Get total count
    $sql = "SELECT COUNT(*) as total " . $baseQuery;
    $result = fetchOne($sql, $params);
    $recordsTotal = $result['total'];
    $recordsFiltered = $recordsTotal;
    
    // Get data
    $sql = "SELECT k.*,
                   dp.tempat,
                   dp.tanggal_lahir as dp_tanggal_lahir,
                   dp.alamat as dp_alamat,
                   dp.pend_terakhir,
                   dp.status_person,
                   dp.jumlah_anak,
                   dp.email,
                   dp.nama_rekening,
                   dp.bank,
                   dp.no_rekening,
                   dp.no_handphone,
                   dp.no_darurat,
                   dp.pas_photo
            " . $baseQuery;
    
    // Add sorting
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';
    
    $columns = [
        'k.nik',
        'k.nama_lengkap',
        'k.jabatan',
        'k.wilayah',
        'k.status_kerja',
        'k.tanggal_masuk',
        'k.tanggal_hbs_kontrak',
        'k.kinerja'
    ];
    
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
    }
    
    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $length;
    $params[] = $start;
    
    $data = fetchAll($sql, $params);
    
    // Format data for DataTables
    $formattedData = array_map(function($row) {
        // Calculate work duration
        $startDate = new DateTime($row['tanggal_masuk']);
        $now = new DateTime();
        $interval = $startDate->diff($now);
        
        $masa_kerja = '';
        if ($interval->y > 0) $masa_kerja .= $interval->y . 'y ';
        if ($interval->m > 0) $masa_kerja .= $interval->m . 'm';
        if ($masa_kerja === '') $masa_kerja = '0m';
        
        $row['masa_kerja'] = $masa_kerja;
        
        return $row;
    }, $data);
    
    // Log the activity
    logActivity(
        $_SESSION['user_id'],
        'view',
        'Melihat daftar pegawai',
        'success'
    );
    
    // Return JSON response
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $formattedData
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Error in employee list: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
