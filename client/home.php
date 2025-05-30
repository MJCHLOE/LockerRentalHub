<?php
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
            <a href="home.php" class="active">
                <iconify-icon icon="mdi:home"></iconify-icon>
                Home
            </a>
            <a href="myrentals.php">
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
            <div class="dropdown">
                <a href="#" class="dropdown-toggle" id="accountDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <iconify-icon icon="mdi:account"></iconify-icon>
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
        <section id="home">
            <div class="welcome-banner">
                <h1>Welcome Back, <span class="client-name"><?php echo htmlspecialchars($firstName); ?></span>!</h1>
                <p class="welcome-subtitle">Access your locker rentals and manage your account</p>
            </div>
        
            <p>This is where you manage your locker rentals, view available lockers, and update your profile</p>

            <!-- Quick Stats -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <iconify-icon icon="mdi:key" width="32"></iconify-icon>
                    </div>
                    <h3>Active Rentals</h3>
                    <p class="stat-number" id="activeRentals">0</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <iconify-icon icon="mdi:clock-outline" width="32"></iconify-icon>
                    </div>
                    <h3>Pending Requests</h3>
                    <p class="stat-number" id="pendingRequests">0</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <iconify-icon icon="mdi:locker" width="32"></iconify-icon>
                    </div>
                    <h3>Available Lockers</h3>
                    <p class="stat-number" id="availableLockers">0</p>
                </div>
            </div>
        </section>

    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="../client_backend/change_password.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changePasswordModalLabel" style="color: black;">Change Password</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
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
    <script src="../client_scripts/password_modal.js"></script>
    
    <script>
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