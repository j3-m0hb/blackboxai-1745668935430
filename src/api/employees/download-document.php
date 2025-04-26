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
    // Get document ID
    $docId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$docId) {
        throw new Exception('Invalid document ID');
    }
    
    // Get document info
    $sql = "SELECT d.*, k.nama_lengkap, k.id as employee_id 
            FROM dokumen_karyawan d
            JOIN karyawan k ON d.karyawan_id = k.id
            WHERE d.id = ? AND d.deleted_at IS NULL";
    
    $document = fetchOne($sql, [$docId]);
    
    if (!$document) {
        throw new Exception('Document not found');
    }
    
    // Check if user has permission to download
    if (!isAdmin() && !isHRD()) {
        // Regular employees can only download their own documents
        if (!isset($_SESSION['karyawan_id']) || $_SESSION['karyawan_id'] != $document['employee_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit();
        }
    }
    
    // Get file path
    $filePath = '../../' . $document['file_path'];
    
    if (!file_exists($filePath)) {
        throw new Exception('File not found on server');
    }
    
    // Log the download activity
    logActivity(
        $_SESSION['user_id'],
        'download',
        'Download dokumen: ' . $document['nama_dokumen'] . ' (' . $document['nama_lengkap'] . ')',
        'success',
        'dokumen_karyawan',
        $docId
    );
    
    // Get file info
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    // Get file extension
    $extension = strtolower(pathinfo($document['nama_dokumen'], PATHINFO_EXTENSION));
    
    // Set appropriate headers based on file type
    switch ($extension) {
        case 'pdf':
            $mimeType = 'application/pdf';
            break;
        case 'doc':
            $mimeType = 'application/msword';
            break;
        case 'docx':
            $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            break;
        case 'jpg':
        case 'jpeg':
            $mimeType = 'image/jpeg';
            break;
        case 'png':
            $mimeType = 'image/png';
            break;
        default:
            $mimeType = 'application/octet-stream';
    }
    
    // Clean output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($document['nama_dokumen']) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    
    // Output file in chunks to handle large files
    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        throw new Exception('Error opening file');
    }
    
    while (!feof($handle)) {
        $chunk = fread($handle, 8192);
        if ($chunk === false) {
            break;
        }
        echo $chunk;
        flush();
    }
    
    fclose($handle);
    exit();

} catch (Exception $e) {
    // Log the error
    error_log("Error in document download: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
