function searchClients() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.getElementById('clientsTableBody').getElementsByTagName('tr');
    const filteredRows = [];
    
    Array.from(rows).forEach(row => {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(searchInput);
        
        // If it matches search criteria, add to filtered rows
        if (isVisible) {
            filteredRows.push(row);
        }
    });
    
    // Update pagination with filtered rows
    if (window.clientsPaginator) {
        window.clientsPaginator.updateRows(filteredRows);
    }
}

// Initialize when document is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', searchClients);
    }
});