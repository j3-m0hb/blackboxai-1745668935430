document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    initFormValidation();
    
    // Initialize dynamic form behavior
    initDynamicForm();
    
    // Initialize file upload previews
    initFileUploads();
    
    // Handle form submission
    handleFormSubmission();
});

// Initialize form validation
function initFormValidation() {
    const form = document.getElementById('employeeForm');
    
    return $(form).validate({
        rules: {
            nik: {
                required: true,
                minlength: 7,
                maxlength: 7,
                digits: true,
                remote: {
                    url: '../../api/employees/check-nik.php',
                    type: 'post'
                }
            },
            nama_lengkap: {
                required: true,
                minlength: 3
            },
            jabatan: 'required',
            wilayah: 'required',
            status_kerja: 'required',
            tanggal_masuk: 'required',
            tanggal_kontrak: {
                required: function() {
                    return $('select[name="status_kerja"]').val() === 'Kontrak';
                }
            },
            tanggal_hbs_kontrak: {
                required: function() {
                    return $('select[name="status_kerja"]').val() === 'Kontrak';
                },
                greaterThan: '#tanggal_kontrak'
            },
            jatah_cuti: {
                required: true,
                min: 0,
                max: 999
            },
            email: {
                email: true
            },
            no_handphone: {
                minlength: 10,
                maxlength: 15
            },
            no_darurat: {
                minlength: 10,
                maxlength: 15
            },
            pas_photo: {
                extension: 'jpg|jpeg|png',
                filesize: 2097152 // 2MB
            }
        },
        messages: {
            nik: {
                required: 'NIK harus diisi',
                minlength: 'NIK harus 7 digit',
                maxlength: 'NIK harus 7 digit',
                digits: 'NIK harus berupa angka',
                remote: 'NIK sudah terdaftar'
            },
            nama_lengkap: {
                required: 'Nama lengkap harus diisi',
                minlength: 'Nama terlalu pendek'
            },
            jabatan: 'Jabatan harus dipilih',
            wilayah: 'Wilayah harus dipilih',
            status_kerja: 'Status kerja harus dipilih',
            tanggal_masuk: 'Tanggal masuk harus diisi',
            tanggal_kontrak: 'Tanggal kontrak harus diisi',
            tanggal_hbs_kontrak: {
                required: 'Tanggal habis kontrak harus diisi',
                greaterThan: 'Tanggal habis kontrak harus lebih besar dari tanggal kontrak'
            },
            jatah_cuti: {
                required: 'Jatah cuti harus diisi',
                min: 'Minimal 0',
                max: 'Maksimal 999'
            },
            email: 'Format email tidak valid',
            no_handphone: {
                minlength: 'Nomor HP terlalu pendek',
                maxlength: 'Nomor HP terlalu panjang'
            },
            no_darurat: {
                minlength: 'Nomor darurat terlalu pendek',
                maxlength: 'Nomor darurat terlalu panjang'
            },
            pas_photo: {
                extension: 'File harus berformat JPG atau PNG',
                filesize: 'Ukuran file maksimal 2MB'
            }
        },
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

// Initialize dynamic form behavior
function initDynamicForm() {
    // Show/hide contract fields based on status
    $('select[name="status_kerja"]').on('change', function() {
        const showContractFields = $(this).val() === 'Kontrak';
        $('.contract-fields').toggle(showContractFields);
        
        if (showContractFields) {
            $('input[name="tanggal_kontrak"], input[name="tanggal_hbs_kontrak"]').prop('required', true);
        } else {
            $('input[name="tanggal_kontrak"], input[name="tanggal_hbs_kontrak"]').prop('required', false);
        }
    });
    
    // Set minimum date for contract end date
    $('input[name="tanggal_kontrak"]').on('change', function() {
        const minDate = $(this).val();
        $('input[name="tanggal_hbs_kontrak"]').attr('min', minDate);
    });
    
    // Show/hide number of children field based on marital status
    $('select[name="status_person"]').on('change', function() {
        const isMarried = $(this).val() === 'Menikah';
        $('input[name="jumlah_anak"]').prop('readonly', !isMarried);
        if (!isMarried) {
            $('input[name="jumlah_anak"]').val('0');
        }
    });
    
    // Auto-populate bank account name
    $('input[name="nama_lengkap"]').on('change', function() {
        if ($('input[name="nama_rekening"]').val() === '') {
            $('input[name="nama_rekening"]').val($(this).val());
        }
    });
}

// Initialize file upload previews
function initFileUploads() {
    // Photo preview
    $('input[name="pas_photo"]').on('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 2097152) {
                toastr.error('Ukuran file terlalu besar. Maksimal 2MB');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = $('<img>', {
                    src: e.target.result,
                    class: 'img-thumbnail mt-2',
                    style: 'max-height: 200px'
                });
                
                $('.photo-preview').remove();
                $('input[name="pas_photo"]').after(
                    $('<div>', { class: 'photo-preview' }).append(preview)
                );
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Document file validation
    $('input[type="file"]').not('[name="pas_photo"]').on('change', function() {
        const files = this.files;
        let totalSize = 0;
        
        for (let i = 0; i < files.length; i++) {
            totalSize += files[i].size;
            
            // Check file type
            const ext = files[i].name.split('.').pop().toLowerCase();
            if (!['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'].includes(ext)) {
                toastr.error('Format file tidak didukung');
                this.value = '';
                return;
            }
        }
        
        // Check total size (10MB limit)
        if (totalSize > 10485760) {
            toastr.error('Total ukuran file terlalu besar. Maksimal 10MB');
            this.value = '';
            return;
        }
    });
}

// Handle form submission
function handleFormSubmission() {
    $('#employeeForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!$(this).valid()) return;
        
        const formData = new FormData(this);
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
        
        $.ajax({
            url: '../../api/employees/save.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Data pegawai berhasil disimpan');
                    
                    // Redirect to employee list after 1 second
                    setTimeout(function() {
                        window.location.href = 'list.php';
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Gagal menyimpan data pegawai');
                }
            },
            error: function(xhr) {
                toastr.error('Terjadi kesalahan sistem');
                console.error(xhr.responseText);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
}

// jQuery validation plugin custom methods
$.validator.addMethod('greaterThan', function(value, element, param) {
    const startDate = $(param).val();
    return !value || !startDate || new Date(value) > new Date(startDate);
});

$.validator.addMethod('filesize', function(value, element, param) {
    return !element.files[0] || element.files[0].size <= param;
});
