<?php
session_start();

// Prevent access if already installed
if (file_exists('.installed') && !isset($_GET['force'])) {
    header('Location: index.php');
    exit();
}

// Define installation steps
$steps = [
    1 => 'System Requirements',
    2 => 'Database Configuration',
    3 => 'Admin Account',
    4 => 'Company Information',
    5 => 'Finalize Installation'
];

// Get current step
$currentStep = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($currentStep) {
        case 2:
            // Validate and save database configuration
            if (validateDatabaseConfig()) {
                $_SESSION['install_db'] = $_POST;
                header('Location: install.php?step=3');
                exit();
            }
            break;
            
        case 3:
            // Validate and save admin account
            if (validateAdminAccount()) {
                $_SESSION['install_admin'] = $_POST;
                header('Location: install.php?step=4');
                exit();
            }
            break;
            
        case 4:
            // Validate and save company information
            if (validateCompanyInfo()) {
                $_SESSION['install_company'] = $_POST;
                header('Location: install.php?step=5');
                exit();
            }
            break;
            
        case 5:
            // Perform installation
            if (performInstallation()) {
                header('Location: index.php');
                exit();
            }
            break;
    }
}

/**
 * Validate database configuration
 */
function validateDatabaseConfig() {
    $required = ['host', 'name', 'user', 'pass'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['error'] = "All fields are required";
            return false;
        }
    }
    
    try {
        $dsn = "mysql:host={$_POST['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $_POST['user'], $_POST['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return true;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database connection failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Validate admin account
 */
function validateAdminAccount() {
    $required = ['username', 'password', 'confirm_password', 'email'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['error'] = "All fields are required";
            return false;
        }
    }
    
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $_SESSION['error'] = "Passwords do not match";
        return false;
    }
    
    if (strlen($_POST['password']) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long";
        return false;
    }
    
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email address";
        return false;
    }
    
    return true;
}

/**
 * Validate company information
 */
function validateCompanyInfo() {
    $required = ['company_name', 'address'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['error'] = "Company name and address are required";
            return false;
        }
    }
    
    return true;
}

/**
 * Perform installation
 */
function performInstallation() {
    try {
        // Initialize system
        require_once 'utils/init.php';
        initializeSystem();
        
        // Create database and tables
        $dbConfig = $_SESSION['install_db'];
        $pdo = new PDO(
            "mysql:host={$dbConfig['host']};charset=utf8mb4",
            $dbConfig['user'],
            $dbConfig['pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbConfig['name']}`");
        
        // Import schema
        $schema = file_get_contents('database/schema.sql');
        $pdo->exec($schema);
        
        // Create admin account
        $admin = $_SESSION['install_admin'];
        $sql = "INSERT INTO users (username, password, email, level) VALUES (?, ?, ?, 'admin')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $admin['username'],
            password_hash($admin['password'], PASSWORD_DEFAULT),
            $admin['email']
        ]);
        
        // Save company information
        $company = $_SESSION['install_company'];
        $settings = json_decode(file_get_contents('config/settings.default.json'), true);
        $settings['company_name'] = $company['company_name'];
        $settings['legal_name'] = $company['legal_name'] ?? $company['company_name'];
        $settings['address'] = $company['address'];
        $settings['contact_info'] = $company['contact_info'] ?? '';
        $settings['tax_id'] = $company['tax_id'] ?? '';
        
        file_put_contents('config/settings.json', json_encode($settings, JSON_PRETTY_PRINT));
        
        // Save database configuration
        $dbConfig = [
            'DB_HOST' => $dbConfig['host'],
            'DB_NAME' => $dbConfig['name'],
            'DB_USER' => $dbConfig['user'],
            'DB_PASS' => $dbConfig['pass']
        ];
        
        $config = "<?php\n\n";
        foreach ($dbConfig as $key => $value) {
            $config .= "define('$key', " . var_export($value, true) . ");\n";
        }
        
        file_put_contents('config/database.php', $config);
        
        // Mark as installed
        file_put_contents('.installed', date('Y-m-d H:i:s'));
        
        // Clear installation session data
        unset($_SESSION['install_db']);
        unset($_SESSION['install_admin']);
        unset($_SESSION['install_company']);
        
        return true;
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Installation failed: " . $e->getMessage();
        return false;
    }
}

