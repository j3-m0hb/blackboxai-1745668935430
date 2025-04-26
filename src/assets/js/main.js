// Global AJAX Setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': CSRF_TOKEN
    }
});

// Loading Overlay
const loadingOverlay = {
    show: function() {
        $('#loading-overlay').fadeIn(200);
    },
    hide: function() {
        $('#loading-overlay').fadeOut(200);
    }
};

// Confirmation Modal
const confirmationModal = {
    show: function(message, callback) {
        $('#confirmationMessage').text(message);
        $('#confirmButton').off('click').on('click', function() {
            $('#confirmationModal').modal('hide');
            callback();
        });
        $('#confirmationModal').modal('show');
    }
};

// Form Validation
function validateForm(formId, rules = {}) {
    return $(formId).validate({
        rules: rules,
        errorElement: 'div',
        errorClass: 'invalid-feedback',
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        },
        errorPlacement: function(error, element) {
            error.insertAfter(element);
        }
    });
}

// DataTable Default Configuration
const dataTableDefaults = {
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
    },
    responsive: true,
    processing: true,
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]]
};

// Initialize DataTables
function initializeDataTable(selector, options = {}) {
    return $(selector).DataTable({
        ...dataTableDefaults,
        ...options
    });
}

// Format Currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Format Date
function formatDate(date, format = 'DD/MM/YYYY') {
    return moment(date).format(format);
}

// AJAX Form Submit
function submitForm(formId, url, method = 'POST', successCallback = null) {
    $(formId).on('submit', function(e) {
        e.preventDefault();
        
        if (!$(this).valid()) return;
        
        const formData = new FormData(this);
        
        loadingOverlay.show();
        
        $.ajax({
            url: url,
            type: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (successCallback) successCallback(response);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Terjadi kesalahan. Silakan coba lagi.');
                console.error(xhr.responseText);
            },
            complete: function() {
                loadingOverlay.hide();
            }
        });
    });
}

// Delete Record
function deleteRecord(url, id) {
    confirmationModal.show('Apakah Anda yakin ingin menghapus data ini?', function() {
        loadingOverlay.show();
        
        $.ajax({
            url: `${url}/${id}`,
            type: 'DELETE',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    // Reload DataTable if exists
                    if ($.fn.DataTable.isDataTable('.datatable')) {
                        $('.datatable').DataTable().ajax.reload();
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Terjadi kesalahan. Silakan coba lagi.');
                console.error(xhr.responseText);
            },
            complete: function() {
                loadingOverlay.hide();
            }
        });
    });
}

// File Upload Preview
function initFilePreview(inputSelector, previewSelector) {
    $(inputSelector).on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(previewSelector).attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });
}

// Initialize Select2
function initSelect2(selector, options = {}) {
    $(selector).select2({
        theme: 'bootstrap-5',
        width: '100%',
        ...options
    });
}

// Chart.js Default Configuration
const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom'
        }
    }
};

// Create Chart
function createChart(selector, type, data, options = {}) {
    return new Chart(document.querySelector(selector), {
        type: type,
        data: data,
        options: {
            ...chartDefaults,
            ...options
        }
    });
}

// Document Ready
$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();
    
    // Auto-hide alerts
    $('.alert-dismissible').fadeTo(5000, 500).slideUp(500);
    
    // Prevent double form submission
    $('form').on('submit', function() {
        $(this).find(':submit').attr('disabled', 'disabled');
    });
    
    // Add loading state to buttons
    $('.btn-loading').on('click', function() {
        const $btn = $(this);
        const loadingText = $btn.data('loading-text') || 'Loading...';
        $btn.prop('disabled', true).html(
            `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${loadingText}`
        );
    });
});

// Export functions
window.loadingOverlay = loadingOverlay;
window.confirmationModal = confirmationModal;
window.validateForm = validateForm;
window.initializeDataTable = initializeDataTable;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.submitForm = submitForm;
window.deleteRecord = deleteRecord;
window.initFilePreview = initFilePreview;
window.initSelect2 = initSelect2;
window.createChart = createChart;
