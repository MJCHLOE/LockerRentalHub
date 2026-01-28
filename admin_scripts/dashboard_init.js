/**
 * Dashboard Initialization Script
 * Sets up event handlers and initializes dashboard components
 */

$(document).ready(function () {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Set the "All" filter as active by default for lockers
    $('#filter-all').addClass('active');

    // Smooth scrolling for navigation links
    // Smooth scrolling for navigation links
    $('a[href^="#"]').on('click', function (event) {
        const href = this.getAttribute('href');
        if (href === '#' || href.length <= 1) return; // Skip empty links

        const target = $(href);

        if (target.length) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 70
            }, 800);
        }
    });

    // Handle URL hash for direct navigation
    if (window.location.hash) {
        // Scroll to the element after a short delay to ensure page is ready
        setTimeout(function () {
            const target = $(window.location.hash);
            if (target.length) {
                $('html, body').scrollTop(target.offset().top - 70);
            }
        }, 300);
    }

    // Handle alerts from URL parameters
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.has('error')) {
        alert(urlParams.get('error'));
    }

    // Count dashboard statistics
    updateDashboardStats();
});

/**
* Update dashboard statistics
*/
function updateDashboardStats() {
    $.ajax({
        url: '../admin_backend/get_dashboard_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                $('#totalUsers').text(data.totalUsers);
                $('#activeRentals').text(data.activeRentals);
                $('#lockersInMaintenance').text(data.lockersInMaintenance);
            }
        },
        error: function () {
            console.error('Failed to load dashboard statistics');
        }
    });
}