// Include header
$pageTitle = "Installation - Step {$currentStep}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .install-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }
        .step {
            position: relative;
            z-index: 2;
            background: white;
            padding: 0 10px;
            text-align: center;
        }
        .step.active .step-number {
            background: #0d6efd;
            color: white;
        }
        .step-number {
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            margin: 0 auto 5px;
        }
        .step-text {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .step.active .step-text {
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <!-- Installation Steps -->
        <div class="steps">
            <?php foreach ($steps as $step => $label): ?>
                <div class="step <?php echo $step === $currentStep ? 'active' : ''; ?>">
                    <div class="step-number"><?php echo $step; ?></div>
                    <div class="step-text"><?php echo $label; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Error Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Step Content -->
        <?php switch ($currentStep): 
            case 1: // System Requirements ?>
                <h4>System Requirements Check</h4>
                <div class="table-responsive">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td>PHP Version (>= 7.4.0)</td>
                                <td><?php echo PHP_VERSION; ?></td>
                                <td>
                                    <?php if (version_compare(PHP_VERSION, '7.4.0', '>=')): ?>
                                        <span class="text-success">✓</span>
                                    <?php else: ?>
                                        <span class="text-danger">✗</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                            $extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'gd', 'zip'];
                            foreach ($extensions as $ext):
                            ?>
                            <tr>
                                <td>PHP Extension: <?php echo $ext; ?></td>
                                <td><?php echo extension_loaded($ext) ? 'Installed' : 'Not Installed'; ?></td>
                                <td>
                                    <?php if (extension_loaded($ext)): ?>
                                        <span class="text-success">✓</span>
                                    <?php else: ?>
                                        <span class="text-danger">✗</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-end">
                    <a href="?step=2" class="btn btn-primary">Next</a>
                </div>
                <?php break;
                
            case 2: // Database Configuration ?>
                <h4>Database Configuration</h4>
                <form method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Database Host</label>
                        <input type="text" class="form-control" name="host" value="localhost" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Name</label>
                        <input type="text" class="form-control" name="name" value="kepeg_sbe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database User</label>
                        <input type="text" class="form-control" name="user" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Database Password</label>
                        <input type="password" class="form-control" name="pass">
                    </div>
                    <div class="text-end">
                        <a href="?step=1" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Next</button>
                    </div>
                </form>
                <?php break;
                
            case 3: // Admin Account ?>
                <h4>Admin Account</h4>
                <form method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <div class="text-end">
                        <a href="?step=2" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Next</button>
                    </div>
                </form>
                <?php break;
                
            case 4: // Company Information ?>
                <h4>Company Information</h4>
                <form method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" class="form-control" name="company_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Legal Name</label>
                        <input type="text" class="form-control" name="legal_name">
                        <div class="form-text">Leave blank to use company name</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Information</label>
                        <textarea class="form-control" name="contact_info" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax ID</label>
                        <input type="text" class="form-control" name="tax_id">
                    </div>
                    <div class="text-end">
                        <a href="?step=3" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Next</button>
                    </div>
                </form>
                <?php break;
                
            case 5: // Finalize Installation ?>
                <h4>Installation Summary</h4>
                <div class="alert alert-info">
                    Please review your installation details before proceeding.
                </div>
                
                <h5>Database Configuration</h5>
                <ul class="list-unstyled">
                    <li><strong>Host:</strong> <?php echo $_SESSION['install_db']['host']; ?></li>
                    <li><strong>Database:</strong> <?php echo $_SESSION['install_db']['name']; ?></li>
                    <li><strong>Username:</strong> <?php echo $_SESSION['install_db']['user']; ?></li>
                </ul>
                
                <h5>Admin Account</h5>
                <ul class="list-unstyled">
                    <li><strong>Username:</strong> <?php echo $_SESSION['install_admin']['username']; ?></li>
                    <li><strong>Email:</strong> <?php echo $_SESSION['install_admin']['email']; ?></li>
                </ul>
                
                <h5>Company Information</h5>
                <ul class="list-unstyled">
                    <li><strong>Name:</strong> <?php echo $_SESSION['install_company']['company_name']; ?></li>
                    <li><strong>Address:</strong> <?php echo $_SESSION['install_company']['address']; ?></li>
                </ul>
                
                <form method="post">
                    <div class="text-end">
                        <a href="?step=4" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-success">Complete Installation</button>
                    </div>
                </form>
                <?php break;
        endswitch; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            
            var forms = document.querySelectorAll('.needs-validation');
            
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
