<?php
require_once '../../config/database.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../../index.php?error=unauthorized');
    exit();
}

// Set page title and breadcrumbs
$pageTitle = 'System Settings';
$breadcrumbs = ['System' => '', 'Settings' => ''];

// Get current settings
$settingsFile = '../../config/settings.json';
$settings = file_exists($settingsFile) ? 
            json_decode(file_get_contents($settingsFile), true) : [];

// Include header
include '../../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#company">
                                <i class="bi bi-building me-1"></i>Company
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#system">
                                <i class="bi bi-gear me-1"></i>System
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#email">
                                <i class="bi bi-envelope me-1"></i>Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#security">
                                <i class="bi bi-shield-lock me-1"></i>Security
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <form id="settingsForm">
                        <div class="tab-content">
                            <!-- Company Settings -->
                            <div class="tab-pane fade show active" id="company">
                                <h5 class="mb-4">Company Information</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Company Name</label>
                                        <input type="text" class="form-control" name="company_name"
                                               value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Legal Name</label>
                                        <input type="text" class="form-control" name="legal_name"
                                               value="<?php echo htmlspecialchars($settings['legal_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="3"><?php 
                                            echo htmlspecialchars($settings['address'] ?? ''); 
                                        ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Contact Information</label>
                                        <textarea class="form-control" name="contact_info" rows="3"><?php 
                                            echo htmlspecialchars($settings['contact_info'] ?? ''); 
                                        ?></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Tax ID</label>
                                        <input type="text" class="form-control" name="tax_id"
                                               value="<?php echo htmlspecialchars($settings['tax_id'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Company Logo</label>
                                        <input type="file" class="form-control" name="logo" accept="image/*">
                                        <?php if (!empty($settings['logo'])): ?>
                                            <div class="mt-2">
                                                <img src="../../<?php echo htmlspecialchars($settings['logo']); ?>" 
                                                     alt="Company Logo" class="img-thumbnail" style="max-height: 50px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- System Settings -->
                            <div class="tab-pane fade" id="system">
                                <h5 class="mb-4">System Configuration</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Default Language</label>
                                        <select class="form-select" name="default_language">
                                            <option value="id" <?php echo ($settings['default_language'] ?? '') === 'id' ? 'selected' : ''; ?>>Indonesian</option>
                                            <option value="en" <?php echo ($settings['default_language'] ?? '') === 'en' ? 'selected' : ''; ?>>English</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Timezone</label>
                                        <select class="form-select" name="timezone">
                                            <?php
                                            $timezones = DateTimeZone::listIdentifiers();
                                            foreach ($timezones as $tz) {
                                                $selected = ($settings['timezone'] ?? '') === $tz ? 'selected' : '';
                                                echo "<option value=\"$tz\" $selected>$tz</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Date Format</label>
                                        <select class="form-select" name="date_format">
                                            <option value="d/m/Y" <?php echo ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                            <option value="Y-m-d" <?php echo ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                            <option value="d-m-Y" <?php echo ($settings['date_format'] ?? '') === 'd-m-Y' ? 'selected' : ''; ?>>DD-MM-YYYY</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Items Per Page</label>
                                        <select class="form-select" name="items_per_page">
                                            <option value="10" <?php echo ($settings['items_per_page'] ?? '') === '10' ? 'selected' : ''; ?>>10</option>
                                            <option value="25" <?php echo ($settings['items_per_page'] ?? '') === '25' ? 'selected' : ''; ?>>25</option>
                                            <option value="50" <?php echo ($settings['items_per_page'] ?? '') === '50' ? 'selected' : ''; ?>>50</option>
                                            <option value="100" <?php echo ($settings['items_per_page'] ?? '') === '100' ? 'selected' : ''; ?>>100</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Session Timeout (minutes)</label>
                                        <input type="number" class="form-control" name="session_timeout" min="5" max="1440"
                                               value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '60'); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Maintenance Mode</label>
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" name="maintenance_mode" value="1"
                                                   <?php echo !empty($settings['maintenance_mode']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Enable maintenance mode</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Email Settings -->
                            <div class="tab-pane fade" id="email">
                                <h5 class="mb-4">Email Configuration</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" name="smtp_host"
                                               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" name="smtp_port"
                                               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control" name="smtp_username"
                                               value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP Password</label>
                                        <input type="password" class="form-control" name="smtp_password"
                                               value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">From Email</label>
                                        <input type="email" class="form-control" name="from_email"
                                               value="<?php echo htmlspecialchars($settings['from_email'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">From Name</label>
                                        <input type="text" class="form-control" name="from_name"
                                               value="<?php echo htmlspecialchars($settings['from_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="button" class="btn btn-info" id="testEmail">
                                            <i class="bi bi-envelope-check me-1"></i>Test Email Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Security Settings -->
                            <div class="tab-pane fade" id="security">
                                <h5 class="mb-4">Security Configuration</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Minimum Password Length</label>
                                        <input type="number" class="form-control" name="min_password_length" min="6" max="32"
                                               value="<?php echo htmlspecialchars($settings['min_password_length'] ?? '8'); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Password Complexity</label>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="require_uppercase" value="1"
                                                   <?php echo !empty($settings['require_uppercase']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Require uppercase letters</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="require_numbers" value="1"
                                                   <?php echo !empty($settings['require_numbers']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Require numbers</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="require_symbols" value="1"
                                                   <?php echo !empty($settings['require_symbols']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Require special characters</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Maximum Login Attempts</label>
                                        <input type="number" class="form-control" name="max_login_attempts" min="3" max="10"
                                               value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? '5'); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Lockout Duration (minutes)</label>
                                        <input type="number" class="form-control" name="lockout_duration" min="5" max="1440"
                                               value="<?php echo htmlspecialchars($settings['lockout_duration'] ?? '30'); ?>">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Password Expiry (days)</label>
                                        <input type="number" class="form-control" name="password_expiry" min="0" max="365"
                                               value="<?php echo htmlspecialchars($settings['password_expiry'] ?? '90'); ?>">
                                        <div class="form-text">Set to 0 to disable password expiry</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-secondary me-2" id="resetSettings">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset to Default
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Save Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Add custom JavaScript
$extraJS = '<script src="../../assets/js/modules/settings.js"></script>';

// Include footer
include '../../includes/footer.php';
?>
