<?php
  // Start session
  session_start();

  // Generate a unique session identifier for staff
  $staffSessionKey = md5('Staff_' . $_SESSION['user_id']);

  // Check if user is logged in and is staff using both regular and role-specific session
  if (!isset($_SESSION[$staffSessionKey]) || 
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Staff Dashboard - Locker Rental Hub</title>
  <!-- Bootstrap 4 CSS -->
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css" />
  <!-- Custom Styles -->
  <link rel="stylesheet" href="staff_dashboard.css?v=<?php echo time(); ?>" />
  <link rel="stylesheet" href="pagination.css" />
  <!-- Iconify CDN -->
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

  <!-- Sidebar Navigation -->
  <div class="sidebar">
    <div class="brand">
      <iconify-icon icon="mdi:lockers"></iconify-icon>
      Staff Portal
    </div>
    
    <ul class="nav-links">
      <li class="nav-item">
        <a class="nav-link active" href="#clients" onclick="setActive(this)">
          <iconify-icon icon="mdi:account-group-outline"></iconify-icon> View Clients
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#lockers" onclick="setActive(this)">
          <iconify-icon icon="mdi:locker-multiple"></iconify-icon> View Lockers
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#manage-rentals" onclick="setActive(this)">
          <iconify-icon icon="fluent-mdl2:task-manager"></iconify-icon> Manage Rentals
        </a>
      </li>
    </ul>

    <!-- Bottom Account Section -->
    <div class="mt-auto">
      
      <!-- Notification Dropdown -->
      <div class="dropdown dropup mb-2">
           <a href="javascript:void(0);" class="nav-link dropdown-toggle" id="notificationBtn" aria-haspopup="true" aria-expanded="false" style="color: var(--text-secondary);">
               <iconify-icon icon="mdi:bell" style="font-size: 1.5rem;"></iconify-icon> Notifications
               <span id="notificationBadge" class="badge badge-danger" style="display: none; margin-left: 5px;">0</span>
           </a>
           <div class="dropdown-menu" id="notificationDropdown" aria-labelledby="notificationBtn" style="width: 300px; max-height: 400px; overflow-y: auto;">
                <h6 class="dropdown-header">Notifications</h6>
                <div id="notificationList">
                     <!-- Loaded via JS -->
                </div>
           </div>
      </div>

      <div class="dropdown">
        <a class="nav-link dropdown-toggle dropup" href="#" id="accountDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color: var(--text-secondary);">
            <iconify-icon icon="mdi:account-circle" style="font-size: 1.8rem;"></iconify-icon>
            My Account
        </a>
        <div class="dropdown-menu" aria-labelledby="accountDropdown">
          <a class="dropdown-item" href="#" data-toggle="modal" data-target="#changePasswordModal">Change Password</a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item text-danger" href="../backend/logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    
    <!-- Users Section -->
    <section id="clients" class="glass-panel">
      <div class="d-flex justify-content-between align-items-center mb-4">
          <h3>View Clients</h3>
      </div>
      
      <div class="search-filter-container">
        <input type="text" id="searchInput" class="form-control w-50" placeholder="Search clients..." onkeyup="searchClients()">
      </div>

      <div class="table-container">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
              </tr>
            </thead>
            <tbody id="clientsTableBody">
              <?php include '../staff_backend/fetch_clients.php'; ?>
            </tbody>
          </table>
      </div>
    </section>

    <!-- Lockers Section -->
    <section id="lockers" class="glass-panel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>View Lockers</h3>
        </div>

        <div class="search-filter-container">
            <input type="text" id="lockerSearchInput" class="form-control w-50" placeholder="Search lockers..." onkeyup="searchLockers()">
            <div class="btn-group" role="group">
                <button class="btn btn-outline-light active filter-btn" onclick="filterLockerStatus('All')">All</button>
                <button class="btn btn-outline-light filter-btn" onclick="filterLockerStatus('Vacant')">Vacant</button>
                <button class="btn btn-outline-light filter-btn" onclick="filterLockerStatus('Occupied')">Rented</button>
                <button class="btn btn-outline-light filter-btn" onclick="filterLockerStatus('Maintenance')">Maintenance</button>
            </div>
        </div>

        <div class="table-container">
            <table class="table">
              <thead>
                <tr>
                  <th>Locker ID</th>
                  <th>Size</th>
                  <th>Status</th>
                  <th>Price / Month</th>
                </tr>
              </thead>
              <tbody id="lockersTableBody">
                <?php include '../staff_backend/fetch_lockers.php'; ?>
              </tbody>
            </table>
        </div>
    </section>

    <!-- Rentals Section -->
    <section id="manage-rentals" class="glass-panel">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h3>Manage Rentals</h3>
        </div>

        <div class="search-filter-container">
            <input type="text" id="rentalSearchInput" class="form-control w-50" placeholder="Search rentals..." onkeyup="searchRentals()">
            <div class="btn-group btn-group-sm" role="group" id="rentalFilters">
                <button class="btn btn-outline-light active" onclick="filterRentals('all')">All</button>
                <button class="btn btn-outline-warning" onclick="filterRentals('pending')">Pending</button>
                <button class="btn btn-outline-success" onclick="filterRentals('approved')">Approved</button>
                <button class="btn btn-outline-success" onclick="filterRentals('active')">Active</button>
                <button class="btn btn-outline-danger" onclick="filterRentals('denied')">Denied</button>
                <button class="btn btn-outline-info" onclick="filterRentals('completed')">Completed</button>
            </div>
        </div>

        <div class="table-container">
            <table class="table">
            <thead>
                <tr>
                    <th>Rental ID</th>
                    <th>Client ID</th>
                    <th>Client Name</th>
                    <th>Locker ID</th>
                    <th>Rental Date</th>
                    <th>Approved Date</th>
                    <th>End Date</th>
                    <th>Time Remaining</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Total Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="rentalsTableBody">
                <?php include '../admin_and_staff_backend/fetch_rentals.php'; ?>
            </tbody>
        </table>
        </div>
    </section>

  </div>

  <!-- MODALS -->

  <!-- Change Password Modal -->
  <div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="changePasswordForm">
          <div class="modal-header">
            <h5 class="modal-title">Change Password</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Current Password</label>
              <input type="password" class="form-control" name="current_password" required>
            </div>
            <div class="form-group">
              <label>New Password</label>
              <input type="password" class="form-control" name="new_password" required minlength="6">
              <small class="text-muted">Minimum 6 characters.</small>
            </div>
            <div class="form-group">
              <label>Confirm New Password</label>
              <input type="password" class="form-control" name="confirm_password" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Receipt Modal -->
  <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 650px;">
          <div class="modal-content bg-transparent border-0 shadow-none">
              <div class="modal-body p-0" id="receiptModalBody">
                  <!-- Receipt Content Loaded Here -->
                  <div class="text-center text-white py-5">
                       <div class="spinner-border" role="status">
                          <span class="sr-only">Loading...</span>
                      </div>
                      <p class="mt-2">Loading Receipt...</p>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  
  <script>
    function setActive(element) {
        document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
        element.classList.add('active');
    }

    // Smooth scrolling (Simplified version from admin)
    $('a[href^="#"]').on('click', function (event) {
        const href = this.getAttribute('href');
        if (href === '#' || href.length <= 1) return;
        const target = $(href);
        if (target.length) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 70
            }, 800);
        }
    });

    // Initialize tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    // Handle Password Change AJAX
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const newPass = formData.get('new_password');
        const confirmPass = formData.get('confirm_password');

        if (newPass !== confirmPass) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'New passwords do not match.',
            });
            return;
        }

        $.ajax({
            url: '../staff_backend/change_password.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                    }).then(() => {
                        $('#changePasswordModal').modal('hide');
                        $('#changePasswordForm')[0].reset();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                    });
                }
            },
            error: function() {
                 Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred.',
                });
            }
        });
    });
  </script>
  
  <script src="../staff_scripts/view_clients.js"></script>
  <script src="../staff_scripts/locker_management.js"></script>
  <script src="../admin_and_staff_scripts/rental_management.js?v=<?php echo time(); ?>"></script>
  <script src="../client_scripts/notifications.js"></script>

  <script>
    // Check for login success
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('login_success') === '1') {
        Swal.fire({
            icon: 'success',
            title: 'Welcome back, Staff!',
            text: 'You have successfully logged in.',
            timer: 2000,
            showConfirmButton: false
        });
        
        // Clean URL
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
    
    function viewReceipt(rentalId) {
        $('#receiptModal').modal('show');
        $('#receiptModalBody').html(`
            <div class="text-center text-white py-5">
                 <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading Receipt...</p>
            </div>
        `);
        
        $.ajax({
            url: '../client/receipt.php',
            method: 'GET',
            data: { rental_id: rentalId, mode: 'modal' },
            success: function(response) {
                $('#receiptModalBody').html(response);
            },
            error: function() {
                $('#receiptModalBody').html('<div class="alert alert-danger">Failed to load receipt.</div>');
            }
        });
    }
  </script>

</body>
</html>