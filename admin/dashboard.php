<?php
  // Start session
  session_start();

  // Generate a unique session identifier for admin
  $adminSessionKey = md5('Admin_' . $_SESSION['user_id']);

  // Check if user is logged in and is admin using both regular and role-specific session
  if (!isset($_SESSION[$adminSessionKey]) || 
      !isset($_SESSION['role']) || 
      $_SESSION['role'] !== 'Admin') {
      header("Location: ../LoginPage.html");
      exit();
  }

  // Update last activity
  $_SESSION[$adminSessionKey]['last_activity'] = time();

  if (isset($_GET['success'])) {
      echo "<script>alert('".$_GET['success']."');</script>";
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard - Locker Rental Hub</title>
  <!-- Bootstrap 4 CSS -->
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css" />
  <!-- Custom Styles -->
  <link rel="stylesheet" href="admin_dashboard.css" />
  <link rel="stylesheet" href="pagination.css" />
  <!-- Iconify CDN -->
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
</head>
<body>

  <!-- Sidebar Navigation -->
  <div class="sidebar">
    <div class="brand">
      <iconify-icon icon="mdi:lockers"></iconify-icon>
      The Admin
    </div>
    
    <ul class="nav-links">
      <li class="nav-item">
        <a class="nav-link active" href="#analytics" onclick="setActive(this)">
          <iconify-icon icon="mdi:chart-bar"></iconify-icon> Analytics
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#clients" onclick="setActive(this)">
          <iconify-icon icon="mdi:account-group-outline"></iconify-icon> Users
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#lockers" onclick="setActive(this)">
          <iconify-icon icon="mdi:locker-multiple"></iconify-icon> Lockers
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#manage-rentals" onclick="setActive(this)">
          <iconify-icon icon="fluent-mdl2:task-manager"></iconify-icon> Rentals
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#logs" onclick="setActive(this)">
          <iconify-icon icon="mdi:file-document-outline"></iconify-icon> Logs
        </a>
      </li>
    </ul>

    <!-- Bottom Account Section -->
    <div class="mt-auto">
      <div class="dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="accountDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color: white; padding-left: 0;">
             <?php 
                $profilePic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default.jpg';
                echo '<img src="../profile_pics/' . $profilePic . '" alt="Profile" class="rounded-circle mr-2" style="width: 30px; height: 30px; object-fit: cover; border: 2px solid var(--accent-color);">';
            ?>
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
    
    <!-- Analytics Section -->
    <section id="analytics" class="glass-panel">
        <h3>System Overview</h3>
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-primary text-white mb-3" style="border: none; border-radius: 12px; background: linear-gradient(45deg, #2980b9, #3498db);">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase mb-2" style="font-size: 0.8rem; opacity: 0.8;">Total Users</h6>
                        <p class="card-text h2 font-weight-bold" id="totalUsers">...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white mb-3" style="border: none; border-radius: 12px; background: linear-gradient(45deg, #8e44ad, #9b59b6);">
                    <div class="card-body">
                         <h6 class="card-title text-uppercase mb-2" style="font-size: 0.8rem; opacity: 0.8;">Total Lockers</h6>
                        <p class="card-text h2 font-weight-bold" id="totalLockers">...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white mb-3" style="border: none; border-radius: 12px; background: linear-gradient(45deg, #27ae60, #2ecc71);">
                    <div class="card-body">
                         <h6 class="card-title text-uppercase mb-2" style="font-size: 0.8rem; opacity: 0.8;">Active Rentals</h6>
                        <p class="card-text h2 font-weight-bold" id="activeRentals">...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                 <div class="card bg-warning text-dark mb-3" style="border: none; border-radius: 12px; background: linear-gradient(45deg, #f39c12, #f1c40f); color: white !important;">
                    <div class="card-body">
                         <h6 class="card-title text-uppercase mb-2" style="font-size: 0.8rem; opacity: 0.8;">Maintenance</h6>
                        <p class="card-text h2 font-weight-bold" id="maintenanceCount">...</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Users Section -->
    <section id="clients" class="glass-panel">
      <div class="d-flex justify-content-between align-items-center mb-4">
          <h3>User Management</h3>
          <button class="btn btn-success" data-toggle="collapse" data-target="#addUserForm">
              <iconify-icon icon="mdi:plus"></iconify-icon> Add User
          </button>
      </div>

      <div id="addUserForm" class="collapse mb-4">
        <div class="card card-body bg-dark border-secondary">
            <form action="../admin_backend/add_user.php" method="POST">
                <div class="form-row">
                    <div class="col-md-4 mb-3">
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                     <div class="col-md-4 mb-3">
                        <select class="form-control" name="role" required>
                            <option value="Client">Client</option>
                            <option value="Staff">Staff</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                     <div class="col-md-6 mb-3">
                        <input type="text" class="form-control" name="firstname" placeholder="First Name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="text" class="form-control" name="lastname" placeholder="Last Name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-md-6 mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="tel" class="form-control" name="phone_number" placeholder="Phone (09XXXXXXXXX)" pattern="[0-9]{11}" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>
        </div>
      </div>
      
      <div class="search-filter-container">
        <input type="text" id="searchInput" class="form-control w-50" placeholder="Search users..." onkeyup="searchUsers()">
        <div class="btn-group" role="group">
          <button id="filter-all" class="btn btn-outline-light active filter-btn" onclick="filterRole('All')">All</button>
          <button id="filter-client" class="btn btn-outline-light filter-btn" onclick="filterRole('Client')">Clients</button>
          <button id="filter-staff" class="btn btn-outline-light filter-btn" onclick="filterRole('Staff')">Staff</button>
          <button id="filter-admin" class="btn btn-outline-light filter-btn" onclick="filterRole('Admin')">Admins</button>
        </div>
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
                <th>Role</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="usersTableBody">
              <?php include '../admin_backend/fetch_users.php'; ?>
            </tbody>
          </table>
      </div>
      <div class="pagination-container">
          <nav aria-label="Users pagination">
            <ul class="pagination" id="usersPagination"></ul>
          </nav>
      </div>
    </section>

    <!-- Lockers Section -->
    <section id="lockers" class="glass-panel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Locker Management</h3>
            <button class="btn btn-success" data-toggle="modal" data-target="#addLockerModal">
                <iconify-icon icon="mdi:plus"></iconify-icon> New Locker
            </button>
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
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="lockersTableBody">
                <?php include '../admin_backend/fetch_lockers.php'; ?>
              </tbody>
            </table>
        </div>
        <div class="pagination-container">
            <nav aria-label="Lockers pagination">
              <ul class="pagination" id="lockersPagination"></ul>
            </nav>
        </div>
    </section>

    <!-- Rentals Section -->
    <section id="manage-rentals" class="glass-panel">
        <div class="d-flex justify-content-between align-items-center mb-4">
             <h3>Rental Management</h3>
             <div class="btn-group">
                <button id="tab-active" class="btn btn-primary rental-tab" onclick="loadRentals('active')">Active</button>
                <button id="tab-archive" class="btn btn-outline-secondary rental-tab" onclick="loadRentals('archive')">History</button>
            </div>
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
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="rentalsTableBody">
                <?php include '../admin_and_staff_backend/fetch_rentals.php'; ?>
            </tbody>
        </table>
        </div>
        <div class="pagination-container">
            <nav aria-label="Rentals pagination">
              <ul class="pagination" id="rentalsPagination"></ul>
            </nav>
        </div>
    </section>

    <!-- Logs Section -->
    <section id="logs" class="glass-panel">
        <h3>System Logs</h3>
        
        <div class="search-filter-container">
            <input type="text" id="logSearchInput" class="form-control w-50" placeholder="Search logs...">
            <div class="btn-group" role="group">
                <button class="btn btn-outline-light active" onclick="filterLogs('all')">All</button>
                <button class="btn btn-outline-light" onclick="filterLogs('admin')">Admin</button>
                <button class="btn btn-outline-light" onclick="filterLogs('staff')">Staff</button>
                <button class="btn btn-outline-light" onclick="filterLogs('client')">Client</button>
            </div>
        </div>

        <div class="table-container">
            <table class="table">
              <thead>
                <tr>
                  <th>Date/Time</th>
                  <th>Action</th>
                  <th>Description</th>
                  <th>User</th>
                  <th>Entity</th>
                </tr>
              </thead>
              <tbody id="logsTableBody">
                <!-- Logs loaded via AJAX usually, but initial include as well? logs.js handles it mostly -->
              </tbody>
            </table>
        </div>
        <div class="pagination-container">
             <nav aria-label="Logs pagination">
                  <ul class="pagination" id="logsPagination"></ul>
             </nav>
        </div>
    </section>

  </div>

  <!-- MODALS -->

  <!-- Change Password Modal -->
  <div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="../admin_backend/change_password.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Change Password</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="user_id" id="password_user_id">
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

  <!-- Add Locker Modal -->
  <div class="modal fade" id="addLockerModal" tabindex="-1">
    <div class="modal-dialog">
      <form action="../admin_backend/add_locker.php" method="POST">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add New Locker</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Locker ID</label>
              <input type="text" name="locker_id" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Size</label>
              <select name="size" class="form-control" required>
                  <option value="Small">Small</option>
                  <option value="Medium">Medium</option>
                  <option value="Large">Large</option>
              </select>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control" required>
                  <option value="Vacant">Vacant</option>
                  <option value="Occupied">Occupied</option>
                  <option value="Maintenance">Maintenance</option>
                  <option value="Reserved">Reserved</option>
              </select>
            </div>
            <div class="form-group">
              <label>Price per Month</label>
              <input type="number" step="any" name="price_per_month" class="form-control" required />
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Save Locker</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Locker Modal -->
  <div class="modal fade" id="editLockerModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Locker</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body" id="editLockerModalBody">
           <!-- Loaded Dynamically -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveLockerEdit()">Save Changes</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
           <h5 class="modal-title">Edit User</h5>
           <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body" id="editModalBody">
           <!-- Loaded Dynamically -->
        </div>
        <div class="modal-footer">
           <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
           <button type="button" class="btn btn-primary" onclick="submitEditForm()">Save Changes</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Templates -->
  <div id="editUserFormTemplate" style="display: none;">
    <form id="editUserForm" action="../admin_backend/edit_user.php" method="POST">
      <input type="hidden" id="editUserId" name="user_id">
      <div class="form-group">
        <label>Username</label>
        <input type="text" class="form-control" id="editUsername" name="username" required>
      </div>
      <div class="form-group">
        <label>Password (leave blank to keep)</label>
        <input type="text" class="form-control" id="editPassword" name="password" placeholder="New Password">
      </div>
      <div class="form-row">
          <div class="col">
             <label>Firstname</label>
             <input type="text" class="form-control" id="editFirstname" name="firstname" required>
          </div>
          <div class="col">
             <label>Lastname</label>
             <input type="text" class="form-control" id="editLastname" name="lastname" required>
          </div>
      </div>
      <div class="form-group mt-3">
        <label>Email</label>
        <input type="email" class="form-control" id="editEmail" name="email">
      </div>
      <div class="form-group">
        <label>Phone Number</label>
        <input type="tel" class="form-control" id="editPhoneNumber" name="phone_number" pattern="[0-9]{11}">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select class="form-control" id="editRole" name="role" required>
          <option value="Client">Client</option>
          <option value="Staff">Staff</option>
          <option value="Admin">Admin</option>
        </select>
      </div>
    </form>
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
  </script>
  <script src="../admin_scripts/password_utils.js"></script>
  <script src="../admin_scripts/user_management.js"></script>
  <script src="../admin_scripts/dashboard_init.js"></script>
  <script src="../admin_scripts/locker_management.js"></script>
  <script src="../admin_scripts/analytics.js"></script>
  <script src="../admin_scripts/logs.js"></script>
  <script src="../admin_and_staff_scripts/rental_management.js"></script>
</body>
</html>