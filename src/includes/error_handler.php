<?php
/**
 * Custom Error Handler
 * 
 * Provides:
 * - Custom error and exception handling
 * - Error logging
 * - User-friendly error messages
 * - Development mode detailed error reporting
 */

// Set error reporting based on environment
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}

// Set error log file
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Register error handler
set_error_handler('customErrorHandler');

// Register exception handler
set_exception_handler('customExceptionHandler');

// Register shutdown function
register_shutdown_function('shutdownHandler');

/**
 * Custom error handler
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // Check if error should be reported based on error_reporting
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Get error type string
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Standards',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    $type = $errorTypes[$errno] ?? 'Unknown Error';
    
    // Create error message
    $message = sprintf(
        "[%s] %s in %s on line %d",
        $type,
        $errstr,
        $errfile,
        $errline
    );
    
    // Log error
    error_log($message);
    
    // Log to activity log if available
    if (function_exists('logActivity')) {
        logActivity(
            $_SESSION['user_id'] ?? null,
            'error',
            $message,
            'error',
            null,
            null,
            [
                'type' => $type,
                'file' => $errfile,
                'line' => $errline,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
            ]
        );
    }
    
    // Display error based on environment
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        displayDetailedError($type, $errstr, $errfile, $errline);
    } else {
        displayUserFriendlyError();
    }
    
    // Don't execute PHP's internal error handler
    return true;
}

/**
 * Custom exception handler
 */
function customExceptionHandler($exception) {
    // Create error message
    $message = sprintf(
        "[Exception] %s in %s on line %d",
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );
    
    // Log error
    error_log($message);
    error_log($exception->getTraceAsString());
    
    // Log to activity log if available
    if (function_exists('logActivity')) {
        logActivity(
            $_SESSION['user_id'] ?? null,
            'exception',
            $message,
            'error',
            null,
            null,
            [
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ]
        );
    }
    
    // Display error based on environment
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        displayDetailedError(
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTrace()
        );
    } else {
        displayUserFriendlyError();
    }
}

/**
 * Shutdown handler to catch fatal errors
 */
function shutdownHandler() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clear any output that has already been generated
        if (ob_get_length()) {
            ob_clean();
        }
        
        customErrorHandler(
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );
    }
}

/**
 * Display detailed error information (for development)
 */
function displayDetailedError($type, $message, $file, $line, $trace = null) {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'type' => $type,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'trace' => $trace
        ]);
        exit();
    }
    
    // Display HTML error page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - <?php echo $type; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><?php echo $type; ?></h5>
                </div>
                <div class="card-body">
                    <h6>Message:</h6>
                    <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars($message); ?></pre>
                    
                    <h6>Location:</h6>
                    <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars($file); ?> (Line <?php echo $line; ?>)</pre>
                    
                    <?php if ($trace): ?>
                        <h6>Stack Trace:</h6>
                        <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars(print_r($trace, true)); ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

/**
 * Display user-friendly error page
 */
function displayUserFriendlyError() {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => 'An error occurred while processing your request.'
        ]);
        exit();
    }
    
    // Display HTML error page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5 text-center">
            <div class="card">
                <div class="card-body">
                    <h1 class="display-1 text-danger">Oops!</h1>
                    <h2 class="mb-4">Something went wrong</h2>
                    <p class="lead">
                        We apologize for the inconvenience. Please try again later or contact support if the problem persists.
                    </p>
                    <div class="mt-4">
                        <a href="javascript:history.back()" class="btn btn-secondary me-2">Go Back</a>
                        <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
