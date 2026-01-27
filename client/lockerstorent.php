<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require '../db/database.php';

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

// Get user ID
$user_id = (int)$_SESSION['user_id'];

// Pagination settings
$lockersPerPage = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $lockersPerPage;

// Filter parameters
$sizeFilter = isset($_GET['size']) ? $_GET['size'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build WHERE clause
$whereClauses = [];
if ($sizeFilter != '') {
    $sizeFilter = $conn->real_escape_string($sizeFilter);
    $whereClauses[] = "size = '$sizeFilter'";
}
if ($statusFilter != '') {
    $statusFilter = $conn->real_escape_string($statusFilter);
    $whereClauses[] = "status = '$statusFilter'";
}
$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Count total lockers with filters
$countQuery = "SELECT COUNT(*) as total FROM lockers $whereSql";
$countResult = $conn->query($countQuery);
$totalLockers = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalLockers / $lockersPerPage);

// Handle invalid page numbers
if ($totalLockers > 0 && $page > $totalPages) {
    header("Location: ?" . http_build_query(array_merge($_GET, ['page' => $totalPages])));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lockers to Rent - Locker Rental Hub</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="client_dashboard.css">
    <link rel="stylesheet" href="locker_grids.css">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
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
            <a href="lockerstorent.php" class="active">
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

    <div class="main-content">
        <h2 class="mb-4">Available Lockers</h2>
        
        <!-- Filter Section -->
        <div class="filters mb-4">
            <div class="row">
                <div class="col-md-3">
                    <select class="form-control" id="sizeFilter">
                        <option value="" <?php if ($sizeFilter == '') echo 'selected'; ?>>All Sizes</option>
                        <option value="Small" <?php if ($sizeFilter == 'Small') echo 'selected'; ?>>Small</option>
                        <option value="Medium" <?php if ($sizeFilter == 'Medium') echo 'selected'; ?>>Medium</option>
                        <option value="Large" <?php if ($sizeFilter == 'Large') echo 'selected'; ?>>Large</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="statusFilter">
                        <option value="" <?php if ($statusFilter == '') echo 'selected'; ?>>All Status</option>
                        <option value="Vacant" <?php if ($statusFilter == 'Vacant') echo 'selected'; ?>>Vacant</option>
                        <option value="Occupied" <?php if ($statusFilter == 'Occupied') echo 'selected'; ?>>Occupied</option>
                        <option value="Maintenance" <?php if ($statusFilter == 'Maintenance') echo 'selected'; ?>>Maintenance</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Locker Grid -->
        <div class="locker-grid" id="lockerGrid">
            <?php
            // Updated SQL query to include reservation checks and previous rentals
            // Updated SQL query to match schema: lockers has direct enum columns for size and status
            $query = "SELECT locker_id, size as size_name, status as status_name, price as price_per_month,
                            (SELECT COUNT(*) FROM rentals r WHERE r.locker_id = lockers.locker_id AND r.status = 'pending') as reservation_count,
                            (SELECT COUNT(*) FROM rentals r WHERE r.locker_id = lockers.locker_id AND r.user_id = $user_id AND r.status IN ('approved', 'active', 'completed')) as has_rented_before,
                            (SELECT COUNT(*) FROM rentals r WHERE r.locker_id = lockers.locker_id AND r.user_id = $user_id AND r.status = 'pending') as has_pending_reservation
                    FROM lockers
                    $whereSql
                    ORDER BY locker_id
                    LIMIT $lockersPerPage OFFSET $offset";

            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $statusClass = "status-" . strtolower($row['status_name']);
                    $reservation_count = $row['reservation_count'];
                    $has_rented_before = $row['has_rented_before'];
                    $has_pending_reservation = $row['has_pending_reservation'];
                    ?>
                    <div class="locker-card <?php echo $statusClass; ?>" 
                        data-size="<?php echo $row['size_name']; ?>"
                        data-status="<?php echo $row['status_name']; ?>"
                        data-locker-id="<?php echo $row['locker_id']; ?>"
                        data-has-rented-before="<?php echo $has_rented_before; ?>"
                        data-has-pending-reservation="<?php echo $has_pending_reservation; ?>">
                        <div class="locker-icon">
                            <iconify-icon icon="mdi:locker" width="48"></iconify-icon>
                        </div>
                        <div class="locker-details">
                            <h4><?php echo $row['locker_id']; ?></h4>
                            <p><?php echo $row['size_name']; ?></p>
                            <p class="status"><?php echo $row['status_name']; ?></p>
                            <!-- Display reservation count only for Reserved lockers with pending reservations -->
                            <?php if ($row['status_name'] == 'Reserved' && $reservation_count > 0): ?>
                                <p class="reservation-count">Reserved by <?php echo $reservation_count; ?> users</p>
                            <?php endif; ?>
                            <p class="price">₱<?php echo number_format($row['price_per_month'], 2); ?>/month</p>
                            
                            <?php 
                            // Check conditions for showing rent button or messages
                            $canRent = false;
                            $message = '';
                            $buttonText = '';
                            
                            if ($has_rented_before > 0) {
                                $message = 'You have previously rented this locker and cannot rent it again.';
                            } elseif ($has_pending_reservation > 0) {
                                $message = 'You already have a pending reservation for this locker.';
                            } elseif ($row['status_name'] == 'Vacant') {
                                $canRent = true;
                                $buttonText = 'Rent Now';
                            } elseif ($row['status_name'] == 'Reserved') {
                                $canRent = true;
                                $buttonText = 'Request Reservation';
                            } elseif ($row['status_name'] == 'Occupied') {
                                $message = 'This locker is currently occupied';
                            } else {
                                $message = 'This locker is not available';
                            }
                            
                            if ($canRent): ?>
                                <button class="btn btn-success btn-sm" 
                                        onclick="rentLocker('<?php echo $row['locker_id']; ?>')">
                                    <?php echo $buttonText; ?>
                                </button>
                            <?php else: ?>
                                <p class="status-message"><?php echo $message; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<div class='col-12 text-center'>No lockers available</div>";
            }
            ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <!-- Previous Button -->
                    <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                        <a class="page-link" href="<?php if ($page > 1) echo '?' . http_build_query(array_merge($_GET, ['page' => $page - 1])); else echo '#'; ?>" aria-label="Previous">
                            <span aria-hidden="true">« Previous</span>
                        </a>
                    </li>
                    
                    <?php
                    $visiblePages = 5; // Number of visible page links
                    $start = max(1, $page - floor($visiblePages / 2));
                    $end = min($totalPages, $start + $visiblePages - 1);
                    $start = max(1, $end - $visiblePages + 1); // Adjust start if end is near totalPages

                    // Always show the first page and ellipsis if necessary
                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                        if ($start > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    // Display the range of pages
                    for ($i = $start; $i <= $end; $i++) {
                        echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                        echo '<a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a>';
                        echo '</li>';
                    }

                    // Always show the last page and ellipsis if necessary
                    if ($end < $totalPages) {
                        if ($end < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Next Button -->
                    <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                        <a class="page-link" href="<?php if ($page < $totalPages) echo '?' . http_build_query(array_merge($_GET, ['page' => $page + 1])); else echo '#'; ?>" aria-label="Next">
                            <span aria-hidden="true">Next »</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Rental Request Modal -->
    <div class="modal fade" id="rentalRequestModal" tabindex="-1" role="dialog" aria-labelledby="rentalRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="rentalRequestModalLabel" style="color: #000;">Rental Request</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="locker-details-container p-3 bg-light rounded mb-3">
                        <h6 class="font-weight-bold" style="color: #000;">Locker Details:</h6>
                        <p style="color: #000;"><strong>Locker ID:</strong> <span id="modalLockerId"></span></p>
                        <p style="color: #000;"><strong>Size:</strong> <span id="modalLockerSize"></span></p>
                        <p style="color: #000;"><strong>Price:</strong> ₱<span id="modalLockerPrice"></span>/month</p>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmRental">Submit Request</button>
                </div>
                <div class="modal-payment-instructions p-3 bg-info text-white">
                    <h6 class="font-weight-bold mb-3">Payment Instructions:</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">• Please proceed to the rental spot's cashier for payment</li>
                        <li class="mb-2">• Payment must be completed within approval</li>
                        <li class="mb-2">• Your rental status will remain 'Pending Payment' until paid</li>
                        <li>• The locker will be reserved for you during this period</li>
                    </ul>
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../client_scripts/dashboard.js"></script>
    <script src="../client_scripts/dropdown.js"></script>
    <script src="../client_scripts/notifications.js"></script>
    
    <script>
        $(document).ready(function() {
            // Update filters to reload page with parameters
            $('#sizeFilter, #statusFilter').on('change', function() {
                const size = $('#sizeFilter').val();
                const status = $('#statusFilter').val();
                const params = new URLSearchParams();
                if (size) params.append('size', size);
                if (status) params.append('status', status);
                params.append('page', 1);
                window.location.search = params.toString();
            });

            // Update click handler for locker cards
            $('.locker-card').on('click', function() {
                const status = $(this).data('status');
                const hasRentedBefore = $(this).data('has-rented-before');
                const hasPendingReservation = $(this).data('has-pending-reservation');
                
                if (status !== 'Vacant' && status !== 'Reserved') {
                    const message = status === 'Occupied' ? 
                        'This locker is currently occupied.' : 
                        'You cannot rent this locker.';
                    alert(message);
                } else if (hasRentedBefore > 0) {
                    alert('You have previously rented this locker and cannot rent it again.');
                } else if (hasPendingReservation > 0) {
                    alert('You already have a pending reservation for this locker.');
                }
            });

            // Handle rental confirmation
            $('#confirmRental').click(function() {
                const lockerId = $('#modalLockerId').text();
                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');
                
                $.ajax({
                    url: '../client_backend/process_rental.php',
                    method: 'POST',
                    data: { locker_id: lockerId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#rentalRequestModal').modal('hide');
                            showAlert('success', 'Rental request submitted! Please proceed to the cashier for payment.');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showAlert('danger', 'Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        showAlert('danger', 'Error connecting to server. Please try again.');
                        console.error(error);
                    },
                    complete: function() {
                        $('#confirmRental').prop('disabled', false).text('Submit Request');
                    }
                });
            });
        });

        function rentLocker(lockerId) {
            const lockerCard = $(`[data-locker-id="${lockerId}"]`);
            const status = lockerCard.data('status');
            const hasRentedBefore = lockerCard.data('has-rented-before');
            const hasPendingReservation = lockerCard.data('has-pending-reservation');
            
            if (hasRentedBefore > 0) {
                alert('You have previously rented this locker and cannot rent it again.');
                return;
            }
            
            if (hasPendingReservation > 0) {
                alert('You already have a pending reservation for this locker.');
                return;
            }
            
            if (status !== 'Vacant' && status !== 'Reserved') {
                alert('This locker is not available for rent.');
                return;
            }
            
            const size = lockerCard.data('size');
            const price = lockerCard.find('.price').text().replace('₱', '').replace('/month', '').trim();
            $('#modalLockerId').text(lockerId);
            $('#modalLockerSize').text(size);
            $('#modalLockerPrice').text(price);
            $('#rentalRequestModal').modal('show');
        }

        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
            `;
            $('.alert:not(.rental-terms)').remove();
            $('.main-content').prepend(alertHtml);
            setTimeout(() => $('.alert:not(.rental-terms)').fadeOut('slow', function() { $(this).remove(); }), 3000);
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