document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        // Hide the toggle button when sidebar is active
        sidebarToggle.style.opacity = sidebar.classList.contains('active') ? '0' : '1';
        sidebarToggle.style.visibility = sidebar.classList.contains('active') ? 'hidden' : 'visible';
    });

    // Close sidebar and show toggle button when clicking outside
    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggle = sidebarToggle.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth <= 768) {
            sidebar.classList.remove('active');
            sidebarToggle.style.opacity = '1';
            sidebarToggle.style.visibility = 'visible';
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            sidebarToggle.style.opacity = '0';
            sidebarToggle.style.visibility = 'hidden';
        } else {
            sidebarToggle.style.opacity = '1';
            sidebarToggle.style.visibility = 'visible';
        }
    });
});

$(document).ready(function() {
    // Load stats immediately
    loadStats();
    
    // Refresh stats every 5 seconds
    setInterval(loadStats, 5000);
});

function loadStats() {
    $.ajax({
        url: '../client_backend/fetch_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Stats response:', response); // Debug log
            if (response.success) {
                $('#activeRentals').text(response.stats.activeRentals || '0');
                $('#pendingRequests').text(response.stats.pendingRequests || '0');
                $('#availableLockers').text(response.stats.availableLockers || '0');
            }
        },
        error: function(xhr, status, error) {
            console.error('Stats error:', error);
            console.log('Response:', xhr.responseText); // Debug log
        }
    });
}