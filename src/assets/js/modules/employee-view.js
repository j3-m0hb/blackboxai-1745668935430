document.addEventListener('DOMContentLoaded', function() {
    // Initialize document table
    initDocumentsTable();
    
    // Initialize attendance table
    initAttendanceTable();
    
    // Load employee history
    loadHistory();
    
    // Initialize delete functionality
    initDeleteHandler();
    
    // Initialize document upload
    initDocumentUpload();
});

// Initialize documents table
function initDocumentsTable() {
    const table = $('#documentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../../api/employees/documents.php',
            type: 'POST',
            data: {
                employee_id: employeeId
            }
        },
        columns: [
            { data: 'nama_dokumen' },
            { data: 'jenis_dokumen' },
            { 
                data: 'created_at',
                render: function(data) {
                    return moment(data).format('DD/MM/YYYY HH:mm');
                }
            },
            {
                data: null,
                render: function(data) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <a href="../../${data.file_path}" class="btn btn-info" target="_blank" title="Lihat">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="../../api/employees/download-document.php?id=${data.id}" 
                               class="btn btn-success" title="Download">
                                <i class="bi bi-download"></i>
                            </a>
                            ${isAdmin || isHRD ? `
                                <button type="button" class="btn btn-danger delete-document" 
                                        data-id="${data.id}" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    `;
                }
            }
        ],
        order: [[2, 'desc']]
    });
    
    // Handle document deletion
    $('#documentsTable').on('click', '.delete-document', function() {
        const docId = $(this).data('id');
        
        if (confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) {
            $.ajax({
                url: '../../api/employees/delete-document.php',
                type: 'POST',
                data: { id: docId },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Dokumen berhasil dihapus');
                        table.ajax.reload();
                    } else {
                        toastr.error(response.message || 'Gagal menghapus dokumen');
                    }
                },
                error: function() {
                    toastr.error('Terjadi kesalahan sistem');
                }
            });
        }
    });
}

// Initialize attendance table
function initAttendanceTable() {
    const table = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../../api/employees/attendance.php',
            type: 'POST',
            data: function(d) {
                d.employee_id = employeeId;
                d.month = $('#attendanceMonth').val();
                d.year = $('#attendanceYear').val();
            }
        },
        columns: [
            { 
                data: 'tanggal',
                render: function(data) {
                    return moment(data).format('DD/MM/YYYY');
                }
            },
            {
                data: 'status_hadir',
                render: function(data) {
                    const statusClass = {
                        'masuk': 'success',
                        'pulang': 'info',
                        'ijin': 'warning',
                        'sakit': 'danger',
                        'lembur': 'primary',
                        'cuti': 'secondary'
                    }[data] || 'secondary';
                    
                    return `<span class="badge bg-${statusClass}">${data}</span>`;
                }
            },
            {
                data: 'waktu',
                render: function(data) {
                    return data ? moment(data, 'HH:mm:ss').format('HH:mm') : '-';
                }
            },
            { data: 'keterangan' }
        ],
        order: [[0, 'desc'], [2, 'asc']]
    });
    
    // Reload table when month/year changes
    $('#attendanceMonth, #attendanceYear').on('change', function() {
        table.ajax.reload();
    });
}

// Load employee history
function loadHistory() {
    $.get('../../api/employees/history.php', { id: employeeId }, function(data) {
        let html = '';
        
        data.forEach(function(item) {
            const date = moment(item.created_at).format('DD/MM/YYYY HH:mm');
            const typeClass = {
                'create': 'success',
                'update': 'info',
                'delete': 'danger'
            }[item.activity_type] || 'secondary';
            
            html += `
                <div class="timeline-item">
                    <div class="timeline-marker bg-${typeClass}"></div>
                    <div class="timeline-content">
                        <div class="timeline-heading">
                            <span class="badge bg-${typeClass}">${item.activity_type}</span>
                            <small class="text-muted ms-2">${date}</small>
                        </div>
                        <div class="timeline-body">
                            ${item.description}
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('.timeline').html(html || '<p class="text-muted">Tidak ada riwayat</p>');
    });
}

// Initialize delete handler
function initDeleteHandler() {
    $('#confirmDelete').on('click', function() {
        $.ajax({
            url: '../../api/employees/delete.php',
            type: 'POST',
            data: { id: employeeId },
            success: function(response) {
                if (response.success) {
                    toastr.success('Data pegawai berhasil dihapus');
                    
                    // Redirect to list page after 1 second
                    setTimeout(function() {
                        window.location.href = 'list.php';
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Gagal menghapus data pegawai');
                }
            },
            error: function() {
                toastr.error('Terjadi kesalahan sistem');
            },
            complete: function() {
                $('#deleteModal').modal('hide');
            }
        });
    });
}

// Initialize document upload
function initDocumentUpload() {
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('employee_id', employeeId);
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...');
        
        $.ajax({
            url: '../../api/employees/upload-document.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Dokumen berhasil diupload');
                    $('#documentsTable').DataTable().ajax.reload();
                    $('#uploadModal').modal('hide');
                    $('#uploadForm')[0].reset();
                } else {
                    toastr.error(response.message || 'Gagal mengupload dokumen');
                }
            },
            error: function() {
                toastr.error('Terjadi kesalahan sistem');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
}
