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
    
    // Use the same fetch_clients.php file but with a search parameter
    $.ajax({
        url: '../staff_backend/fetch_clients.php',
        method: 'GET',
        data: { search: searchInput },
        success: function(response) {
            $('#clientsTableBody').html(response);
            
            // Reinitialize pagination if it exists
            if (typeof initPagination === 'function') {
                initPagination('#clientsTableBody', 10); // Assuming 10 items per page
            }
        },
        error: function(xhr, status, error) {
            console.error('Error searching clients:', error);
        }
    });
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