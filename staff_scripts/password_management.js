// Staff password management functionality
$(document).ready(function() {
    // Initialize the change password modal content
    initChangePasswordModal();
    
    // Set up form submission handler
    $('#passwordChangeForm').on('submit', function(e) {
        e.preventDefault();
        
        const currentPassword = $('#currentPassword').val();
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();
        
        // Reset previous errors
        $('.password-error').text('');
        
        // Client-side validation
        if (currentPassword === '') {
            $('#currentPasswordError').text('Please enter your current password');
            return;
        }
        
        if (newPassword === '') {
            $('#newPasswordError').text('Please enter a new password');
            return;
        }
        
        if (newPassword.length < 6) {
            $('#newPasswordError').text('Password must be at least 6 characters');
            return;
        }
        
        if (confirmPassword === '') {
            $('#confirmPasswordError').text('Please confirm your new password');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            $('#confirmPasswordError').text('Passwords do not match');
            return;
        }
        
        // Show loading state
        $('#changePasswordBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');
        
        // Submit form via AJAX
        $.ajax({
            url: '../staff_backend/change_password.php',
            type: 'POST',
            data: {
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showPasswordChangeAlert('success', response.message);
                    
                    // Reset form and close modal
                    $('#passwordChangeForm')[0].reset();
                    $('#changePasswordModal').modal('hide');
                } else {
                    // Show error message
                    showPasswordChangeAlert('danger', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                showPasswordChangeAlert('danger', 'An error occurred. Please try again later.');
            },
            complete: function() {
                // Reset button state
                $('#changePasswordBtn').prop('disabled', false).text('Change Password');
            }
        });
    });

    // Set up password visibility toggling
    $('.toggle-password').click(function() {
        togglePasswordVisibility($(this).data('target'));
    });
});

/**
 * Initialize the change password modal
 */
function initChangePasswordModal() {
    // Check if modal content already exists before initializing
    if ($('#changePasswordModal').length === 0) {
        const modalHtml = `
            <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="passwordChangeForm">
                                <div class="password-alert"></div>
                                
                                <!-- Current Password -->
                                <div class="form-group">
                                    <label for="currentPassword">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="currentPassword">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small id="currentPasswordError" class="form-text text-danger password-error"></small>
                                </div>
                                
                                <!-- New Password -->
                                <div class="form-group">
                                    <label for="newPassword">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="newPassword">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Minimum 6 characters.</small>
                                    <small id="newPasswordError" class="form-text text-danger password-error"></small>
                                </div>
                                
                                <!-- Confirm New Password -->
                                <div class="form-group">
                                    <label for="confirmPassword">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirmPassword">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small id="confirmPasswordError" class="form-text text-danger password-error"></small>
                                </div>
                                
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary" id="changePasswordBtn">Change Password</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
    }
}

/**
 * Toggle password field visibility
 * @param {string} fieldId - ID of the password field to toggle
 */
function togglePasswordVisibility(fieldId) {
    const field = $('#' + fieldId);
    const fieldType = field.attr('type');
    
    if (fieldType === 'password') {
        field.attr('type', 'text');
        $('.toggle-password[data-target="' + fieldId + '"] i').removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        field.attr('type', 'password');
        $('.toggle-password[data-target="' + fieldId + '"] i').removeClass('fa-eye-slash').addClass('fa-eye');
    }
}

/**
 * Show password change alert message
 * @param {string} type - Alert type (success, danger, warning, info)
 * @param {string} message - Alert message
 */
function showPasswordChangeAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Remove any existing password alerts
    $('.password-alert').empty();
    
    // Add the alert to the form
    $('.password-alert').html(alertHtml);
    
    // Auto dismiss after 3 seconds if it's a success message
    if (type === 'success') {
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }
}

/**
 * Alternative simple password visibility toggle function
 * This can be used as a direct onclick handler in HTML
 */
function togglePasswordVisibilitySimple(inputId) {
    const passwordInput = document.getElementById(inputId);
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
    } else {
        passwordInput.type = 'password';
    }
}