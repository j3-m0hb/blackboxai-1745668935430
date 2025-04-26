<?php
/**
 * Base Controller Class
 * 
 * Provides common functionality for all controllers:
 * - Request handling
 * - Response formatting
 * - Access control
 * - View rendering
 * - Flash messages
 */
abstract class Controller {
    protected $request;
    protected $response;
    protected $user;
    protected $settings;
    protected $viewData = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->request = $_REQUEST;
        $this->user = $_SESSION['user_id'] ?? null;
        $this->settings = getSettings();
        
        // Set default view data
        $this->viewData = [
            'user' => $this->getUser(),
            'settings' => $this->settings,
            'flash' => $this->getFlashMessages()
        ];
    }
    
    /**
     * Get current user data
     */
    protected function getUser() {
        if (!$this->user) {
            return null;
        }
        
        static $userData = null;
        
        if ($userData === null) {
            $db = Database::getInstance();
            $userData = $db->fetchOne(
                "SELECT u.*, k.nama_lengkap, k.jabatan, k.wilayah 
                 FROM users u 
                 LEFT JOIN karyawan k ON u.karyawan_id = k.id 
                 WHERE u.id = ? AND u.deleted_at IS NULL",
                [$this->user]
            );
        }
        
        return $userData;
    }
    
    /**
     * Check if user has permission
     */
    protected function checkPermission($permission) {
        $user = $this->getUser();
        
        if (!$user) {
            return false;
        }
        
        // Admin has all permissions
        if ($user['level'] === 'admin') {
            return true;
        }
        
        // Check specific permissions
        switch ($permission) {
            case 'view_employees':
                return in_array($user['level'], ['admin', 'hrd']);
                
            case 'manage_employees':
                return in_array($user['level'], ['admin', 'hrd']);
                
            case 'view_attendance':
                return true; // All users can view attendance
                
            case 'manage_attendance':
                return in_array($user['level'], ['admin', 'hrd']);
                
            case 'view_payroll':
                return true; // All users can view their own payroll
                
            case 'manage_payroll':
                return in_array($user['level'], ['admin', 'hrd']);
                
            case 'manage_settings':
                return $user['level'] === 'admin';
                
            default:
                return false;
        }
    }
    
    /**
     * Render view
     */
    protected function render($view, $data = []) {
        // Merge with default view data
        $data = array_merge($this->viewData, $data);
        
        // Extract variables for view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include view file
        $viewFile = APP_ROOT . '/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$view}");
        }
        
        include $viewFile;
        
        // Get contents and clean buffer
        $content = ob_get_clean();
        
        // Include layout if not AJAX request
        if (!isAjaxRequest()) {
            // Include layout
            ob_start();
            include APP_ROOT . '/views/layouts/main.php';
            return ob_get_clean();
        }
        
        return $content;
    }
    
    /**
     * JSON response
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Success response
     */
    protected function success($message = '', $data = []) {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Error response
     */
    protected function error($message = '', $code = 400) {
        return $this->json([
            'success' => false,
            'message' => $message
        ], $code);
    }
    
    /**
     * Redirect
     */
    protected function redirect($url, $message = '', $type = 'success') {
        if ($message) {
            $this->setFlash($message, $type);
        }
        
        header('Location: ' . $url);
        exit();
    }
    
    /**
     * Set flash message
     */
    protected function setFlash($message, $type = 'success') {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    /**
     * Get flash messages
     */
    protected function getFlashMessages() {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
    
    /**
     * Get POST data
     */
    protected function getPost($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get GET data
     */
    protected function getQuery($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Get uploaded file
     */
    protected function getFile($key) {
        return $_FILES[$key] ?? null;
    }
    
    /**
     * Validate CSRF token
     */
    protected function validateCsrf() {
        $token = $this->getPost('csrf_token');
        if (!$token || $token !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }
    }
    
    /**
     * Generate CSRF token
     */
    protected function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Log activity
     */
    protected function logActivity($type, $description, $status = 'success') {
        logActivity(
            $this->user,
            $type,
            $description,
            $status
        );
    }
    
    /**
     * Get pagination data
     */
    protected function getPagination($total, $page = 1, $perPage = null) {
        $perPage = $perPage ?? ($this->settings['items_per_page'] ?? 25);
        $page = max(1, $page);
        $pages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $pages,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
            'offset' => $offset,
            'limit' => $perPage
        ];
    }
    
    /**
     * Get sort parameters
     */
    protected function getSort($defaultField = 'id', $defaultDir = 'desc') {
        $field = $this->getQuery('sort', $defaultField);
        $dir = strtolower($this->getQuery('dir', $defaultDir));
        
        if (!in_array($dir, ['asc', 'desc'])) {
            $dir = $defaultDir;
        }
        
        return [$field, $dir];
    }
    
    /**
     * Get filter parameters
     */
    protected function getFilters($allowedFilters = []) {
        $filters = [];
        
        foreach ($allowedFilters as $filter) {
            $value = $this->getQuery($filter);
            if ($value !== null && $value !== '') {
                $filters[$filter] = $value;
            }
        }
        
        return $filters;
    }
    
    /**
     * Get search parameters
     */
    protected function getSearch($fields = []) {
        $search = $this->getQuery('search');
        if (!$search || !$fields) {
            return '';
        }
        
        $conditions = [];
        foreach ($fields as $field) {
            $conditions[] = "{$field} LIKE ?";
        }
        
        return '(' . implode(' OR ', $conditions) . ')';
    }
    
    /**
     * Get search parameters values
     */
    protected function getSearchValues($fields = []) {
        $search = $this->getQuery('search');
        if (!$search || !$fields) {
            return [];
        }
        
        $values = [];
        foreach ($fields as $field) {
            $values[] = "%{$search}%";
        }
        
        return $values;
    }
}
