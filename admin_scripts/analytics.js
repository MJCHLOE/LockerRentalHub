function updateAnalytics() {
    fetch('../admin_backend/get_analytics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalUsers').textContent = data.data.total_users;
                document.getElementById('totalLockers').textContent = data.data.total_lockers;
                document.getElementById('activeRentals').textContent = data.data.active_rentals;
                document.getElementById('maintenanceCount').textContent = data.data.maintenance_count;
            } else {
                console.error('Error:', data.message);
                setErrorState();
            }
        })
        .catch(error => {
            console.error('Failed to fetch analytics:', error);
            setErrorState();
        });
}

function setErrorState() {
    const elements = ['totalUsers', 'totalLockers', 'activeRentals', 'maintenanceCount'];
    elements.forEach(id => document.getElementById(id).textContent = 'Error');
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    updateAnalytics();
    // Refresh analytics every 5 seconds
    setInterval(updateAnalytics, 5000);
});