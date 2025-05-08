// Function to load logs
function loadLogs(filter = 'all', search = '') {
    $.ajax({
        url: '../admin_backend/fetch_logs.php',
        type: 'GET',
        data: {
            filter: filter,
            search: search
        },
        success: function(response) {
            $('#logsTableBody').html(response);
        },
        error: function(xhr, status, error) {
            console.error("Error loading logs:", error);
            $('#logsTableBody').html('<tr><td colspan="5" class="text-center">Error loading logs</td></tr>');
        }
    });
}

// Function to filter logs
function filterLogs(filter) {
    $('.btn-group button').removeClass('active');
    $(`.btn-group button[onclick="filterLogs('${filter}')"]`).addClass('active');
    const searchTerm = $('#logSearchInput').val();
    loadLogs(filter, searchTerm);
}

// Search function
function searchLogs() {
    const searchTerm = $('#logSearchInput').val();
    const currentFilter = $('.btn-group button.active').attr('onclick').match(/'(.*?)'/)[1];
    loadLogs(currentFilter, searchTerm);
}

// Initialize when document is ready
$(document).ready(function() {
    // Initial load of logs
    loadLogs();

    // Add search input event listener
    $('#logSearchInput').on('keyup', function() {
        searchLogs();
    });
});