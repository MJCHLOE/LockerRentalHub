function searchRentals() {
    const searchInput = document.getElementById('rentalSearchInput').value.toLowerCase();
    const rows = document.getElementById('rentalsTableBody').getElementsByTagName('tr');

    Array.from(rows).forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchInput) ? '' : 'none';
    });
}

function filterRentals(status) {
    const rows = document.getElementById('rentalsTableBody').getElementsByTagName('tr');
    
    Array.from(rows).forEach(row => {
        const rowStatus = row.querySelector('[data-status]')?.dataset.status;
        if (status === 'all' || rowStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    // Update active button state
    document.querySelectorAll('.btn-group button').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(status)) {
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

    // Show loading indicator
    const loadingIndicator = document.createElement('div');
    loadingIndicator.id = 'loadingIndicator';
    loadingIndicator.className = 'position-fixed w-100 h-100 d-flex justify-content-center align-items-center';
    loadingIndicator.style.top = '0';
    loadingIndicator.style.left = '0';
    loadingIndicator.style.backgroundColor = 'rgba(0,0,0,0.5)';
    loadingIndicator.style.zIndex = '9999';
    loadingIndicator.innerHTML = '<div class="spinner-border text-light" role="status"><span class="sr-only">Loading...</span></div>';
    document.body.appendChild(loadingIndicator);

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
        // Remove loading indicator
        document.getElementById('loadingIndicator')?.remove();
        
        if (data.success) {
            // Show success message
            const alertElement = document.createElement('div');
            alertElement.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alertElement.style.top = '20px';
            alertElement.style.right = '20px';
            alertElement.style.zIndex = '9999';
            alertElement.innerHTML = `
                <strong>Success!</strong> Rental status updated successfully.
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            `;
            document.body.appendChild(alertElement);
            
            // Auto close the alert after 3 seconds
            setTimeout(() => {
                alertElement.remove();
                location.reload();
            }, 2000);
        } else {
            alert('Error updating rental status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        // Remove loading indicator
        document.getElementById('loadingIndicator')?.remove();
        
        console.error('Error:', error);
        alert('Error updating rental status. Please try again.');
    });
}