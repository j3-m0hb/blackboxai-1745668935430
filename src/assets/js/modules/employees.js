// Initialize DataTable
let employeeTable;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize employee table
    employeeTable = initializeDataTable();
    
    // Initialize filters
    initializeFilters();
    
    // Initialize export buttons
    initializeExport();
    
    // Initialize contract modal
    initializeContractModal();
});

// Initialize DataTable with custom configuration
function initializeDataTable() {
    return $('#employeeTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../../api/employees/list.php',
            type: 'POST',
            data: function(d) {
                d.wilayah = $('#filterWilayah').val();
                d.status = $('#filterStatus').val();
                d.jabatan = $('#filterJabatan').val();
                d.search = $('#searchInput').val();
            }
        },
        columns: [
            { data: 'nik' },
            { data: 'nama_lengkap' },
            { data: 'jabatan' },
            { data: 'wilayah' },
            { 
                data: 'status_kerja',
                render: function(data, type, row) {
                    let statusClass = {
                        'Kontrak': 'warning',
                        'Kartap': 'success',
                        'Freelance': 'info',
                        'Magang': 'secondary',
                        'PHK': 'danger'
                    }[data] || 'primary';
                    
                    return `<span class="badge bg-${statusClass}">${data}</span>`;
                }
            },
            { 
                data: 'masa_kerja',
                render: function(data, type, row) {
                    return calculateWorkDuration(row.tanggal_masuk);
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    if (row.status_kerja === 'Kontrak') {
                        let daysRemaining = calculateDaysRemaining(row.tanggal_hbs_kontrak);
                        let badgeClass = daysRemaining <= 30 ? 'danger' : 
                                       daysRemaining <= 90 ? 'warning' : 'success';
                        
                        return `<button class="btn btn-link btn-sm view-contract" data-id="${row.id}">
                                    <span class="badge bg-${badgeClass}">${daysRemaining} hari</span>
                                </button>`;
                    }
                    return '-';
                }
            },
            {
                data: 'kinerja',
                render: function(data, type, row) {
                    let kinerjaClass = {
                        'Baik': 'success',
                        'Sedang': 'warning',
                        'Biasa': 'info',
                        'Teguran 1': 'danger',
                        'Teguran 2': 'danger',
                        'Teguran 3': 'danger'
                    }[data] || 'secondary';
                    
                    return `<span class="badge bg-${kinerjaClass}">${data}</span>`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <a href="view.php?id=${row.id}" class="btn btn-info" title="Lihat">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="edit.php?id=${row.id}" class="btn btn-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-danger delete-employee" 
                                    data-id="${row.id}" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Tidak ada data yang ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data yang tersedia",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });
}

// Initialize filters
function initializeFilters() {
    // Populate jabatan filter
    $.get('../../api/employees/positions.php', function(data) {
        let options = '<option value="">Semua Jabatan</option>';
        data.forEach(function(position) {
            options += `<option value="${position}">${position}</option>`;
        });
        $('#filterJabatan').html(options);
    });
    
    // Add filter change handlers
    ['#filterWilayah', '#filterStatus', '#filterJabatan'].forEach(function(selector) {
        $(selector).on('change', function() {
            employeeTable.ajax.reload();
        });
    });
    
    // Add search input handler
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            employeeTable.ajax.reload();
        }, 500);
    });
}

// Initialize export functionality
function initializeExport() {
    // Export to CSV
    $('#exportCsv').on('click', function() {
        let params = new URLSearchParams({
            wilayah: $('#filterWilayah').val(),
            status: $('#filterStatus').val(),
            jabatan: $('#filterJabatan').val(),
            search: $('#searchInput').val(),
            format: 'csv'
        });
        
        window.location.href = `../../api/employees/export.php?${params}`;
    });
    
    // Export to PDF
    $('#exportPdf').on('click', function() {
        let params = new URLSearchParams({
            wilayah: $('#filterWilayah').val(),
            status: $('#filterStatus').val(),
            jabatan: $('#filterJabatan').val(),
            search: $('#searchInput').val(),
            format: 'pdf'
        });
        
        window.location.href = `../../api/employees/export.php?${params}`;
    });
}

// Initialize contract modal
function initializeContractModal() {
    $(document).on('click', '.view-contract', function() {
        let employeeId = $(this).data('id');
        
        $.get(`../../api/employees/contract.php?id=${employeeId}`, function(data) {
            let contractHtml = `
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th width="200">Nama</th>
                            <td>${data.nama_lengkap}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Mulai</th>
                            <td>${formatDate(data.tanggal_kontrak)}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Berakhir</th>
                            <td>${formatDate(data.tanggal_hbs_kontrak)}</td>
                        </tr>
                        <tr>
                            <th>Sisa Waktu</th>
                            <td>${calculateDaysRemaining(data.tanggal_hbs_kontrak)} hari</td>
                        </tr>
                        <tr>
                            <th>Status Kinerja</th>
                            <td>${data.kinerja}</td>
                        </tr>
                        <tr>
                            <th>Catatan</th>
                            <td>${data.cat_tindakan || '-'}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="mt-3">
                    <h6>Riwayat Kontrak</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Periode</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.history.map(h => `
                                    <tr>
                                        <td>${formatDate(h.start_date)} - ${formatDate(h.end_date)}</td>
                                        <td>${h.duration}</td>
                                        <td>${h.status}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            $('#contractModal .modal-body').html(contractHtml);
            new bootstrap.Modal('#contractModal').show();
        });
    });
}

// Handle delete employee
$(document).on('click', '.delete-employee', function() {
    let employeeId = $(this).data('id');
    
    $('#deleteModal').data('employee-id', employeeId).modal('show');
});

$('#confirmDelete').on('click', function() {
    let employeeId = $('#deleteModal').data('employee-id');
    
    $.ajax({
        url: '../../api/employees/delete.php',
        type: 'POST',
        data: { id: employeeId },
        success: function(response) {
            if (response.success) {
                toastr.success('Data pegawai berhasil dihapus');
                employeeTable.ajax.reload();
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

// Utility functions
function calculateWorkDuration(startDate) {
    let start = moment(startDate);
    let now = moment();
    let years = now.diff(start, 'years');
    start.add(years, 'years');
    let months = now.diff(start, 'months');
    
    let duration = [];
    if (years > 0) duration.push(years + 'y');
    if (months > 0) duration.push(months + 'm');
    
    return duration.join(' ') || '0m';
}

function calculateDaysRemaining(endDate) {
    return moment(endDate).diff(moment(), 'days');
}

function formatDate(date) {
    return moment(date).format('DD/MM/YYYY');
}
