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
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    
    // Get all rows from the table
    const clientsTable = document.getElementById('clientsTableBody');
    const rows = clientsTable.getElementsByTagName('tr');
    
    // Track which rows match the search
    let matchedRows = [];
    let noMatchCount = 0;
    
    // Loop through all table rows to find matches
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        // Skip header rows or rows with no cells
        if (cells.length === 0) continue;
        
        // Check if this is a "No clients found" message row
        if (cells.length === 1 && cells[0].getAttribute('colspan')) {
            row.style.display = 'none';
            continue;
        }
        
        // Search through all cells in the row
        for (let j = 0; j < cells.length; j++) {
            const cellText = cells[j].textContent || cells[j].innerText;
            
            if (cellText.toLowerCase().indexOf(searchInput) > -1) {
                found = true;
                break;
            }
        }
        
        // Show/hide row based on search result
        if (found || searchInput === '') {
            row.style.display = '';
            matchedRows.push(row);
        } else {
            row.style.display = 'none';
            noMatchCount++;
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