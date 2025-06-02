function toggleEdit(field) {
    const modal = $('#editProfileModal');
    const fieldLabel = $('#edit-field-label');
    const fieldValue = $('#edit-field-value');
    const fieldType = $('#edit-field-type');
    
    // Set current value and field type
    fieldType.val(field);
    
    switch(field) {
        case 'username':
            fieldLabel.text('Username:');
            fieldValue.val($('#username-display').text());
            break;
        case 'fullname':
            fieldLabel.text('Full Name:');
            fieldValue.val($('#fullname-display').text());
            break;
        case 'email':
            fieldLabel.text('Email:');
            fieldValue.val($('#email-display').text());
            fieldValue.attr('type', 'email');
            break;
        case 'phone':
            fieldLabel.text('Phone Number:');
            fieldValue.val($('#phone-display').text());
            fieldValue.attr('type', 'tel');
            break;
    }
    
    modal.modal('show');
}

function saveProfileEdit() {
    const fieldType = $('#edit-field-type').val();
    const fieldValue = $('#edit-field-value').val();

    $.ajax({
        url: '../client_backend/update_profile.php',
        method: 'POST',
        data: {
            field_type: fieldType,
            field_value: fieldValue
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Update the displayed value
                $(`#${fieldType}-display`).text(fieldValue);
                $('#editProfileModal').modal('hide');
                
                // Show success message
                showAlert('success', 'Profile updated successfully!');
                
                // Refresh the page content after 1 second
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'An error occurred while updating the profile.');
        }
    });
}

// Add this new function for displaying alerts
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Remove any existing alerts
    $('.alert').remove();
    
    // Add the new alert before the card
    $('.card').before(alertHtml);
    
    // Auto dismiss alert after 3 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}

// Profile Picture Management Functions
$(document).ready(function() {
    // Initialize profile picture functionality
    initProfilePicture();
    
    // Store original image source for error recovery
    const originalSrc = $('#profile-pic').attr('src');
    $('#profile-pic').data('original-src', originalSrc);
});

function initProfilePicture() {
    // Handle profile picture upload with validation and error handling
    $('#profile-pic-upload').change(function() {
        const file = this.files[0];
        
        if (!file) return;
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        const fileType = file.type.toLowerCase();
        
        if (!allowedTypes.includes(fileType)) {
            showAlert('danger', 'Please select a valid image file (JPG, PNG, or GIF)');
            this.value = ''; // Clear the input
            return;
        }
        
        // Validate file size (5MB max)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            showAlert('danger', 'File size is too large. Please select an image under 5MB.');
            this.value = ''; // Clear the input
            return;
        }
        
        const formData = new FormData($('#profile-pic-form')[0]);
        const reader = new FileReader();
        
        // Show preview immediately for better user experience
        reader.onload = function(e) {
            $('#profile-pic').attr('src', e.target.result);
        }
        reader.readAsDataURL(file);
        
        // Hide any previous alerts
        $('#upload-success-alert, #upload-error-alert').hide();
        
        // Show loading state
        showAlert('info', 'Uploading profile picture...');
        
        $.ajax({
            url: '../client_backend/upload_profile_pic.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            timeout: 30000, // 30 second timeout
            success: function(response) {
                console.log('Upload response:', response);
                
                if (response.success) {
                    // Update image with cache busting
                    const timestamp = new Date().getTime();
                    $('#profile-pic').attr('src', response.newSrc + '?t=' + timestamp);
                    
                    // Show success message
                    showAlert('success', 'Profile picture updated successfully!');
                    
                    // Hide the old alerts
                    $('#upload-success-alert, #upload-error-alert').hide();
                } else {
                    // Show error message
                    showAlert('danger', response.message || 'Failed to upload profile picture');
                        
                    // Revert to original image if available
                    revertProfilePicture();
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', status, error);
                console.log('Response text:', xhr.responseText);
                
                let errorMessage = 'Server error occurred while uploading';
                if (status === 'timeout') {
                    errorMessage = 'Upload timeout. Please try again with a smaller image.';
                } else if (xhr.status === 413) {
                    errorMessage = 'File too large. Please select a smaller image.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                }
                
                showAlert('danger', errorMessage);
                
                // Revert to original image
                revertProfilePicture();
            }
        });
    });
}

function revertProfilePicture() {
    const originalSrc = $('#profile-pic').data('original-src');
    if (originalSrc) {
        $('#profile-pic').attr('src', originalSrc);
    }
}

// Optional: Add drag and drop functionality for profile picture
function initDragAndDrop() {
    const profilePicContainer = $('.profile-pic-container');
    
    profilePicContainer.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });
    
    profilePicContainer.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });
    
    profilePicContainer.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            // Validate file before setting
            const file = files[0];
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            
            if (allowedTypes.includes(file.type.toLowerCase())) {
                $('#profile-pic-upload')[0].files = files;
                $('#profile-pic-upload').trigger('change');
            } else {
                showAlert('danger', 'Please drop a valid image file (JPG, PNG, or GIF)');
            }
        }
    });
}

// Function to handle profile picture click (trigger file input)
function triggerProfilePicUpload() {
    $('#profile-pic-upload').click();
}

// Initialize drag and drop when document is ready (uncomment if you want this feature)
// $(document).ready(function() {
//     initDragAndDrop();
// });