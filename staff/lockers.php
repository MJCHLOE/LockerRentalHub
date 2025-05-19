<?php
session_start();

// Generate a unique session identifier for staff
$staffSessionKey = md5('Staff_' . $_SESSION['user_id']);

// Check if user is logged in and is staff using both regular and role-specific session
if (!isset($_SESSION[$staffSessionKey] ) || 
    !isset($_SESSION['role']) || 
    $_SESSION['role'] !== 'Staff') {
    header("Location: ../LoginPage.html");
    exit();
}

// Update last activity
$_SESSION[$staffSessionKey]['last_activity'] = time();

if (isset($_GET['success'])) {
    echo "<script>alert('".$_GET['success']."');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Locker Rental Hub</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="staff_dashboard.css">
    <link rel="stylesheet" href="pagination.css">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">
            <iconify-icon icon="mdi:lockers" width="24"></iconify-icon>
            Staff Dashboard
        </a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">View Clients</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="lockers.php">View Lockers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_rentals.php">Manage Rentals</a>
                </li>
                <!-- My Account Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="accountDropdown" role="button" data-toggle="dropdown">
                        My Account
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountDropdown">
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#changePasswordModal">Change Password</a>
                        <a class="dropdown-item" href="../backend/logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Change Password Modal directly in HTML -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel" style="color: black;">Change Password</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="passwordChangeForm">
                        <div class="password-alert"></div>
                        
                        <!-- Current Password -->
                        <div class="form-group">
                            <label for="currentPassword" style="color: black;">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="currentPassword" name="current_password" required style="color: black;">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="currentPassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <small id="currentPasswordError" class="form-text text-danger password-error" style="color: red !important;"></small>
                        </div>
                        
                        <!-- New Password -->
                        <div class="form-group">
                            <label for="newPassword" style="color: black;">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6" style="color: black;">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="newPassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted" style="color: black !important;">Minimum 6 characters.</small>
                            <small id="newPasswordError" class="form-text text-danger password-error" style="color: red !important;"></small>
                        </div>
                        
                        <!-- Confirm New Password -->
                        <div class="form-group">
                            <label for="confirmPassword" style="color: black;">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required style="color: black;">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirmPassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <small id="confirmPasswordError" class="form-text text-danger password-error" style="color: red !important;"></small>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" id="changePasswordBtn">Change Password</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-5">

        <!-- View Lockers Section -->
        <section id="view-lockers" class="my-4">
            <h3>View Lockers</h3>
            <p>Current locker status and availability</p>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <input type="text" id="lockerSearchInput" class="form-control w-25" 
                       placeholder="Search by Locker ID or Size">
                
                <div class="btn-group">
                    <button class="btn btn-primary filter-btn active" onclick="filterLockerStatus('All')">All</button>
                    <button class="btn btn-success filter-btn" onclick="filterLockerStatus('Vacant')">Vacant</button>
                    <button class="btn btn-info filter-btn" onclick="filterLockerStatus('Occupied')">Occupied</button>
                    <button class="btn btn-warning filter-btn" onclick="filterLockerStatus('Maintenance')">Maintenance</button>
                </div>
            </div>

            <div class="table-container">
                <div class="table-responsive bg-dark text-white p-3 rounded">
                    <table class="table table-dark table-bordered">
                        <thead>
                            <tr>
                                <th>Locker ID</th>
                                <th>Size</th>
                                <th>Status</th>
                                <th>Price/Month</th>
                            </tr>
                        </thead>
                        <tbody id="lockersTableBody">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination will be added here by JavaScript -->
            </div>
        </section>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../admin_and_staff_scripts/rental_management.js"></script>
    <script src="../staff_scripts/locker_management.js"></script>
    <script src="../staff_scripts/view_clients.js"></script>
    <script src="../staff_scripts/pajination.js"></script>

    <!-- Password management script -->
    <script>
    // Add the necessary JavaScript for password functionality
    $(document).ready(function() {
        // Set up form submission handler
        $('#passwordChangeForm').on('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = $('#currentPassword').val();
            const newPassword = $('#newPassword').val();
            const confirmPassword = $('#confirmPassword').val();
            
            // Reset previous errors
            $('.password-error').text('');
            
            // Client-side validation
            if (currentPassword === '') {
                $('#currentPasswordError').text('Please enter your current password');
                return;
            }
            
            if (newPassword === '') {
                $('#newPasswordError').text('Please enter a new password');
                return;
            }
            
            if (newPassword.length < 6) {
                $('#newPasswordError').text('Password must be at least 6 characters');
                return;
            }
            
            if (confirmPassword === '') {
                $('#confirmPasswordError').text('Please confirm your new password');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                $('#confirmPasswordError').text('Passwords do not match');
                return;
            }
            
            // Show loading state
            $('#changePasswordBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');
            
            // Submit form via AJAX
            $.ajax({
                url: '../staff_backend/change_password.php',
                type: 'POST',
                data: {
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showPasswordChangeAlert('success', response.message);
                        
                        // Reset form and close modal
                        $('#passwordChangeForm')[0].reset();
                        $('#changePasswordModal').modal('hide');
                    } else {
                        // Show error message
                        showPasswordChangeAlert('danger', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    showPasswordChangeAlert('danger', 'An error occurred. Please try again later.');
                },
                complete: function() {
                    // Reset button state
                    $('#changePasswordBtn').prop('disabled', false).text('Change Password');
                }
            });
        });

        // Set up password visibility toggling
        $('.toggle-password').click(function() {
            const targetId = $(this).data('target');
            const passwordInput = $('#' + targetId);
            const fieldType = passwordInput.attr('type');
            
            if (fieldType === 'password') {
                passwordInput.attr('type', 'text');
                $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordInput.attr('type', 'password');
                $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });

    /**
     * Show password change alert message
     * @param {string} type - Alert type (success, danger, warning, info)
     * @param {string} message - Alert message
     */
    function showPasswordChangeAlert(type, message) {
        let textColor = 'black';
        if (type === 'danger') textColor = 'red';
        if (type === 'success') textColor = 'green';
        
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert" style="color: ${textColor} !important;">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        // Remove any existing password alerts
        $('.password-alert').empty();
        
        // Add the alert to the form
        $('.password-alert').html(alertHtml);
        
        // Auto dismiss after 3 seconds if it's a success message
        if (type === 'success') {
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 3000);
        }
    }
    </script>
</body>
</html>