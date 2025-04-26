<?php
require_once 'config/config.php';

// Check if maintenance mode is enabled
$settingsFile = 'config/settings.json';
$settings = file_exists($settingsFile) ? 
            json_decode(file_get_contents($settingsFile), true) : [];

// Allow admin access even in maintenance mode
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'admin') {
    header('Location: index.php');
    exit();
}

// If not in maintenance mode, redirect to index
if (empty($settings['maintenance_mode'])) {
    header('Location: index.php');
    exit();
}

$companyName = $settings['company_name'] ?? APP_NAME;
$logo = !empty($settings['logo']) ? $settings['logo'] : 'assets/images/logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo $companyName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .maintenance-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .logo {
            max-width: 150px;
            margin-bottom: 2rem;
        }
        .maintenance-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .estimated-time {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        @keyframes wrench {
            0% { transform: rotate(-12deg); }
            50% { transform: rotate(12deg); }
            100% { transform: rotate(-12deg); }
        }
        .bi-wrench {
            animation: wrench 3s ease infinite;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="maintenance-container">
            <?php if (file_exists($logo)): ?>
                <img src="<?php echo $logo; ?>" alt="<?php echo $companyName; ?>" class="logo">
            <?php endif; ?>
            
            <div class="maintenance-icon">
                <i class="bi bi-wrench"></i>
            </div>
            
            <h2 class="mb-4">System Maintenance</h2>
            
            <p class="lead">
                We're currently performing scheduled maintenance to improve our services.
                We apologize for any inconvenience this may cause.
            </p>
            
            <?php if (!empty($settings['maintenance_message'])): ?>
                <div class="alert alert-info">
                    <?php echo nl2br(htmlspecialchars($settings['maintenance_message'])); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['maintenance_end_time'])): ?>
                <div class="estimated-time">
                    <strong>Estimated Completion Time:</strong><br>
                    <?php echo date('d F Y H:i', strtotime($settings['maintenance_end_time'])); ?>
                </div>
            <?php endif; ?>
            
            <hr>
            
            <p class="text-muted mb-0">
                If you need immediate assistance, please contact:
            </p>
            <?php if (!empty($settings['contact_info'])): ?>
                <p class="mb-0">
                    <?php echo nl2br(htmlspecialchars($settings['contact_info'])); ?>
                </p>
            <?php else: ?>
                <p class="mb-0">
                    System Administrator
                </p>
            <?php endif; ?>
            
            <div class="mt-4">
                <button type="button" class="btn btn-primary" onclick="checkStatus()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Check Status
                </button>
            </div>
        </div>
    </div>
    
    <script>
        function checkStatus() {
            // Show loading state
            const btn = document.querySelector('.btn-primary');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Checking...';
            
            // Check if maintenance mode is still active
            fetch('api/system/check-maintenance.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.maintenance_mode) {
                        window.location.reload();
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        }
        
        // Auto-check status every 5 minutes
        setInterval(checkStatus, 300000);
    </script>
</body>
</html>
