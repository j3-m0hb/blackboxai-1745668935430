document.addEventListener('DOMContentLoaded', function() {
    // Initialize form handling
    initSettingsForm();
    
    // Initialize email testing
    initEmailTest();
    
    // Initialize settings reset
    initSettingsReset();
});

// Initialize settings form
function initSettingsForm() {
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const submitBtn = $(form).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.prop('disabled', true)
                .html('<i class="bi bi-arrow-repeat spin me-1"></i>Saving...');
        
        // Create FormData object
        const formData = new FormData(form);
        
        // Add checkbox values (unchecked checkboxes are not included in FormData)
        $(form).find('input[type="checkbox"]').each(function() {
            if (!this.checked) {
                formData.append(this.name, '0');
            }
        });
        
        $.ajax({
            url: '../../api/system/save-settings.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Settings saved successfully');
                    
                    // Update logo preview if new logo was uploaded
                    if (response.data && response.data.logo) {
                        updateLogoPreview(response.data.logo);
                    }
                    
                    // Update any system-wide settings that need immediate effect
                    if (response.data && response.data.session_timeout) {
                        updateSessionTimeout(response.data.session_timeout);
                    }
                } else {
                    toastr.error(response.message || 'Failed to save settings');
                }
            },
            error: function() {
                toastr.error('System error occurred');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle logo file selection
    $('input[name="logo"]').on('change', function() {
        const file = this.files[0];
        if (file) {
            // Validate file type
            if (!file.type.match('image.*')) {
                toastr.error('Please select an image file');
                this.value = '';
                return;
            }
            
            // Validate file size (max 2MB)
            if (file.size > 2097152) {
                toastr.error('File size must be less than 2MB');
                this.value = '';
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                updateLogoPreview(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
}

// Initialize email test
function initEmailTest() {
    $('#testEmail').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        
        // Get email settings
        const emailSettings = {
            smtp_host: $('input[name="smtp_host"]').val(),
            smtp_port: $('input[name="smtp_port"]').val(),
            smtp_username: $('input[name="smtp_username"]').val(),
            smtp_password: $('input[name="smtp_password"]').val(),
            from_email: $('input[name="from_email"]').val(),
            from_name: $('input[name="from_name"]').val()
        };
        
        // Validate required fields
        if (!emailSettings.smtp_host || !emailSettings.smtp_port || 
            !emailSettings.smtp_username || !emailSettings.smtp_password ||
            !emailSettings.from_email) {
            toastr.error('Please fill in all email settings');
            return;
        }
        
        // Show loading state
        btn.prop('disabled', true)
           .html('<i class="bi bi-arrow-repeat spin me-1"></i>Testing...');
        
        $.ajax({
            url: '../../api/system/test-email.php',
            type: 'POST',
            data: emailSettings,
            success: function(response) {
                if (response.success) {
                    toastr.success('Test email sent successfully');
                } else {
                    toastr.error(response.message || 'Failed to send test email');
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

// Initialize settings reset
function initSettingsReset() {
    $('#resetSettings').on('click', function() {
        if (confirm('Are you sure you want to reset all settings to default values? This cannot be undone.')) {
            const btn = $(this);
            const originalText = btn.html();
            
            // Show loading state
            btn.prop('disabled', true)
               .html('<i class="bi bi-arrow-repeat spin me-1"></i>Resetting...');
            
            $.ajax({
                url: '../../api/system/reset-settings.php',
                type: 'POST',
                success: function(response) {
                    if (response.success) {
                        toastr.success('Settings reset successfully');
                        // Reload page to show default values
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        toastr.error(response.message || 'Failed to reset settings');
                    }
                },
                error: function() {
                    toastr.error('System error occurred');
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        }
    });
}

// Utility Functions

function updateLogoPreview(src) {
    let img = $('input[name="logo"]').siblings('img');
    if (img.length === 0) {
        img = $('<img>', {
            class: 'img-thumbnail mt-2',
            style: 'max-height: 50px'
        });
        $('input[name="logo"]').after(img);
    }
    img.attr('src', src);
}

function updateSessionTimeout(minutes) {
    // Update session timeout warning
    if (window.sessionTimeoutTimer) {
        clearTimeout(window.sessionTimeoutTimer);
    }
    
    window.sessionTimeoutTimer = setTimeout(function() {
        // Show session timeout warning
        const modal = new bootstrap.Modal(document.getElementById('sessionTimeoutModal'));
        modal.show();
    }, (minutes * 60 * 1000) - (5 * 60 * 1000)); // Show warning 5 minutes before timeout
}

// Add spinning animation for loading icons
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
