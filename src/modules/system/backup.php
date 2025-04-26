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
$pageTitle = 'Database Backup & Restore';
$breadcrumbs = ['System' => '', 'Backup & Restore' => ''];

// Include header
include '../../includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <!-- Backup Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Database Backup</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <p class="text-muted">
                                Create a backup of the entire database. The backup will be stored in a compressed file
                                and can be used to restore the database if needed.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-primary" id="createBackup">
                                <i class="bi bi-download me-1"></i>Create Backup
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Recent Backups</h6>
                    <div class="table-responsive">
                        <table class="table table-hover" id="backupsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Restore Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Database Restore</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Restoring a backup will overwrite the current database. 
                        Make sure to create a backup of the current data before proceeding.
                    </div>
                    
                    <form id="restoreForm" class="row g-3">
                        <div class="col-md-8">
                            <input type="file" class="form-control" id="backupFile" name="backupFile" 
                                   accept=".sql,.zip" required>
                            <div class="form-text">
                                Select a backup file (.sql or .zip) to restore the database
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-upload me-1"></i>Restore Database
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Restore</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Warning:</strong> This action will overwrite the current database.
                    All existing data will be replaced with the data from the backup file.
                    This action cannot be undone.
                </div>
                <p>Are you sure you want to restore the database from this backup?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRestore">
                    Yes, Restore Database
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Add custom JavaScript
$extraJS = '<script src="../../assets/js/modules/backup.js"></script>';

// Include footer
include '../../includes/footer.php';
?>
