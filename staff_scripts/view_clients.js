/**
 * Clients Management JavaScript for Staff
 * Handles client viewing and searching functionality for the staff dashboard
 */

$(document).ready(function() {
    // Initial load of clients
    refreshClientsTable();

    // Debounce function to limit search frequency
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

    // Set up search functionality with debounce
    const debouncedSearch = debounce(searchClients, 300); // 300ms delay
    $('#searchInput').on('input', debouncedSearch);
});

/**
 * Search clients based on input text
 */
function searchClients() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const clientsTable = document.getElementById('clientsTableBody');
    const rows = clientsTable.getElementsByTagName('tr');

    for (let row of rows) {
        const cells = row.getElementsByTagName('td');
        let found = false;

        // Search through each cell in the row
        for (let cell of cells) {
            const text = (cell.textContent || cell.innerText).trim().toLowerCase();
            if (text.indexOf(searchInput) > -1) {
                found = true;
                break;
            }
        }

        // Show/hide row based on search result
        row.style.display = found ? '' : 'none';
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
            searchClients(); // Re-apply search filter after table update
        },
        error: function(xhr, status, error) {
            console.error('Error fetching clients:', error);
        }
    });
}

// Refresh clients table every 30 seconds
setInterval(refreshClientsTable, 30000);