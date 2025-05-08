/**
 * Locker Management JavaScript
 * Handles all locker-related functionality for the admin dashboard
 */

// Initialize locker management when document is ready
$(document).ready(function() {
    // Set up edit locker buttons
    $(document).on('click', '.edit-locker', function() {
        const lockerId = $(this).data('id');
        loadLockerData(lockerId);
    });
  
    // Set up delete locker buttons
    $(document).on('click', '.delete-locker', function() {
        const lockerId = $(this).data('id');
        confirmDeleteLocker(lockerId);
    });
  });
  
  /**
  * Load locker data into the edit modal
  * @param {string} lockerId - The ID of the locker to edit
  */
  function loadLockerData(lockerId) {
    $.ajax({
        url: '../admin_backend/edit_locker.php',
        type: 'GET',
        data: { id: lockerId },
        success: function(response) {
            $('#editLockerModalBody').html(response);
            $('#editLockerModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            alert('Error loading locker data: ' + error);
        }
    });
  }
  
  /**
  * Function to be called from the edit button in the table
  * Delegates to loadLockerData
  * @param {string} lockerId - The ID of the locker to edit
  */
  function editLocker(lockerId) {
    loadLockerData(lockerId);
  }
  
  /**
  * Save the edited locker data
  */
  function saveLockerEdit() {
    const formData = new FormData(document.getElementById('editLockerForm'));
    
    $.ajax({
        url: '../admin_backend/edit_locker.php',
        type: 'POST',
        data: new URLSearchParams(formData),
        processData: false,
        contentType: 'application/x-www-form-urlencoded',
        success: function(response) {
            try {
                const data = JSON.parse(response);
                if (data.success) {
                    $('#editLockerModal').modal('hide');
                    // Refresh the lockers table
                    refreshLockerTable();
                    alert('Locker updated successfully');
                } else {
                    alert('Error: ' + (data.error || data.message || 'Unknown error'));
                }
            } catch (e) {
                console.error('Failed to parse JSON response:', e);
                alert('Error processing response');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            alert('Error saving locker data: ' + error);
        }
    });
  }
  
  /**
  * Confirm and delete a locker
  * @param {string} lockerId - The ID of the locker to delete
  */
  function confirmDeleteLocker(lockerId) {
    if (confirm('Are you sure you want to delete this locker? This action cannot be undone.')) {
        $.ajax({
            url: '../admin_backend/delete_locker.php',
            type: 'POST',
            data: { locker_id: lockerId },
            success: function(response) {
                // First check if response is already a JSON object
                if (typeof response === 'object') {
                    handleDeleteResponse(response);
                } else {
                    try {
                        const data = JSON.parse(response);
                        handleDeleteResponse(data);
                    } catch (e) {
                        console.error('Failed to parse JSON response:', e, response);
                        alert('Error processing response');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('Error deleting locker: ' + error);
            }
        });
    }
  }

  /**
  * Handle the delete response
  * @param {Object} data - The response data
  */
  function handleDeleteResponse(data) {
    if (data.success) {
        refreshLockerTable();
        alert(data.message);
    } else {
        alert('Error: ' + (data.message || 'Unknown error'));
    }
  }
  
  /**
  * Refresh the lockers table with updated data
  */
  function refreshLockerTable() {
    $.ajax({
        url: '../admin_backend/fetch_lockers.php',
        type: 'GET',
        success: function(response) {
            $('#lockersTableBody').html(response);
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            alert('Error refreshing locker table: ' + error);
        }
    });
  }
  
  /**
  * Search lockers based on input text
  */
  function searchLockers() {
    const input = document.getElementById('lockerSearchInput');
    const filter = input.value.toUpperCase();
    const tableBody = document.getElementById('lockersTableBody');
    const rows = tableBody.getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const lockerId = rows[i].getElementsByTagName('td')[0];
        const size = rows[i].getElementsByTagName('td')[1];
        
        if (lockerId && size) {
            const lockerIdText = lockerId.textContent || lockerId.innerText;
            const sizeText = size.textContent || size.innerText;
            
            if (lockerIdText.toUpperCase().indexOf(filter) > -1 || 
                sizeText.toUpperCase().indexOf(filter) > -1) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
  }
  
  /**
  * Filter lockers by status
  * @param {string} status - The status to filter by ('All', 'Vacant', 'Occupied', 'Maintenance')
  */
  function filterLockerStatus(status) {
    const tableBody = document.getElementById('lockersTableBody');
    const rows = tableBody.getElementsByTagName('tr');
    
    // Update active filter button
    $('.filter-btn').removeClass('active');
    $(`button[onclick="filterLockerStatus('${status}')"]`).addClass('active');
    
    if (status === 'All') {
        // Show all rows
        for (let i = 0; i < rows.length; i++) {
            rows[i].style.display = '';
        }
        return;
    }
    
    for (let i = 0; i < rows.length; i++) {
        const statusCell = rows[i].getElementsByTagName('td')[2];
        
        if (statusCell) {
            const statusText = statusCell.textContent || statusCell.innerText;
            
            if (statusText.trim() === status) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
  }