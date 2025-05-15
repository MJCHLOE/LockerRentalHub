/**
 * Clients Management JavaScript for Staff
 * Handles client viewing and searching functionality for the staff dashboard
 */

$(document).ready(function() {
    // Initial load of clients
    refreshClientsTable();

    // Set up search functionality with debounce to optimize performance
    const debouncedSearch = debounce(searchClients, 300); // 300ms delay
    $('#searchInput').on('input', debouncedSearch);
});

/**
 * Debounce function to limit search frequency
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Search clients based on input text
 */
function searchClients() {
    let input = document.getElementById('searchInput');
    let filter = input.value.toLowerCase();
    let tbody = document.getElementById('clientsTableBody');
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
    
    // If no matches were found and this isn't an empty search, show a message
    if (matchedRows.length === 0 && searchInput !== '') {
        const noMatchRow = document.createElement('tr');
        noMatchRow.innerHTML = `<td colspan="5" class="text-center">No clients found matching "${searchInput}"</td>`;
        noMatchRow.classList.add('no-match-row');
        clientsTable.appendChild(noMatchRow);
    } else {
        // Remove any existing "no match" message
        const noMatchRows = clientsTable.getElementsByClassName('no-match-row');
        while (noMatchRows.length > 0) {
            noMatchRows[0].remove();
        }
    }
    
    // Reinitialize pagination if it exists
    if (typeof initPagination === 'function') {
        initPagination('#clientsTableBody', 10); // Assuming 10 items per page
    }

/**
 * Refresh the clients table with updated data
 */
function refreshClientsTable() {
    $.ajax({
        url: '../staff_backend/fetch_clients.php',
        method: 'GET',
        success: function(response) {
            $('#clientsTableBody').html(response);
            
            // Get the current search value and apply the search filter
            const searchInput = document.getElementById('searchInput').value;
            if (searchInput.trim() !== '') {
                searchClients();
            }
            
            // Reinitialize pagination if it exists
            if (typeof initPagination === 'function') {
                initPagination('#clientsTableBody', 10); // Assuming 10 items per page
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching clients:', error);
        }
    });
}

// Refresh clients table every 30 seconds, but only if no search is active
setInterval(function() {
    if ($('#searchInput').val() === '') {
        refreshClientsTable();
    }
}, 30000);