// Password modal functionality
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
            url: '../client_backend/client_change_password.php',
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
});

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
    $('.password-alert').remove();
    
    // Add the alert to the form
    $('#passwordChangeForm').prepend(alertHtml);
    
    // Auto dismiss after 3 seconds if it's a success message
    if (type === 'success') {
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }
}