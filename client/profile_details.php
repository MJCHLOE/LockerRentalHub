<?php
session_start();

// Check if user is logged in and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Client') {
    header('Location: ../LoginPage.html');
    exit();
}

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

require_once '../db/database.php';

// Fetch user details
$userId = $_SESSION['user_id'];
$sql = "SELECT u.username, u.firstname, u.lastname, u.email, u.phone_number, 
               c.full_name as client_name
        FROM users u
        JOIN clients c ON u.user_id = c.user_id
        WHERE u.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userDetails = $result->fetch_assoc();

// Define profile picture path and ensure directory exists
$userId = $_SESSION['user_id'];
$profilePicsDir = realpath("..") . "/profile_pics";

// Make sure the profile_pics directory exists with proper permissions
if (!file_exists($profilePicsDir)) {
    if (!mkdir($profilePicsDir, 0777, true)) {
        error_log("Failed to create profile pics directory: $profilePicsDir");
    } else {
        chmod($profilePicsDir, 0777); // Set permissions
        error_log("Created profile pics directory: $profilePicsDir");
    }
}

$profilePicPath = "$profilePicsDir/user_{$userId}.jpg";
$profilePicUrl = file_exists($profilePicPath) ? "/profile_pics/user_{$userId}.jpg" : "../assets/default_profile.jpg";
error_log("Looking for profile picture at: $profilePicPath");
error_log("Using profile picture URL: $profilePicUrl");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Details - Locker Rental Hub</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="client_dashboard.css">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        .profile-pic-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin-bottom: 20px;
            border-radius: 50%;
            overflow: hidden;
        }
        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-pic-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.7);
            overflow: hidden;
            width: 100%;
            height: 0;
            transition: .5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profile-pic-container:hover .profile-pic-overlay {
            height: 40px;
        }
        .profile-pic-text {
            color: white;
            font-size: 14px;
            cursor: pointer;
        }
        #upload-success-alert, #upload-error-alert {
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Sidebar Toggle Button -->
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
                <a href="#" class="dropdown-toggle active" id="accountDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <iconify-icon icon="mdi:account"></iconify-icon>
                    My Account
                </a>
                <div class="dropdown-menu" aria-labelledby="accountDropdown">
                    <a class="dropdown-item active" href="profile_details.php">
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
        <section id="profile-details" class="mt-4">
            <h2>Profile Details</h2>
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="profile-pic-container mx-auto">
                                <img id="profile-pic" src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="Profile Picture" class="profile-pic">
                                <div class="profile-pic-overlay">
                                    <div class="profile-pic-text" onclick="document.getElementById('profile-pic-upload').click()">
                                        <iconify-icon icon="mdi:camera" width="16"></iconify-icon> Change Photo
                                    </div>
                                </div>
                            </div>
                            <form id="profile-pic-form" enctype="multipart/form-data">
                                <input type="file" id="profile-pic-upload" name="profile_pic" style="display:none;" accept="image/*">
                            </form>
                            <div id="upload-success-alert" class="alert alert-success" role="alert">
                                Profile picture updated successfully!
                            </div>
                            <div id="upload-error-alert" class="alert alert-danger" role="alert">
                                Error uploading profile picture.
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="profile-info">
                                <div class="info-group mb-4">
                                    <h5 class="text-info">Account Information</h5>
                                    <div class="detail-item">
                                        <label>Username:</label>
                                        <span id="username-display"><?php echo htmlspecialchars($userDetails['username']); ?></span>
                                        <button class="btn btn-sm btn-primary ml-3" onclick="toggleEdit('username')">Edit</button>
                                    </div>
                                    <div class="detail-item">
                                        <label>Full Name:</label>
                                        <span id="fullname-display"><?php echo htmlspecialchars($userDetails['firstname'] . ' ' . $userDetails['lastname']); ?></span>
                                        <button class="btn btn-sm btn-primary ml-3" onclick="toggleEdit('fullname')">Edit</button>
                                    </div>
                                </div>

                                <div class="info-group mb-4">
                                    <h5 class="text-info">Contact Information</h5>
                                    <div class="detail-item">
                                        <label>Email:</label>
                                        <span id="email-display"><?php echo htmlspecialchars($userDetails['email']); ?></span>
                                        <button class="btn btn-sm btn-primary ml-3" onclick="toggleEdit('email')">Edit</button>
                                    </div>
                                    <div class="detail-item">
                                        <label>Phone Number:</label>
                                        <span id="phone-display"><?php echo htmlspecialchars($userDetails['phone_number']); ?></span>
                                        <button class="btn btn-sm btn-primary ml-3" onclick="toggleEdit('phone')">Edit</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editProfileForm">
                        <input type="hidden" id="edit-field-type" name="field_type">
                        <div class="form-group">
                            <label id="edit-field-label"></label>
                            <input type="text" class="form-control" id="edit-field-value" name="field_value" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveProfileEdit()">Save Changes</button>
                </div>
            </div>
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
    <script src="../client_scripts/profile_management.js"></script>
    <script>
        $(document).ready(function() {
            // Fix dropdown toggle after item click
            $('.dropdown-item').on('click', function(e) {
                // Prevent propagation for modal triggers to keep dropdown open
                if ($(this).attr('data-toggle') === 'modal') {
                    e.stopPropagation();
                }
            });

            // Ensure dropdown toggle works
            $('#accountDropdown').on('click', function(e) {
                e.preventDefault();
                $(this).parent().toggleClass('show');
                $(this).attr('aria-expanded', $(this).parent().hasClass('show'));
                $(this).next('.dropdown-menu').toggleClass('show');
            });

            // Handle profile picture upload
            $('#profile-pic-upload').change(function() {
                if (this.files && this.files[0]) {
                    var formData = new FormData($('#profile-pic-form')[0]);
                    var reader = new FileReader();
                    
                    // Show preview immediately for better user experience
                    reader.onload = function(e) {
                        $('#profile-pic').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(this.files[0]);
                    
                    // Display loading message
                    $('#upload-success-alert').text('Uploading...').fadeIn();
                    
                    $.ajax({
                        url: '../client_backend/upload_profile_pic.php',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            console.log('Upload response:', response);
                            if (response.success) {
                                // Add a timestamp to prevent browser caching of the old image
                                $('#profile-pic').attr('src', response.newSrc + '?t=' + new Date().getTime());
                                $('#upload-success-alert').text('Profile picture updated successfully!').fadeIn().delay(3000).fadeOut();
                                console.log('Full image path:', response.fullPath);
                            } else {
                                $('#upload-error-alert').text(response.message).fadeIn().delay(3000).fadeOut();
                                console.error('Upload error:', response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#upload-error-alert').text('Server error: ' + error).fadeIn().delay(3000).fadeOut();
                            console.error('Ajax error:', status, error);
                            console.log('Response text:', xhr.responseText);
                        }
                    });
                }
            });
        });

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