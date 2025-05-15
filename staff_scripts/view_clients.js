// Function to search clients
function searchClients() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const clientsTable = document.getElementById('clientsTableBody');
    const rows = clientsTable.getElementsByTagName('tr');

    for (let row of rows) {
        const cells = row.getElementsByTagName('td');
        let found = false;

        // Search through each cell in the row
        for (let cell of cells) {
            const text = cell.textContent || cell.innerText;
            if (text.toLowerCase().indexOf(searchInput) > -1) {
                found = true;
                break;
            }
        }

        // Show/hide row based on search result
        row.style.display = found ? '' : 'none';
    }
}

// Function to refresh clients table
function refreshClientsTable() {
    $.ajax({
        url: '../staff_backend/fetch_clients.php',
        method: 'GET',
        success: function(response) {
            $('#clientsTableBody').html(response);
            // Re-apply search filter after table refresh
            searchClients();
        },
        error: function(xhr, status, error) {
            console.error('Error fetching clients:', error);
        }
    });
}

// Debounce function to limit how often searchClients is called
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

// Attach search event listener to search input
document.addEventListener('DOMContentLoaded', function() {
    // Initial load of clients table
    refreshClientsTable();

    // Attach debounced search handler to input event
    const searchInput = document.getElementById('searchInput');
    const debouncedSearch = debounce(searchClients, 300); // 300ms delay
    searchInput.addEventListener('input', debouncedSearch);
});

// Refresh clients table every 30 seconds
setInterval(refreshClientsTable, 30000);