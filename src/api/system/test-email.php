<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

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

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

try {
    // Get email settings from request
    $settings = [
        'smtp_host' => $_POST['smtp_host'] ?? '',
        'smtp_port' => intval($_POST['smtp_port'] ?? 587),
        'smtp_username' => $_POST['smtp_username'] ?? '',
        'smtp_password' => $_POST['smtp_password'] ?? '',
        'from_email' => $_POST['from_email'] ?? '',
        'from_name' => $_POST['from_name'] ?? ''
    ];
    
    // Validate required fields
    $required = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'from_email'];
    foreach ($required as $field) {
        if (empty($settings[$field])) {
            throw new Exception("Field $field is required");
        }
    }
    
    // Load PHPMailer
    require '../../vendor/phpmailer/phpmailer/src/Exception.php';
    require '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require '../../vendor/phpmailer/phpmailer/src/SMTP.php';
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host = $settings['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $settings['smtp_username'];
    $mail->Password = $settings['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $settings['smtp_port'];
    
    // Recipients
    $mail->setFrom($settings['from_email'], $settings['from_name']);
    $mail->addAddress($settings['from_email']); // Send test email to self
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from ' . APP_NAME;
    
    // Create email body
    $body = '
        <h2>Test Email</h2>
        <p>This is a test email from ' . APP_NAME . ' to verify email settings.</p>
        <hr>
        <h3>Email Configuration:</h3>
        <ul>
            <li><strong>SMTP Host:</strong> ' . $settings['smtp_host'] . '</li>
            <li><strong>SMTP Port:</strong> ' . $settings['smtp_port'] . '</li>
            <li><strong>From Email:</strong> ' . $settings['from_email'] . '</li>
            <li><strong>From Name:</strong> ' . $settings['from_name'] . '</li>
        </ul>
        <hr>
        <p>If you received this email, your email settings are configured correctly.</p>
        <p>
            <small>
                Sent on: ' . date('Y-m-d H:i:s') . '<br>
                Server IP: ' . $_SERVER['SERVER_ADDR'] . '<br>
                Server Name: ' . $_SERVER['SERVER_NAME'] . '
            </small>
        </p>
    ';
    
    $mail->Body = $body;
    $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $body));
    
    // Send email
    if (!$mail->send()) {
        throw new Exception('Failed to send test email: ' . $mail->ErrorInfo);
    }
    
    // Log the activity
    logActivity(
        $_SESSION['user_id'],
        'test',
        'Sent test email to ' . $settings['from_email'],
        'success'
    );
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent successfully to ' . $settings['from_email']
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Error testing email: " . $e->getMessage());
    
    // Log the failed activity
    if (isset($settings['from_email'])) {
        logActivity(
            $_SESSION['user_id'],
            'test',
            'Failed to send test email to ' . $settings['from_email'] . ': ' . $e->getMessage(),
            'failure'
        );
    }
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
