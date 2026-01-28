$(document).ready(function () {
    // Poll for notifications every 10 seconds
    fetchNotifications();
    setInterval(fetchNotifications, 10000);

    // Toggle notification dropdown
    $('#notificationBtn').click(function (e) {
        e.stopPropagation();
        $('#notificationDropdown').toggle();
        if ($('#notificationDropdown').is(':visible')) {
            // Optional: Mark all as read when opening? Or just leave them.
        }
    });

    // Close dropdown when clicking outside
    $(document).click(function () {
        $('#notificationDropdown').hide();
    });

    $('#notificationDropdown').click(function (e) {
        e.stopPropagation();
    });
});

function fetchNotifications() {
    $.ajax({
        url: '../backend/fetch_notifications.php',
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                updateNotificationUI(response.unread_count, response.notifications);
            } else {
                console.error("Fetch notifications failed:", response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error (Notifications):", status, error);
            console.log("Response Text:", xhr.responseText);
        }
    });
}

function updateNotificationUI(count, notifications) {
    // Update Badge
    const badge = $('#notificationBadge');
    if (count > 0) {
        badge.text(count);
        badge.show();
    } else {
        badge.hide();
    }

    // Update List
    const list = $('#notificationList');
    list.empty();

    if (notifications.length === 0) {
        list.append('<div class="dropdown-item text-muted text-center">No notifications</div>');
        return;
    }

    notifications.forEach(notif => {
        let icon = 'mdi:information';
        let color = 'text-primary';

        if (notif.title.toLowerCase().includes('approved')) {
            icon = 'mdi:check-circle';
            color = 'text-success';
        } else if (notif.title.toLowerCase().includes('denied')) {
            icon = 'mdi:alert-circle';
            color = 'text-danger';
        } else if (notif.title.toLowerCase().includes('request')) {
            icon = 'mdi:bell-ring';
            color = 'text-warning';
        }

        const isReadClass = notif.is_read == 1 ? 'read' : 'unread';
        const bgClass = notif.is_read == 1 ? '' : 'bg-light'; // Highlight unread

        const item = `
            <div class="dropdown-item ${bgClass} p-3 border-bottom notification-item" onclick="markAsRead(${notif.notification_id})">
                <div class="d-flex align-items-start">
                    <div class="mr-3 mt-1">
                        <iconify-icon icon="${icon}" class="${color}" style="font-size: 1.2rem;"></iconify-icon>
                    </div>
                    <div>
                        <h6 class="mb-1 font-weight-bold" style="font-size: 0.9rem;">${notif.title}</h6>
                        <p class="mb-1 text-secondary" style="font-size: 0.8rem; white-space: normal;">${notif.message}</p>
                        <small class="text-muted" style="font-size: 0.7rem;">${formatTime(notif.created_at)}</small>
                    </div>
                </div>
            </div>
        `;
        list.append(item);
    });

    // Add "Mark all read" button
    if (count > 0) {
        list.append(`
            <div class="dropdown-item text-center p-2 bg-light sticky-bottom">
                <a href="#" onclick="markAllRead(); return false;" class="text-primary small">Mark all as read</a>
            </div>
        `);
    }
}

function markAsRead(id) {
    $.ajax({
        url: '../backend/mark_notification_read.php',
        method: 'POST',
        data: JSON.stringify({ notification_id: id }),
        contentType: 'application/json',
        success: function () {
            fetchNotifications(); // Refresh to update badge
        }
    });
}

function markAllRead() {
    $.ajax({
        url: '../backend/mark_notification_read.php',
        method: 'POST',
        data: JSON.stringify({ mark_all: true }),
        contentType: 'application/json',
        success: function () {
            fetchNotifications();
        }
    });
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}
