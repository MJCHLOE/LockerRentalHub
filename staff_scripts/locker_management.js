/**
 * Locker Management JavaScript for Staff
 * Handles locker viewing functionality for the staff dashboard
 */

$(document).ready(function() {
    // Initial load of lockers
    refreshLockerTable();

    // Set up search functionality
    $('#lockerSearchInput').on('keyup', function() {
        searchLockers();
    });
});

/**
 * Refresh the lockers table with updated data
 */
function refreshLockerTable() {
    $.ajax({
        url: '../staff_backend/fetch_lockers.php',
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