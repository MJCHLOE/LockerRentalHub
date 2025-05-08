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
    if (!confirm(`Are you sure you want to ${newStatus} this rental?`)) {
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