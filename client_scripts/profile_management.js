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