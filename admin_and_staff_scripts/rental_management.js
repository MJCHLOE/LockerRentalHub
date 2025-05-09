/**
 * Rental Management JavaScript Functions
 */

// Function to handle updating rental status
function updateRentalStatus(rentalId, newStatus) {
    // Confirm before proceeding
    let statusText = {
        'approved': 'approve',
        'denied': 'deny',
        'active': 'activate',
        'completed': 'complete',
        'cancelled': 'cancel'
    };
    
    let confirmAction = confirm(`Are you sure you want to ${statusText[newStatus]} this rental?`);
    if (!confirmAction) {
        return;
    }
    
    // Send AJAX request to update status
    $.ajax({
        url: '../admin_and_staff_backend/update_rental_status.php',
        type: 'POST',
        data: {
            rental_id: rentalId,
            status: newStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message
                alert(response.message);
                
                // Refresh the rentals table
                refreshRentalsTable();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            let errorMessage = xhr.responseJSON ? xhr.responseJSON.message : 'An unknown error occurred';
            alert('Error: ' + errorMessage);
        }
    });
}

// Function to refresh the rentals table
function refreshRentalsTable() {
    $.ajax({
        url: '../admin_and_staff_backend/fetch_rentals.php',
        type: 'GET',
        success: function(html) {
            $('#rentalsTableBody').html(html);
        },
        error: function() {
            alert('Error refreshing rentals table');
        }
    });
}

// Function to search rentals
function searchRentals() {
    let input = document.getElementById('rentalSearchInput');
    let filter = input.value.toUpperCase();
    let table = document.getElementById('rentalsTableBody');
    let tr = table.getElementsByTagName('tr');
    
    for (let i = 0; i < tr.length; i++) {
        let tdId = tr[i].getElementsByTagName('td')[0];
        let tdClient = tr[i].getElementsByTagName('td')[1];
        let tdLocker = tr[i].getElementsByTagName('td')[2];
        
        if (tdId || tdClient || tdLocker) {
            let idText = tdId.textContent || tdId.innerText;
            let clientText = tdClient.textContent || tdClient.innerText;
            let lockerText = tdLocker.textContent || tdLocker.innerText;
            
            if (idText.toUpperCase().indexOf(filter) > -1 || 
                clientText.toUpperCase().indexOf(filter) > -1 || 
                lockerText.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}

// Function to filter rentals by status
function filterRentals(status) {
    // Set active button
    $('.btn-group button').removeClass('active');
    $(event.target).addClass('active');
    
    let table = document.getElementById('rentalsTableBody');
    let tr = table.getElementsByTagName('tr');
    
    for (let i = 0; i < tr.length; i++) {
        if (status === 'all') {
            tr[i].style.display = '';
        } else {
            let dataStatus = tr[i].getAttribute('data-status');
            if (dataStatus === status) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}

// Load rentals when document is ready
$(document).ready(function() {
    // Initial load of rentals
    refreshRentalsTable();
    
    // Set up automatic refresh every 30 seconds
    setInterval(refreshRentalsTable, 30000);
});