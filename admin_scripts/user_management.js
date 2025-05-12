/**
 * User management functionality for admin dashboard
 */

// Search function for users based on input text
function searchUsers() {
    let input = document.getElementById('searchInput');
    let filter = input.value.toLowerCase();
    let tbody = document.getElementById('usersTableBody');
    let tr = tbody.getElementsByTagName('tr');

    for (let i = 0; i < tr.length; i++) {
        let id = tr[i].getElementsByTagName('td')[0];
        let username = tr[i].getElementsByTagName('td')[1];
        let fullName = tr[i].getElementsByTagName('td')[2];
        let email = tr[i].getElementsByTagName('td')[3];
        let phone = tr[i].getElementsByTagName('td')[4];
        let role = tr[i].getElementsByTagName('td')[5];
        
        if (id && username && fullName && email && phone && role) {
            let txtValue = id.textContent + username.textContent + 
                          fullName.textContent + email.textContent + 
                          phone.textContent + role.textContent;
            
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}
  
// Filter function based on role using AJAX
function filterRole(role) {
    // Update active button style
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.classList.remove('active');
    });
    document.getElementById('filter-' + role.toLowerCase()).classList.add('active');
    
    // Show loading indicator
    document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border text-light" role="status"><span class="sr-only">Loading...</span></div></td></tr>';
    
    // Perform AJAX request
    $.ajax({
      url: '../admin_backend/fetch_users.php',
      method: 'GET',
      data: { filter: role === 'All' ? null : role },
      success: function(response) {
        document.getElementById('usersTableBody').innerHTML = response;
      },
      error: function() {
        document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading users. Please try again.</td></tr>';
      }
    });
  }
  
  // Edit user function - Fixed to work properly
  function editUser(userId) {
    // Show loading in the modal
    $('#editUserModal').modal('show');
    $('#editModalBody').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
    
    // Fetch user data with AJAX
    $.ajax({
      url: '../admin_backend/edit_user.php',
      method: 'GET',
      data: { id: userId },
      dataType: 'json',
      success: function(response) {
        if (response.status === 'success') {
          const user = response.user;
          
          // Populate the form fields
          $('#editUserId').val(user.user_id);
          $('#editUsername').val(user.username);
          $('#editFirstname').val(user.firstname);
          $('#editLastname').val(user.lastname);
          $('#editEmail').val(user.email);
          $('#editPhoneNumber').val(user.phone_number);
          $('#editRole').val(user.role);
          
          // Clear the password field (it's optional for updates)
          $('#editPassword').val('');
          
          // Show the form
          $('#editModalBody').html($('#editUserFormTemplate').html());
          
          // Set form values after the HTML has been replaced
          $('#editUserId').val(user.user_id);
          $('#editUsername').val(user.username);
          $('#editFirstname').val(user.firstname);
          $('#editLastname').val(user.lastname);
          $('#editEmail').val(user.email);
          $('#editPhoneNumber').val(user.phone_number);
          $('#editRole').val(user.role);
        } else {
          $('#editModalBody').html(`<div class="alert alert-danger">${response.message}</div>`);
        }
      },
      error: function() {
        $('#editModalBody').html('<div class="alert alert-danger">Error loading user data. Please try again.</div>');
      }
    });
  }
  
  // Submit edit form function
  function submitEditForm() {
    const form = document.getElementById('editUserForm');
    
    // Validate form
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    
    // Submit form
    form.submit();
  }
  
  // Delete user function - IMPROVED
  function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
      // Identify the row to be deleted
      const row = document.querySelector(`tr[data-id="${userId}"]`) || 
                  document.querySelector(`tr td:first-child:contains('User #${userId}')`).parentNode;
      
      // If we still can't find the row, try another approach
      if (!row) {
        const allRows = document.querySelectorAll('#usersTableBody tr');
        for (let i = 0; i < allRows.length; i++) {
          if (allRows[i].innerHTML.includes(`'deleteUser(${userId})'`) || 
              allRows[i].innerHTML.includes(`"deleteUser(${userId})"`)) {
            row = allRows[i];
            break;
          }
        }
      }
      
      // Store original content in case of error
      const oldHtml = row ? row.innerHTML : null;
      
      // Show loading indicator if row was found
      if (row) {
        row.innerHTML = '<td colspan="7" class="text-center"><div class="spinner-border text-light" role="status"><span class="sr-only">Loading...</span></div></td>';
      }
      
      // Send AJAX request to delete the user
      $.ajax({
        url: '../admin_backend/delete_user.php',
        method: 'POST',
        data: { user_id: userId },
        dataType: 'json',
        success: function(response) {
          if (response.status === 'success') {
            // Remove the row from the table if found
            if (row) {
              row.remove();
            } else {
              // If row wasn't found, refresh the entire table
              filterRole(document.querySelector('.filter-btn.active').textContent.trim());
            }
            alert(response.message);
          } else {
            // Restore the row and show error if row was found
            if (row && oldHtml) {
              row.innerHTML = oldHtml;
            }
            alert(response.message || 'Error deleting user.');
          }
        },
        error: function(xhr) {
          console.error('Delete error:', xhr.responseText);
          // Restore the row and show error if row was found
          if (row && oldHtml) {
            row.innerHTML = oldHtml;
          }
          
          let errorMsg = 'Error deleting user. Please try again.';
          try {
            // Try to parse error message from response
            const response = JSON.parse(xhr.responseText);
            if (response && response.message) {
              errorMsg = response.message;
            }
          } catch (e) {
            // Use default error message if parsing fails
          }
          
          alert(errorMsg);
        }
      });
    }
  }
  
  // Initialize the user management functionality
  $(document).ready(function() {
    // Set the "All" button as active by default
    document.getElementById('filter-all').classList.add('active');
    
    // Custom jQuery selector for contains (case-insensitive)
    jQuery.expr[':'].contains = function(a, i, m) {
      return jQuery(a).text().toUpperCase()
          .indexOf(m[3].toUpperCase()) >= 0;
    };
  });