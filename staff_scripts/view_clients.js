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
        },
        error: function(xhr, status, error) {
            console.error('Error fetching clients:', error);
        }
    });
}

// Refresh clients table every 30 seconds
setInterval(refreshClientsTable, 30000);

// Initial load of clients table when page loads
document.addEventListener('DOMContentLoaded', function() {
    refreshClientsTable();
});