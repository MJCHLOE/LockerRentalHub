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

    // Custom jQuery selector for contains (case-insensitive)
    jQuery.expr[':'].contains = function(a, i, m) {
        return jQuery(a).text().toUpperCase()
            .indexOf(m[3].toUpperCase()) >= 0;
    };
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
 * Improved implementation based on user_management.js
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
        
        if (id && username && fullName && email && phone) {
            let txtValue = id.textContent + ' ' + 
                          username.textContent + ' ' + 
                          fullName.textContent + ' ' + 
                          email.textContent + ' ' + 
                          phone.textContent;
            
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}

/**
 * Refresh the clients table with updated data and reapply search
 */
function refreshClientsTable() {
    // Show loading indicator
    $('#clientsTableBody').html('<tr><td colspan="5" class="text-center"><div class="spinner-border text-light" role="status"><span class="sr-only">Loading...</span></div></td></tr>');
    
    $.ajax({
        url: '../staff_backend/fetch_clients.php',
        method: 'GET',
        success: function(response) {
            $('#clientsTableBody').html(response);
            
            // Re-apply search filter after table update if search field has content
            if ($('#searchInput').val().trim() !== '') {
                searchClients();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching clients:', error);
            $('#clientsTableBody').html('<tr><td colspan="5" class="text-center text-danger">Error loading clients. Please try again.</td></tr>');
        }
    });
}

/**
 * Function to manually trigger search (can be attached to a button if needed)
 */
function triggerClientSearch() {
    searchClients();
}

// Refresh clients table every 30 seconds
setInterval(refreshClientsTable, 30000);