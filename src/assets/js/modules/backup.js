document.addEventListener('DOMContentLoaded', function() {
    // Initialize backup table
    initBackupTable();
    
    // Initialize backup creation
    initBackupCreation();
    
    // Initialize restore functionality
    initRestore();
});

// Initialize backup table
function initBackupTable() {
    const table = $('#backupsTable').DataTable({
        ajax: {
            url: '../../utils/backup.php?action=list',
            dataSrc: 'backups'
        },
        columns: [
            {
                data: 'created_at',
                render: function(data) {
                    return moment(data).format('DD/MM/YYYY HH:mm:ss');
                }
            },
            { data: 'filename' },
            {
                data: 'size',
                render: function(data) {
                    return formatFileSize(data);
                }
            },
            {
                data: 'created_by',
                render: function(data, type, row) {
                    return row.created_by_name || data;
                }
            },
            {
                data: null,
                render: function(data) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <a href="../../utils/backup.php?action=download&file=${data.filename}" 
                               class="btn btn-info" title="Download">
                                <i class="bi bi-download"></i>
                            </a>
                            <button type="button" class="btn btn-danger delete-backup" 
                                    data-file="${data.filename}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10
    });
    
    // Handle backup deletion
    $('#backupsTable').on('click', '.delete-backup', function() {
        const filename = $(this).data('file');
        
        if (confirm('Are you sure you want to delete this backup?')) {
            $.ajax({
                url: '../../utils/backup.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    file: filename
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Backup deleted successfully');
                        table.ajax.reload();
                    } else {
                        toastr.error(response.message || 'Failed to delete backup');
                    }
                },
                error: function() {
                    toastr.error('System error occurred');
                }
            });
        }
    });
}

// Initialize backup creation
function initBackupCreation() {
    $('#createBackup').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.prop('disabled', true)
           .html('<i class="bi bi-arrow-repeat spin me-1"></i>Creating Backup...');
        
        $.ajax({
            url: '../../utils/backup.php',
            type: 'POST',
            data: { action: 'backup' },
            success: function(response) {
                if (response.success) {
                    toastr.success('Backup created successfully');
                    $('#backupsTable').DataTable().ajax.reload();
                } else {
                    toastr.error(response.message || 'Failed to create backup');
                }
            },
            error: function() {
                toastr.error('System error occurred');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
}

// Initialize restore functionality
function initRestore() {
    let selectedFile = null;
    
    // Handle file selection
    $('#backupFile').on('change', function(e) {
        selectedFile = e.target.files[0];
    });
    
    // Handle form submission
    $('#restoreForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!selectedFile) {
            toastr.error('Please select a backup file');
            return;
        }
        
        // Show confirmation modal
        $('#restoreModal').modal('show');
    });
    
    // Handle restore confirmation
    $('#confirmRestore').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.prop('disabled', true)
           .html('<i class="bi bi-arrow-repeat spin me-1"></i>Restoring...');
        
        const formData = new FormData();
        formData.append('action', 'restore');
        formData.append('file', selectedFile);
        
        $.ajax({
            url: '../../utils/backup.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Database restored successfully');
                    $('#restoreForm')[0].reset();
                } else {
                    toastr.error(response.message || 'Failed to restore database');
                }
            },
            error: function() {
                toastr.error('System error occurred');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
                $('#restoreModal').modal('hide');
            }
        });
    });
}

// Utility function to format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Add spinning animation for loading icon
const style = document.createElement('style');
style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
