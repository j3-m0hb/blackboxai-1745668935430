<?php
/**
 * Helper Functions
 * 
 * Collection of utility functions for common tasks
 */

/**
 * Date and Time Functions
 */

function formatDate($date, $format = null) {
    if (!$date) return '';
    
    $settings = getSettings();
    $format = $format ?? ($settings['date_format'] ?? 'd/m/Y');
    
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = null) {
    if (!$datetime) return '';
    
    $settings = getSettings();
    $format = $format ?? ($settings['date_format'] . ' H:i');
    
    return date($format, strtotime($datetime));
}

function calculateAge($birthDate) {
    if (!$birthDate) return 0;
    return date_diff(date_create($birthDate), date_create('today'))->y;
}

function calculateDuration($startDate, $endDate = null) {
    if (!$startDate) return '';
    
    $start = new DateTime($startDate);
    $end = $endDate ? new DateTime($endDate) : new DateTime();
    $interval = $start->diff($end);
    
    $parts = [];
    if ($interval->y > 0) $parts[] = $interval->y . 'y';
    if ($interval->m > 0) $parts[] = $interval->m . 'm';
    if ($interval->d > 0) $parts[] = $interval->d . 'd';
    
    return implode(' ', $parts) ?: '0d';
}

function isWeekend($date) {
    return in_array(date('N', strtotime($date)), [6, 7]); // 6 = Saturday, 7 = Sunday
}

function getWorkingDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $days = 0;
    
    while ($start <= $end) {
        if (!isWeekend($start->format('Y-m-d'))) {
            $days++;
        }
        $start->modify('+1 day');
    }
    
    return $days;
}

/**
 * String Functions
 */

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $string;
}

function sanitizeFilename($filename) {
    // Remove any character that is not alphanumeric, dot, dash, or underscore
    $filename = preg_replace('/[^a-zA-Z0-9\.\-\_]/', '', $filename);
    
    // Remove multiple dots
    $filename = preg_replace('/\.+/', '.', $filename);
    
    // Ensure filename doesn't start with a dot
    $filename = ltrim($filename, '.');
    
    return $filename;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

function truncateText($text, $length = 100, $ending = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length - strlen($ending)) . $ending;
}

/**
 * Number Functions
 */

function formatCurrency($amount, $decimals = 0) {
    $settings = getSettings();
    $symbol = $settings['payroll']['currency_symbol'] ?? 'Rp';
    $thousands = $settings['payroll']['thousands_separator'] ?? '.';
    $decimal = $settings['payroll']['decimal_separator'] ?? ',';
    
    return $symbol . ' ' . number_format($amount, $decimals, $decimal, $thousands);
}

function formatNumber($number, $decimals = 0) {
    $settings = getSettings();
    $thousands = $settings['payroll']['thousands_separator'] ?? '.';
    $decimal = $settings['payroll']['decimal_separator'] ?? ',';
    
    return number_format($number, $decimals, $decimal, $thousands);
}

/**
 * File Functions
 */

function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function isImage($filename) {
    $extensions = ['jpg', 'jpeg', 'png', 'gif'];
    return in_array(getFileExtension($filename), $extensions);
}

function generateThumbnail($sourcePath, $targetPath, $width = 150, $height = 150) {
    list($originalWidth, $originalHeight) = getimagesize($sourcePath);
    
    $ratio = min($width / $originalWidth, $height / $originalHeight);
    $newWidth = round($originalWidth * $ratio);
    $newHeight = round($originalHeight * $ratio);
    
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    switch (getFileExtension($sourcePath)) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case 'png':
            $source = imagecreatefrompng($sourcePath);
            break;
        case 'gif':
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    switch (getFileExtension($targetPath)) {
        case 'jpg':
        case 'jpeg':
            return imagejpeg($thumb, $targetPath, 90);
        case 'png':
            return imagepng($thumb, $targetPath, 9);
        case 'gif':
            return imagegif($thumb, $targetPath);
    }
    
    return false;
}

/**
 * Array Functions
 */

function arrayToOptions($array, $valueKey, $labelKey) {
    $options = [];
    foreach ($array as $item) {
        $options[$item[$valueKey]] = $item[$labelKey];
    }
    return $options;
}

function flattenArray($array, $prefix = '') {
    $result = [];
    
    foreach ($array as $key => $value) {
        $newKey = $prefix ? $prefix . '.' . $key : $key;
        
        if (is_array($value)) {
            $result = array_merge($result, flattenArray($value, $newKey));
        } else {
            $result[$newKey] = $value;
        }
    }
    
    return $result;
}

/**
 * Validation Functions
 */

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^[0-9\-\(\)\/\+\s]*$/', $phone);
}

function validatePassword($password) {
    $settings = getSettings();
    $minLength = $settings['min_password_length'] ?? 8;
    
    if (strlen($password) < $minLength) {
        return false;
    }
    
    if (!empty($settings['require_uppercase']) && !preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if (!empty($settings['require_numbers']) && !preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    if (!empty($settings['require_symbols']) && !preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * URL Functions
 */

function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    return rtrim($protocol . $host . $path, '/') . '/';
}

/**
 * Settings Functions
 */

function getSettings() {
    static $settings = null;
    
    if ($settings === null) {
        $settingsFile = __DIR__ . '/../config/settings.json';
        $settings = file_exists($settingsFile) ? 
                   json_decode(file_get_contents($settingsFile), true) : [];
    }
    
    return $settings;
}

function getSetting($key, $default = null) {
    $settings = getSettings();
    $keys = explode('.', $key);
    $value = $settings;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}
