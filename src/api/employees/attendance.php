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
    // Get parameters
    $employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
    $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
    $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    
    if (!$employeeId) {
        throw new Exception('Invalid employee ID');
    }
    
    // Get DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Base query
    $baseQuery = "FROM absensi 
                  WHERE karyawan_id = ? 
                  AND MONTH(tanggal) = ? 
                  AND YEAR(tanggal) = ?
                  AND deleted_at IS NULL";
    
    $params = [$employeeId, $month, $year];
    
    // Add search
    if ($search) {
        $baseQuery .= " AND (status_hadir LIKE ? OR keterangan LIKE ?)";
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
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = [
        'tanggal',
        'status_hadir',
        'waktu',
        'keterangan'
    ];
    
    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
        if ($columns[$orderColumn] === 'tanggal') {
            $sql .= ", waktu ASC";
        }
    }
    
    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $length;
    $params[] = $start;
    
    $data = fetchAll($sql, $params);
    
    // Get monthly summary
    $sql = "SELECT 
                COUNT(CASE WHEN status_hadir = 'masuk' THEN 1 END) as hadir,
                COUNT(CASE WHEN status_hadir = 'ijin' THEN 1 END) as ijin,
                COUNT(CASE WHEN status_hadir = 'sakit' THEN 1 END) as sakit,
                COUNT(CASE WHEN status_hadir = 'cuti' THEN 1 END) as cuti,
                COUNT(CASE WHEN status_hadir = 'lembur' THEN 1 END) as lembur,
                COUNT(DISTINCT DATE(tanggal)) as total_hari,
                COUNT(CASE WHEN TIME(waktu) > '08:00:00' AND status_hadir = 'masuk' THEN 1 END) as telat
            FROM absensi 
            WHERE karyawan_id = ? 
            AND MONTH(tanggal) = ? 
            AND YEAR(tanggal) = ?
            AND deleted_at IS NULL";
    
    $summary = fetchOne($sql, [$employeeId, $month, $year]);
    
    // Get working days in month
    $totalWorkingDays = getWorkingDays($month, $year);
    $summary['working_days'] = $totalWorkingDays;
    $summary['absent'] = $totalWorkingDays - $summary['total_hari'];
    
    // Calculate attendance percentage
    $summary['attendance_rate'] = $totalWorkingDays > 0 ? 
        round(($summary['hadir'] / $totalWorkingDays) * 100, 2) : 0;
    
    // Log the activity
    logActivity(
        $_SESSION['user_id'],
        'view',
        'Melihat absensi pegawai: ' . date('F Y', mktime(0, 0, 0, $month, 1, $year)),
        'success',
        'absensi',
        $employeeId
    );
    
    // Return JSON response
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Error in employee attendance: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Calculate working days in a month (excluding weekends)
 */
function getWorkingDays($month, $year) {
    $totalDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $workingDays = 0;
    
    for ($day = 1; $day <= $totalDays; $day++) {
        $date = new DateTime("$year-$month-$day");
        $dayOfWeek = $date->format('N');
        
        // Skip weekends (6 = Saturday, 7 = Sunday)
        if ($dayOfWeek < 6) {
            $workingDays++;
        }
    }
    
    return $workingDays;
}
