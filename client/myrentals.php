<?php
// Start session 
session_start();

// Generate a unique session identifier for client
$clientSessionKey = md5('Client_' . $_SESSION['user_id']);

// Check if user is logged in and is client using both regular and role-specific session
if (!isset($_SESSION[$clientSessionKey]) || 
    !isset($_SESSION['role']) || 
    $_SESSION['role'] !== 'Client') {
    header("Location: ../LoginPage.html");
    exit();
}

// Update last activity
$_SESSION[$clientSessionKey]['last_activity'] = time();

// Get client's first name from role-specific session
$firstName = isset($_SESSION[$clientSessionKey]['firstname']) ? 
            $_SESSION[$clientSessionKey]['firstname'] : 'Client';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locker Rental Hub</title>
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="client_dashboard.css">
    <!-- Iconify CDN -->
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
</head>
<body>
    <!-- Add this button before the sidebar -->
    <button id="sidebarToggle" class="sidebar-toggle">
        <iconify-icon icon="mdi:menu" width="24"></iconify-icon>
    </button>

    <!-- Left Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="brand">
            <iconify-icon icon="mdi:lockers" width="24"></iconify-icon>
            <span>Locker Rental Hub</span>
        </div>

        <nav>
            <a href="home.php">
                <iconify-icon icon="mdi:home"></iconify-icon>
                Home
            </a>
            <a href="myrentals.php" class="active">
                <iconify-icon icon="mdi:locker-multiple"></iconify-icon>
                My Rentals
            </a>
            <a href="lockerstorent.php">
                <iconify-icon icon="mdi:locker"></iconify-icon>
                Lockers To Rent
            </a>
            <a href="myrentalhistory.php">
                <iconify-icon icon="mdi:history"></iconify-icon>
                My Rental History
            </a>
            
            <!-- Notification Dropdown -->
            <div class="dropdown">
                <a href="#" class="dropdown-toggle" id="notificationBtn" role="button" aria-haspopup="true" aria-expanded="false" style="display: flex; align-items: center; position: relative;">
                    <iconify-icon icon="mdi:bell" style="font-size: 1.2rem;"></iconify-icon>
                    <span id="notificationBadge" class="badge badge-danger" style="position: absolute; top: -5px; right: 10px; font-size: 0.6rem; display: none;">0</span>
                    <span class="ml-2">Notifications</span>
                </a>
                <div class="dropdown-menu" id="notificationDropdown" aria-labelledby="notificationBtn" style="width: 300px; max-height: 400px; overflow-y: auto;">
                    <h6 class="dropdown-header">Notifications</h6>
                    <div id="notificationList">
                        <!-- Loaded via JS -->
                    </div>
                </div>
            </div>

            <div class="dropdown">
                <a href="#" class="dropdown-toggle" id="accountDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="display: flex; align-items: center;">
                    <?php 
                         $profilePic = (isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic'])) ? $_SESSION['profile_pic'] : 'default_profile.jpg';
                        $profilePicPath = "../client/profile_pics/" . $profilePic;
                        if (!file_exists($profilePicPath)) {
                            $profilePicPath = "../client/profile_pics/default.jpg";
                        }
                        echo '<img src="' . $profilePicPath . '" alt="Profile" class="rounded-circle mr-2" style="width: 30px; height: 30px; object-fit: cover;">';
                    ?>
                    My Account
                </a>
                <div class="dropdown-menu" aria-labelledby="accountDropdown">
                    <a class="dropdown-item" href="profile_details.php">
                        <iconify-icon icon="mdi:card-account-details"></iconify-icon>
                        Details
                    </a>
                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#changePasswordModal">
                        <iconify-icon icon="mdi:key"></iconify-icon>
                        Change Password
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="../backend/logout.php">
                        <iconify-icon icon="mdi:logout"></iconify-icon>
                        Logout
                    </a>
                </div>
            </div>
        </nav>

        <div class="contact-info">
            <p>Contact Us:</p>
            <div class="social-links">
                <a href="#"><iconify-icon icon="mdi:facebook"></iconify-icon></a>
                <a href="#"><iconify-icon icon="mdi:twitter"></iconify-icon></a>
                <a href="#"><iconify-icon icon="mdi:instagram"></iconify-icon></a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4">My Rentals</h2>
        
        <!-- Filter Controls -->
        <div class="filter-group">
            <button class="btn btn-outline-light active filter-btn" onclick="filterRentals('all', this)">All</button>
            <button class="btn btn-outline-warning filter-btn" onclick="filterRentals('pending', this)">Pending</button>
            <button class="btn btn-outline-success filter-btn" onclick="filterRentals('active', this)">Active</button>
            <button class="btn btn-outline-info filter-btn" onclick="filterRentals('completed', this)">Completed</button>
            <button class="btn btn-outline-danger filter-btn" onclick="filterRentals('denied', this)">Denied</button>
            <button class="btn btn-outline-secondary filter-btn" onclick="filterRentals('history', this)">History</button>
        </div>

        <div class="table-container bg-dark text-white p-3 rounded">
            <table class="table table-dark table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Locker ID</th>
                        <th>Size</th>
                        <th>Rental Date</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Time Remaining</th>
                        <th>Status</th>
                        <th>Price/Month</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="rentalsTableBody">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="../client_backend/change_password.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changePasswordModalLabel" style="color: black;">Change Password</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">

                        <!-- Current Password -->
                        <div class="form-group">
                            <label for="currentPassword" style="color: black;">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" onclick="togglePasswordVisibility('currentPassword')">
                                <label class="form-check-label">Show Password</label>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="form-group">
                            <label for="newPassword" style="color: black;">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6">
                            <small class="text-muted" style="color: black;">Minimum 6 characters.</small>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" onclick="togglePasswordVisibility('newPassword')">
                                <label class="form-check-label">Show Password</label>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="form-group">
                            <label for="confirmPassword" style="color: black;">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" onclick="togglePasswordVisibility('confirmPassword')">
                                <label class="form-check-label">Show Password</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" style="color: black;">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" style="color: black;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../client_scripts/dashboard.js"></script>
    <script src="../client_scripts/dropdown.js"></script>
    <script src="../client_scripts/notifications.js"></script>
    <script>
    $(document).ready(function() {
        // Load all rentals initially
        filterRentals('all', $('.filter-btn').first());
    });

    function filterRentals(status, btn) {
        // Update active button
        if(btn) {
            $('.filter-btn').removeClass('active');
            $(btn).addClass('active');
        }

        // Fetch rentals
        $.ajax({
            url: '../client_backend/fetch_all_rentals.php',
            method: 'GET',
            data: { status: status },
            success: function(response) {
                $('#rentalsTableBody').html(response);
                startCountdownTimer();
            }
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
        $('.time-remaining').each(function() {
            const endDateStr = $(this).data('end-date');
            if (!endDateStr) return;
            
            const now = new Date().getTime();
            const endTime = new Date(endDateStr).getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                $(this).removeClass('text-warning text-white').addClass('text-danger font-weight-bold').text('Expired');
            } else {
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                let timeStr = "";
                if (days > 0) timeStr += days + "d ";
                timeStr += hours + "h " + minutes + "m " + seconds + "s";
                
                const className = days < 3 ? 'text-warning font-weight-bold' : 'text-white';
                $(this).removeClass('text-white text-warning text-danger font-weight-bold').addClass(className).text(timeStr);
            }
        });
    }

    function cancelRental(rentalId) {
        if(confirm('Are you sure you want to cancel this rental request?')) {
            $.ajax({
                url: '../client_backend/cancel_rental.php',
                method: 'POST',
                data: { rental_id: rentalId },
                success: function(response) {
                    if(response.success) {
                        showAlert('success', 'Rental request cancelled successfully');
                        // Refresh current view
                        filterRentals($('.filter-btn.active').text().toLowerCase(), null); 
                    } else {
                        showAlert('danger', 'Error: ' + response.message);
                    }
                }
            });
        }
    }

    function terminateRental(rentalId) {
        if(confirm('Are you sure you want to terminate this rental? This action cannot be undone.')) {
            $.ajax({
                url: '../client_backend/terminate_rental.php',
                method: 'POST',
                data: { rental_id: rentalId },
                success: function(response) {
                    if(response.success) {
                        showAlert('success', 'Rental terminated successfully');
                        loadActiveRentals();
                    } else {
                        showAlert('danger', 'Error: ' + response.message);
                    }
                }
            });
        }
    }

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        // Remove any existing alerts
        $('.alert').remove();
        
        // Add the new alert at the top of main-content
        $('.main-content').prepend(alertHtml);
        
        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }

    function togglePasswordVisibility(fieldId) {
        var field = document.getElementById(fieldId);
        if (field.type === "password") {
            field.type = "text";
        } else {
            field.type = "password";
        }
    }
    </script>
</body>
</html>