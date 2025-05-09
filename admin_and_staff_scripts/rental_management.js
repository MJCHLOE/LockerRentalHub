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

// Function to update rental status
function updateRentalStatus(rentalId, status) {
    // Confirm before making changes
    if (!confirm(`Are you sure you want to change the status to ${status}?`)) {
        return;
    }
    
    // Call the API to update status
    fetch('api/update_rental_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            rental_id: rentalId,
            status: status,
            operation_type: 'status_update'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showNotification('Success', data.message, 'success');
            
            // Refresh the table
            loadRentals();
        } else {
            // Show error message
            showNotification('Error', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error', 'An unexpected error occurred', 'danger');
    });
}

// Function to update payment status
function updatePaymentStatus(rentalId, newStatus) {
    // Call the API to update payment status
    fetch('api/update_rental_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            rental_id: rentalId,
            payment_status: newStatus,
            operation_type: 'payment_update'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showNotification('Success', data.message, 'success');
            
            // Close the modal
            $('#paymentModal').modal('hide');
            
            // Refresh the table
            loadRentals();
        } else {
            // Show error message
            showNotification('Error', data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error', 'An unexpected error occurred', 'danger');
    });
}

// Function to load rentals data
function loadRentals() {
    fetch('api/fetch_rentals.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('rentalsTableBody').innerHTML = html;
            
            // Re-attach event listeners for payment status cells
            setupPaymentStatusListeners();
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error', 'Unable to load rentals data', 'danger');
        });
}

// Function to setup payment status click listeners
function setupPaymentStatusListeners() {
    const paymentStatusCells = document.querySelectorAll('.payment-status');
    
    paymentStatusCells.forEach(cell => {
        cell.addEventListener('click', function() {
            const rentalId = this.getAttribute('data-rental-id');
            const currentPaymentStatus = this.getAttribute('data-payment');
            
            // Open the payment status modal
            openPaymentModal(rentalId, currentPaymentStatus);
        });
    });
}

// Function to open payment status modal
function openPaymentModal(rentalId, currentStatus) {
    // Set the values in the modal
    document.getElementById('paymentRentalId').value = rentalId;
    document.getElementById('currentPaymentStatus').textContent = currentStatus;
    
    // Set the selected option in the dropdown
    document.getElementById('newPaymentStatus').value = currentStatus;
    
    // Show the modal
    $('#paymentModal').modal('show');
}

// Function for showing notifications
function showNotification(title, message, type) {
    // You can use any notification library here (toastr, sweetalert2, etc.)
    // For this example, we'll assume a Bootstrap toast
    
    const toastHtml = `
        <div class="toast bg-${type}" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
            <div class="toast-header">
                <strong class="mr-auto">${title}</strong>
                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body text-white">
                ${message}
            </div>
        </div>
    `;
    
    const toastContainer = document.getElementById('toastContainer');
    toastContainer.innerHTML = toastHtml;
    $('.toast').toast('show');
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initial load of rentals
    loadRentals();
    
    // Setup event listener for payment form submission
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const rentalId = document.getElementById('paymentRentalId').value;
        const newStatus = document.getElementById('newPaymentStatus').value;
        
        updatePaymentStatus(rentalId, newStatus);
    });
});