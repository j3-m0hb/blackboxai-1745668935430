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
    // Get NIK from request
    $nik = isset($_POST['nik']) ? trim($_POST['nik']) : '';
    
    // Validate NIK format
    if (!preg_match('/^\d{7}$/', $nik)) {
        echo 'false'; // Invalid format
        exit();
    }
    
    // Check if editing existing employee
    $currentId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Check if NIK exists
    $sql = "SELECT id FROM karyawan WHERE nik = ? AND deleted_at IS NULL";
    if ($currentId > 0) {
        $sql .= " AND id != ?";
    }
    
    $params = [$nik];
    if ($currentId > 0) {
        $params[] = $currentId;
    }
    
    $existing = fetchOne($sql, $params);
    
    // Return true if NIK is available (doesn't exist), false if taken
    echo $existing ? 'false' : 'true';
    
    // Log the check if it's a new check (not part of form validation)
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        logActivity(
            $_SESSION['user_id'],
            'check',
            'Memeriksa ketersediaan NIK: ' . $nik,
            $existing ? 'exists' : 'available'
        );
    }

} catch (Exception $e) {
    // Log the error
    error_log("Error in NIK check: " . $e->getMessage());
    
    // Return false to indicate error
    echo 'false';
}
