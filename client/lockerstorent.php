<?php
session_start();
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
    $whereClauses[] = "ls.size_name = '$sizeFilter'";
}
if ($statusFilter != '') {
    $statusFilter = $conn->real_escape_string($statusFilter);
    $whereClauses[] = "lst.status_name = '$statusFilter'";
}
$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Count total lockers with filters
$countQuery = "SELECT COUNT(*) as total FROM lockerunits l
               JOIN lockersizes ls ON l.size_id = ls.size_id
               JOIN lockerstatuses lst ON l.status_id = lst.status_id
               $whereSql";
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
            $query = "SELECT l.locker_id, ls.size_name, lst.status_name, l.price_per_month
                      FROM lockerunits l
                      JOIN lockersizes ls ON l.size_id = ls.size_id
                      JOIN lockerstatuses lst ON l.status_id = lst.status_id
                      $whereSql
                      ORDER BY l.locker_id
                      LIMIT $lockersPerPage OFFSET $offset";

            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $statusClass = "status-" . strtolower($row['status_name']);
                    ?>
                    <div class="locker-card <?php echo $statusClass; ?>" 
                         data-size="<?php echo $row['size_name']; ?>"
                         data-status="<?php echo $row['status_name']; ?>"
                         data-locker-id="<?php echo $row['locker_id']; ?>">
                        <div class="locker-icon">
                            <iconify-icon icon="mdi:locker" width="48"></iconify-icon>
                        </div>
                        <div class="locker-details">
                            <h4><?php echo $row['locker_id']; ?></h4>
                            <p><?php echo $row['size_name']; ?></p>
                            <p class="status"><?php echo $row['status_name']; ?></p>
                            <p class="price">₱<?php echo number_format($row['price_per_month'], 2); ?>/month</p>
                            <?php if ($row['status_name'] == 'Vacant'): ?>
                                <button class="btn btn-success btn-sm" 
                                        onclick="rentLocker('<?php echo $row['locker_id']; ?>')">
                                    Rent Now
                                </button>
                            <?php else: ?>
                                <p class="status-message">
                                    <?php echo ($row['status_name'] == 'Occupied') ? 
                                        'This locker is currently occupied' : 
                                        'This locker is under maintenance'; ?>
                                </p>
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
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="locker-details-container p-3 bg-light rounded mb-3">
                        <h6 class="font-weight-bold" style="color: #000;">Locker Details:</h6>
                        <p style="color: #000;"><strong>Locker ID:</strong> <span id="modalLockerId"></span></p>
                        <p style="color: #000;"><strong>Size:</strong> <span id="modalLockerSize"></span></p>
                        <p style="color: #000;"><strong>Price:</strong> ₱<span id="modalLockerPrice"></span>/month</p>
                    </div>
                    <div class="rental-terms alert alert-info">
                        <h6 class="font-weight-bold mb-3">Payment Instructions:</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">• Please proceed to the rental spot's cashier for payment</li>
                            <li class="mb-2">• Payment must be completed within approval</li>
                            <li class="mb-2">• Your rental status will remain 'Pending Payment' until paid</li>
                            <li>• The locker will be reserved for you during this period</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmRental">Submit Request</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../client_scripts/dashboard.js"></script>
    <script src="../client_scripts/dropdown.js"></script>
    
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
                if (status !== 'Vacant') {
                    const message = status === 'Occupied' ? 
                        'This locker is currently occupied.' : 
                        'This locker is under maintenance.';
                    alert(message);
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
            if (status !== 'Vacant') {
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
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            $('.alert:not(.rental-terms)').remove();
            $('.main-content').prepend(alertHtml);
            setTimeout(() => $('.alert:not(.rental-terms)').fadeOut('slow', function() { $(this).remove(); }), 3000);
        }
    </script>
</body>
</html>