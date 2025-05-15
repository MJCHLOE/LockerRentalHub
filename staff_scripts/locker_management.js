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
            
            // Initialize or refresh paginator after loading data
            if (window.lockersPaginator) {
                window.lockersPaginator.rows = Array.from(document.getElementById('lockersTableBody').getElementsByTagName('tr'));
                window.lockersPaginator.filteredRows = [...window.lockersPaginator.rows];
                window.lockersPaginator.setupPagination();
                window.lockersPaginator.showPage(1);
            } else if (document.getElementById('lockersTableBody')) {
                window.lockersPaginator = new TablePaginator('lockersTableBody');
            }
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
    const filteredRows = [];
    
    for (let i = 0; i < rows.length; i++) {
        const lockerId = rows[i].getElementsByTagName('td')[0];
        const size = rows[i].getElementsByTagName('td')[1];
        
        if (lockerId && size) {
            const lockerIdText = lockerId.textContent || lockerId.innerText;
            const sizeText = size.textContent || size.innerText;
            
            if (lockerIdText.toUpperCase().indexOf(filter) > -1 || 
                sizeText.toUpperCase().indexOf(filter) > -1) {
                filteredRows.push(rows[i]);
            }
        }
    }
    
    // Update pagination with filtered rows
    if (window.lockersPaginator) {
        window.lockersPaginator.updateRows(filteredRows);
    }
}

/**
 * Filter lockers by status
 * @param {string} status - The status to filter by ('All', 'Vacant', 'Occupied', 'Maintenance')
 */
function filterLockerStatus(status) {
    const tableBody = document.getElementById('lockersTableBody');
    const rows = tableBody.getElementsByTagName('tr');
    const filteredRows = [];
    
    // Update active filter button
    $('.filter-btn').removeClass('active');
    $(`button[onclick="filterLockerStatus('${status}')"]`).addClass('active');
    
    if (status === 'All') {
        filteredRows.push(...Array.from(rows));
    } else {
        for (let i = 0; i < rows.length; i++) {
            const statusCell = rows[i].getElementsByTagName('td')[2];
            
            if (statusCell) {
                const statusText = statusCell.textContent || statusCell.innerText;
                
                if (statusText.trim() === status) {
                    filteredRows.push(rows[i]);
                }
            }
        }
    }
    
    // Update pagination with filtered rows
    if (window.lockersPaginator) {
        window.lockersPaginator.updateRows(filteredRows);
    }
}