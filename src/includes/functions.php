<?php
// Common utility functions

/**
 * User Authentication Functions
 */

function getUserById($id) {
    $sql = "SELECT u.*, k.nama_lengkap, k.jabatan, k.wilayah 
            FROM users u 
            LEFT JOIN karyawan k ON u.karyawan_id = k.id 
            WHERE u.id = ? AND u.deleted_at IS NULL";
    return fetchOne($sql, [$id]);
}

function authenticateUser($username, $password) {
    $sql = "SELECT * FROM users WHERE username = ? AND deleted_at IS NULL";
    $user = fetchOne($sql, [$username]);
    
    if ($user && md5($password) === $user['password']) {
        // Log successful login
        logActivity($user['id'], 'login', 'Login berhasil', 'success');
        return $user;
    }
    
    // Log failed login attempt
    logActivity(null, 'login', 'Login gagal - Username/password salah', 'failure');
    return false;
}

function isAdmin() {
    return isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'admin';
}

function isHRD() {
    return isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'hrd';
}

function checkPermission($requiredLevel) {
    if (!isset($_SESSION['user_level'])) {
        header('Location: login.php');
        exit();
    }
    
    if ($_SESSION['user_level'] !== 'admin' && $_SESSION['user_level'] !== $requiredLevel) {
        header('Location: index.php?error=unauthorized');
        exit();
    }
}

/**
 * Employee Data Functions
 */

function getTotalEmployees() {
    $sql = "SELECT COUNT(*) as total FROM karyawan WHERE deleted_at IS NULL";
    $result = fetchOne($sql);
    return $result['total'] ?? 0;
}

function getEmployeesByStatus($status) {
    $sql = "SELECT COUNT(*) as total FROM karyawan WHERE status_kerja = ? AND deleted_at IS NULL";
    $result = fetchOne($sql, [$status]);
    return $result['total'] ?? 0;
}

function getCouriersByLocation($location) {
    $sql = "SELECT COUNT(*) as total FROM karyawan 
            WHERE wilayah = ? AND jabatan = 'Kurir' AND deleted_at IS NULL";
    $result = fetchOne($sql, [$location]);
    return $result['total'] ?? 0;
}

function getActiveContracts() {
    $today = date('Y-m-d');
    $sql = "SELECT k.*, dp.nama_lengkap 
            FROM karyawan k
            LEFT JOIN data_personal dp ON k.nama_lengkap = dp.nama_lengkap
            WHERE k.status_kerja = 'Kontrak' 
            AND k.tanggal_hbs_kontrak >= ? 
            AND k.deleted_at IS NULL
            ORDER BY k.tanggal_hbs_kontrak ASC
            LIMIT 5";
    return fetchAll($sql, [$today]);
}

function getExpiringContracts() {
    $today = date('Y-m-d');
    $thirtyDaysLater = date('Y-m-d', strtotime('+30 days'));
    
    $sql = "SELECT k.*, dp.nama_lengkap 
            FROM karyawan k
            LEFT JOIN data_personal dp ON k.nama_lengkap = dp.nama_lengkap
            WHERE k.status_kerja = 'Kontrak' 
            AND k.tanggal_hbs_kontrak BETWEEN ? AND ?
            AND k.deleted_at IS NULL
            ORDER BY k.tanggal_hbs_kontrak ASC";
    return fetchAll($sql, [$today, $thirtyDaysLater]);
}

/**
 * Activity Logging Functions
 */

function logActivity($userId, $activityType, $description, $status = 'success') {
    $sql = "INSERT INTO activity_log (user_id, activity_type, description, status, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $params = [
        $userId,
        $activityType,
        $description,
        $status,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    return insert($sql, $params);
}

function getRecentActivities($limit = 10) {
    $sql = "SELECT al.*, u.username, k.nama_lengkap 
            FROM activity_log al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN karyawan k ON u.karyawan_id = k.id
            ORDER BY al.created_at DESC
            LIMIT ?";
    
    $activities = fetchAll($sql, [$limit]);
    
    $html = '';
    foreach ($activities as $activity) {
        $statusClass = $activity['status'] === 'success' ? 'text-success' : 'text-danger';
        $html .= sprintf(
            '<tr>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td class="%s">%s</td>
            </tr>',
            date('d/m/Y H:i', strtotime($activity['created_at'])),
            htmlspecialchars($activity['username'] ?? 'System'),
            htmlspecialchars($activity['description']),
            $statusClass,
            htmlspecialchars($activity['status'])
        );
    }
    
    return $html;
}

/**
 * File Upload Functions
 */

function uploadFile($file, $type = 'document') {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file parameters.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed with error code: ' . $file['error']);
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('File is too large. Maximum size is ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB');
    }

    if (!in_array($file['type'], ALLOWED_FILE_TYPES)) {
        throw new Exception('File type not allowed.');
    }

    $uploadDir = UPLOAD_PATH . '/' . ($type === 'photo' ? 'photos' : 'documents');
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to move uploaded file.');
    }

    return $fileName;
}

/**
 * Date and Time Functions
 */

function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

function calculateAge($birthDate) {
    return date_diff(date_create($birthDate), date_create('today'))->y;
}

function calculateWorkDuration($startDate) {
    $start = new DateTime($startDate);
    $now = new DateTime();
    $interval = $start->diff($now);
    
    $years = $interval->y;
    $months = $interval->m;
    $days = $interval->d;
    
    $result = '';
    if ($years > 0) {
        $result .= $years . 'y ';
    }
    if ($months > 0) {
        $result .= $months . 'm ';
    }
    if ($days > 0) {
        $result .= $days . 'd';
    }
    
    return trim($result) ?: '0d';
}

/**
 * Security Functions
 */

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function validateToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Response Functions
 */

function jsonResponse($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

/**
 * Utility Functions
 */

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function getMonthName($month) {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $months[$month] ?? '';
}

function generateBreadcrumbs($paths) {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    $html .= '<li class="breadcrumb-item"><a href="index.php">Home</a></li>';
    
    $count = count($paths);
    $i = 1;
    
    foreach ($paths as $title => $url) {
        if ($i === $count) {
            $html .= sprintf('<li class="breadcrumb-item active" aria-current="page">%s</li>', $title);
        } else {
            $html .= sprintf('<li class="breadcrumb-item"><a href="%s">%s</a></li>', $url, $title);
        }
        $i++;
    }
    
    $html .= '</ol></nav>';
    return $html;
}
