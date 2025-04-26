<?php
/**
 * Base API Controller Class
 * 
 * Provides common functionality for API controllers:
 * - JSON responses
 * - API authentication
 * - Rate limiting
 * - Request validation
 * - Error handling
 */
class ApiController extends Controller {
    protected $method;
    protected $contentType;
    protected $rawInput;
    protected $apiUser;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Get request method and content type
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Get raw input
        $this->rawInput = file_get_contents('php://input');
        
        // Parse JSON input
        if (strpos($this->contentType, 'application/json') !== false) {
            $_POST = json_decode($this->rawInput, true) ?? [];
        }
        
        // Set JSON response headers
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        
        // Validate request
        $this->validateRequest();
    }
    
    /**
     * Validate API request
     */
    protected function validateRequest() {
        // Check maintenance mode
        if (!empty($this->settings['maintenance_mode'])) {
            $this->error('System is under maintenance', 503);
        }
        
        // Check rate limit
        if (!$this->checkRateLimit()) {
            $this->error('Rate limit exceeded', 429);
        }
        
        // Validate API token if required
        if ($this->requiresAuth() && !$this->validateApiToken()) {
            $this->error('Invalid API token', 401);
        }
    }
    
    /**
     * Check if endpoint requires authentication
     */
    protected function requiresAuth() {
        // Public endpoints that don't require authentication
        $publicEndpoints = [
            '/api/auth/login',
            '/api/auth/forgot-password',
            '/api/auth/reset-password',
            '/api/system/check-maintenance'
        ];
        
        return !in_array($_SERVER['REQUEST_URI'], $publicEndpoints);
    }
    
    /**
     * Validate API token
     */
    protected function validateApiToken() {
        // Get token from header
        $token = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
        
        if (!$token) {
            return false;
        }
        
        // Validate token
        $db = Database::getInstance();
        $apiUser = $db->fetchOne(
            "SELECT u.*, k.nama_lengkap, k.jabatan, k.wilayah 
             FROM users u 
             LEFT JOIN karyawan k ON u.karyawan_id = k.id 
             WHERE u.api_token = ? AND u.deleted_at IS NULL",
            [$token]
        );
        
        if (!$apiUser) {
            return false;
        }
        
        // Store API user
        $this->apiUser = $apiUser;
        return true;
    }
    
    /**
     * Check rate limit
     */
    protected function checkRateLimit() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $endpoint = $_SERVER['REQUEST_URI'];
        
        // Get rate limit settings
        $window = 60; // 1 minute
        $maxRequests = 60; // 60 requests per minute
        
        // Get current requests
        $key = "rate_limit:{$ip}:{$endpoint}";
        $requests = apcu_fetch($key) ?? 0;
        
        if ($requests >= $maxRequests) {
            return false;
        }
        
        // Increment requests
        apcu_inc($key, 1, $success, $window);
        
        return true;
    }
    
    /**
     * Validate required parameters
     */
    protected function validateParams($required = []) {
        $missing = [];
        
        foreach ($required as $param) {
            if (!isset($_REQUEST[$param]) || $_REQUEST[$param] === '') {
                $missing[] = $param;
            }
        }
        
        if ($missing) {
            $this->error('Missing required parameters: ' . implode(', ', $missing), 400);
        }
        
        return true;
    }
    
    /**
     * Success response
     */
    protected function success($data = null, $message = '') {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->json($response);
    }
    
    /**
     * Error response
     */
    protected function error($message, $code = 400, $errors = []) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        $this->json($response, $code);
    }
    
    /**
     * Validation error response
     */
    protected function validationError($errors) {
        $this->error('Validation failed', 422, $errors);
    }
    
    /**
     * Not found response
     */
    protected function notFound($message = 'Resource not found') {
        $this->error($message, 404);
    }
    
    /**
     * Unauthorized response
     */
    protected function unauthorized($message = 'Unauthorized') {
        $this->error($message, 401);
    }
    
    /**
     * Forbidden response
     */
    protected function forbidden($message = 'Forbidden') {
        $this->error($message, 403);
    }
    
    /**
     * Method not allowed response
     */
    protected function methodNotAllowed($message = 'Method not allowed') {
        $this->error($message, 405);
    }
    
    /**
     * Get pagination metadata
     */
    protected function getPaginationMeta($pagination) {
        return [
            'current_page' => $pagination['current_page'],
            'per_page' => $pagination['per_page'],
            'total' => $pagination['total'],
            'last_page' => $pagination['last_page'],
            'from' => $pagination['from'],
            'to' => $pagination['to']
        ];
    }
    
    /**
     * Format collection response
     */
    protected function collection($data, $pagination = null, $message = '') {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
        
        if ($pagination) {
            $response['meta'] = [
                'pagination' => $this->getPaginationMeta($pagination)
            ];
        }
        
        $this->json($response);
    }
    
    /**
     * Format resource response
     */
    protected function resource($data, $message = '') {
        $this->success($data, $message);
    }
    
    /**
     * Log API request
     */
    protected function logRequest($status = 'success') {
        $this->logActivity(
            'api_request',
            sprintf(
                '%s %s',
                $this->method,
                $_SERVER['REQUEST_URI']
            ),
            $status,
            null,
            null,
            [
                'method' => $this->method,
                'uri' => $_SERVER['REQUEST_URI'],
                'params' => $_REQUEST,
                'user_id' => $this->apiUser['id'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]
        );
    }
}
