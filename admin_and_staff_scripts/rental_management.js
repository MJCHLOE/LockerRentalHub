function searchRentals() {
    const searchInput = document.getElementById('rentalSearchInput').value.toLowerCase();
    const rows = document.getElementById('rentalsTableBody').getElementsByTagName('tr');
    const filteredRows = [];

    Array.from(rows).forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchInput)) {
            filteredRows.push(row);
        }
    });
    
    // Update pagination with filtered rows
    if (window.rentalsPaginator) {
        window.rentalsPaginator.updateRows(filteredRows);
    }
}

function filterRentals(status) {
    const rows = document.getElementById('rentalsTableBody').getElementsByTagName('tr');
    const filteredRows = [];
    
    Array.from(rows).forEach(row => {
        const rowStatus = row.querySelector('[data-status]')?.dataset.status;
        if (status === 'all' || rowStatus === status) {
            filteredRows.push(row);
        }
    });
    
    // Update pagination with filtered rows
    if (window.rentalsPaginator) {
        window.rentalsPaginator.updateRows(filteredRows);
    }

    // Update active button state
    document.querySelectorAll('.btn-group button').forEach(btn => {
        btn.classList.remove('active');
        if (btn.onclick.toString().includes(status)) {
            btn.classList.add('active');
        }
    });
}

function updateRentalStatus(rentalId, newStatus) {
    let confirmMessage = 'Are you sure you want to ';
    switch(newStatus) {
        case 'approved':
            confirmMessage += 'approve';
            break;
        case 'active':
            confirmMessage += 'active';
            break;
        case 'denied':
            confirmMessage += 'deny';
            break;
        case 'cancelled':
            confirmMessage += 'cancel';
            break;
        case 'completed':
            confirmMessage += 'complete';
            break;
        default:
            confirmMessage += 'update';
    }
    confirmMessage += ' this rental?';

    if (!confirm(confirmMessage)) {
        return;
    }

    fetch('../admin_and_staff_backend/update_rental_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            rental_id: rentalId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Rental status updated successfully');
            location.reload();
        } else {
            alert('Error updating rental status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating rental status');
    });
}

// Initialize when document is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for search input
    const searchInput = document.getElementById('rentalSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', searchRentals);
    }
});