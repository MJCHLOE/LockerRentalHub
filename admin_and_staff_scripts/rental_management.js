function searchRentals() {
    const searchInput = document.getElementById('rentalSearchInput').value.toLowerCase();
    const rows = document.getElementById('rentalsTableBody').getElementsByTagName('tr');

    Array.from(rows).forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchInput) ? '' : 'none';
    });
}

function filterRentals(status) {
    // Update active button state
    document.querySelectorAll('#rentalFilters .btn').forEach(btn => {
        btn.classList.remove('active');
        // Simple check if the button text triggers this status
        if (btn.onclick.toString().includes(status)) {
            btn.classList.add('active');
        }
    });

    const tbody = document.getElementById('rentalsTableBody');
    tbody.innerHTML = '<tr><td colspan="10" class="text-center"><div class="spinner-border text-light" role="status"></div></td></tr>';

    // REMOVED: type (archive/active) logic.
    // We now just pass the filter to the backend which handles the UNION.

    fetch(`../admin_and_staff_backend/fetch_rentals.php?filter=${status}`)
        .then(response => response.text())
        .then(html => {
            tbody.innerHTML = html;
            startCountdownTimer();
        })
        .catch(error => {
            console.error('Error filtering rentals:', error);
            tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Error loading data</td></tr>';
        });
}

function loadRentals(type) {
    // NOTE: This function's argument 'type' is now ignored as we unified the table.
    // It's kept or aliased to simple refresh calls if needed, but the buttons calling it are gone.
    // However, if called programmatically, we treat it as a general refresh.
    
    // We default status to 'all' on initial load.
    filterRentals('all');
}

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    loadRentals('all');
});

function updateRentalStatus(rentalId, newStatus) {
    let confirmMessage = 'Are you sure you want to ';
    switch (newStatus) {
        case 'approved':
            confirmMessage += 'approve';
            break;
        case 'active':
            confirmMessage += 'activate';
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
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert(data.message);
                    
                    // Reload current view based on active filter
                    const activeFilterBtn = document.querySelector('#rentalFilters .btn.active');
                    let status = 'all';
                    if (activeFilterBtn) {
                        const onClick = activeFilterBtn.getAttribute('onclick');
                        if (onClick) {
                            const match = onClick.match(/'([^']+)'/);
                            if (match) status = match[1];
                        }
                    }

                    filterRentals(status); 
                } else {
                    alert('Error updating rental status: ' + data.message);
                }
            } catch (e) {
                console.error('Server Error:', text);
                alert('Server Error: ' + text.substring(0, 500));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating rental status: ' + error + '\n(Check console for details)');
        });
}

let countdownInterval;
function startCountdownTimer() {
    if (countdownInterval) clearInterval(countdownInterval);

    // Run immediately
    updateTimers();

    countdownInterval = setInterval(updateTimers, 1000);
}

function updateTimers() {
    // Select all elements with class time-remaining
    const timers = document.querySelectorAll('.time-remaining');

    timers.forEach(timer => {
        const endDateStr = timer.getAttribute('data-end-date');
        if (!endDateStr) return;

        const now = new Date().getTime();
        const endTime = new Date(endDateStr).getTime();
        const distance = endTime - now;

        if (distance < 0) {
            timer.className = 'text-danger font-weight-bold';
            timer.textContent = 'Expired';
        } else {
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            let timeStr = "";
            if (days > 0) timeStr += days + "d ";
            timeStr += hours + "h " + minutes + "m " + seconds + "s";

            // Text color based on urgency
            if (days < 3) {
                timer.className = 'text-warning font-weight-bold';
            } else {
                timer.className = ''; // Default inherit
            }
            timer.textContent = timeStr;
        }
    });
}