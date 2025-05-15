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

  <!-- Iconify CDN -->
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="#">
      <iconify-icon icon="mdi:lockers" width="24"></iconify-icon>
      Admin Dashboard
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a class="nav-link" href="#clients"><iconify-icon icon="mdi:account-group-outline"></iconify-icon> Users</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#lockers"><iconify-icon icon="mdi:locker-multiple"></iconify-icon> Lockers</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#analytics"><iconify-icon icon="mdi:chart-bar"></iconify-icon> Analytics</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#logs"><iconify-icon icon="mdi:file-document-outline"></iconify-icon> Logs</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#manage-rentals"><iconify-icon icon="fluent-mdl2:task-manager"></iconify-icon> Manage Rentals</a>
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
      </ul>
    </div>
  </nav>

  <!-- Change Password Modal -->
  <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form action="../admin_backend/change_password.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="changePasswordModalLabel" style="color: black;">Change Password</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="user_id" id="password_user_id">

            <!-- Current Password -->
            <div class="form-group">
              <label for="currentPassword" style="color: black;">Current Password</label>
              <input type="password" class="form-control" id="currentPassword" name="current_password" required>
              
              <!-- Show Password Checkbox -->
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
              
              <!-- Show Password Checkbox -->
              <div class="form-check mt-2">
                <input type="checkbox" class="form-check-input" onclick="togglePasswordVisibility('newPassword')">
                <label class="form-check-label">Show Password</label>
              </div>
            </div>

            <!-- Confirm New Password -->
            <div class="form-group">
              <label for="confirmPassword" style="color: black;">Confirm New Password</label>
              <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
              
              <!-- Show Password Checkbox -->
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
  </div>

  <div class="container-fluid mt-5 pt-3">
    <div class="row">
      <!-- Main Content -->
      <div class="col-md-12">
      <section id="clients" class="my-4">
      <h3>Manage Users</h3>
      <p>Add, edit, or remove user accounts.</p>

      <!-- Add Users part -->
      <button class="btn btn-success mb-3" data-toggle="collapse" data-target="#addUserForm">Add New User</button>

      <div id="addUserForm" class="collapse">
        <form action="../admin_backend/add_user.php" method="POST" class="bg-secondary p-3 rounded">
          <div class="form-group">
            <label for="newUsername">Username</label>
            <input type="text" class="form-control" id="newUsername" name="username" required>
          </div>

          <div class="form-group">
            <label for="addUserPassword">Password</label>
            <input type="password" class="form-control" id="addUserPassword" name="password" required>
            
            <!-- Show Password Checkbox -->
            <div class="form-check mt-2">
              <input type="checkbox" class="form-check-input" id="showPasswordToggle" onclick="togglePasswordVisibility()">
              <label class="form-check-label">Show Password</label>
            </div>
          </div>
          
          <div class="form-group">
            <label for="newFirstname">Firstname</label>
            <input type="text" class="form-control" id="newFirstname" name="firstname" required>
          </div>

          <div class="form-group">
            <label for="newLastname">Lastname</label>
            <input type="text" class="form-control" id="newLastname" name="lastname" required>
          </div>

          <div class="form-group">
            <label for="newEmail">Email</label>
            <input type="email" class="form-control" id="newEmail" name="email" required>
          </div>

          <div class="form-group">
            <label for="newPhone">Phone Number</label>
            <input type="tel" class="form-control" id="newPhone" name="phone_number" 
                   pattern="[0-9]{11}" placeholder="09XXXXXXXXX" required>
          </div>

          <div class="form-group">
            <label for="newRole">Role</label>
            <select class="form-control" id="newRole" name="role" required>
              <option value="Client">Client</option>
              <option value="Staff">Staff</option>
              <option value="Admin">Admin</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary">Add User</button>
        </form>
      </div>
      
      <!-- Search function and filters for users table -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <input type="text" id="searchInput" class="form-control w-50" placeholder="Search by ID, Username, Name, Email, or Phone" onkeyup="searchUsers()">

        <div class="btn-group" role="group" aria-label="User filters">
          <button id="filter-all" class="btn btn-primary filter-btn" onclick="filterRole('All')">All</button>
          <button id="filter-client" class="btn btn-success filter-btn" onclick="filterRole('Client')">Clients</button>
          <button id="filter-staff" class="btn btn-info filter-btn" onclick="filterRole('Staff')">Staff</button>
          <button id="filter-admin" class="btn btn-warning filter-btn" onclick="filterRole('Admin')">Admins</button>
        </div>
      </div>

      <!-- Displaying all users details -->  
      <div class="table-responsive bg-dark text-white p-3 rounded">
        <table class="table table-dark table-bordered">
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
    </section>

    <section id="lockers" class="my-4">
    <h3>Manage Lockers</h3>
    <p>Add, edit, or remove locker entries.</p>

    <!-- Add New Locker Button -->
    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addLockerModal">Add New Locker</button>

    <!-- Search and Filter Controls -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <input type="text" id="lockerSearchInput" class="form-control w-50" placeholder="Search by Locker ID or Size" onkeyup="searchLockers()">
      <div class="btn-group" role="group" aria-label="Locker filters">
        <button id="filter-all" class="btn btn-primary filter-btn" onclick="filterLockerStatus('All')">All</button>
        <button id="filter-vacant" class="btn btn-success filter-btn" onclick="filterLockerStatus('Vacant')">Vacant</button>
        <button id="filter-occupied" class="btn btn-info filter-btn" onclick="filterLockerStatus('Occupied')">Rented</button>
        <button id="filter-maintenance" class="btn btn-warning filter-btn" onclick="filterLockerStatus('Maintenance')">In Maintenance</button>
      </div>
    </div>

    <!-- Locker Table -->
    <div class="table-responsive bg-dark text-white p-3 rounded">
      <table class="table table-dark table-bordered">
        <thead>
          <tr>
            <th>Locker ID</th>
            <th>Size</th>
            <th>Status</th>
            <th>Price per Month</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="lockersTableBody">
          <!-- Locker rows will be populated dynamically via PHP -->
          <?php include '../admin_backend/fetch_lockers.php'; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Add Locker Modal -->
  <div class="modal fade" id="addLockerModal" tabindex="-1" role="dialog" aria-labelledby="addLockerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <form action="../admin_backend/add_locker.php" method="POST" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addLockerModalLabel">Add New Locker</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Locker ID</label>
            <input type="text" name="locker_id" class="form-control" required />
          </div>
          <div class="form-group">
            <label>Size</label>
            <select name="size_id" class="form-control" required>
              <?php foreach ($conn->query("SELECT * FROM lockersizes") as $row): ?>
                <option value="<?= $row['size_id'] ?>"><?= $row['size_name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status_id" class="form-control" required>
              <?php foreach ($conn->query("SELECT * FROM lockerstatuses") as $row): ?>
                <option value="<?= $row['status_id'] ?>"><?= $row['status_name'] ?></option>
              <?php endforeach; ?>
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
      </form>
    </div>
  </div>

  <!-- Edit Locker Modal -->
<div class="modal fade" id="editLockerModal" tabindex="-1" role="dialog" aria-labelledby="editLockerModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editLockerModalLabel" style="color: black">Edit Locker</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="editLockerModalBody">
        <!-- Content loaded dynamically -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="saveLockerEdit()">Save Changes</button>
      </div>
    </div>
  </div>
</div>

    <section id="analytics" class="my-4">
    <h3>System Analytics</h3>
    <p>Overview of system performance.</p>
    <div class="bg-dark text-white p-3 rounded">
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-primary text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text h3" id="totalUsers">Loading...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Lockers</h5>
                        <p class="card-text h3" id="totalLockers">Loading...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Active Rentals</h5>
                        <p class="card-text h3" id="activeRentals">Loading...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark mb-3">
                    <div class="card-body">
                        <h5 class="card-title">In Maintenance</h5>
                        <p class="card-text h3" id="maintenanceCount">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

        <section id="logs" class="my-4">
    <h3>System Logs</h3>
    <p>View recent activity logs.</p>
    
    <!-- Filter Buttons and Search -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <input type="text" id="logSearchInput" class="form-control w-50" 
               placeholder="Search logs...">
        
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary active" onclick="filterLogs('all')">All Logs</button>
            <button type="button" class="btn btn-info" onclick="filterLogs('admin')">Admin Logs</button>
            <button type="button" class="btn btn-success" onclick="filterLogs('staff')">Staff Logs</button>
            <button type="button" class="btn btn-warning" onclick="filterLogs('client')">Client Logs</button>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="table-responsive bg-dark text-white p-3 rounded">
        <table class="table table-dark table-bordered">
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
                <!-- Logs will be loaded here -->
            </tbody>
        </table>
    </div>
</section>

<section id="manage-rentals" class="my-4">
    <h3>Manage Rentals</h3>
    <p>Process and manage locker rentals.</p>

    <!-- Search and Filter Controls -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <input type="text" id="rentalSearchInput" class="form-control w-50" 
               placeholder="Search by ID, Client, or Locker ID" onkeyup="searchRentals()">
        
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary active" onclick="filterRentals('all')">All Rentals</button>
            <button type="button" class="btn btn-warning" onclick="filterRentals('pending')">Pending</button>
            <button type="button" class="btn btn-success" onclick="filterRentals('approved')">Approved</button>
            <button type="button" class="btn btn-success" onclick="filterRentals('active')">Active</button>
            <button type="button" class="btn btn-danger" onclick="filterRentals('denied')">Denied</button>
            <button type="button" class="btn btn-secondary" onclick="filterRentals('cancelled')">Cancelled</button>
            <button type="button" class="btn btn-info" onclick="filterRentals('completed')">Completed</button>
        </div>
    </div>

      <!-- Rentals Table -->
      <div class="table-responsive bg-dark text-white p-3 rounded">
          <table class="table table-dark table-bordered">
              <thead>
                  <tr>
                  <th>Rental ID</th>
                  <th>Client</th>
                  <th>Locker ID</th>
                  <th>Rental Date</th>
                  <th>Rent Ended Date</th>
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
  </section>
    </div>
  </div>
</div>

  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="editModalBody">
          <!-- Content will be loaded dynamically -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="submitEditForm()">Save Changes</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Hidden template for edit user form -->
  <div id="editUserFormTemplate" style="display: none;">
    <form id="editUserForm" action="../admin_backend/edit_user.php" method="POST">
      <input type="hidden" id="editUserId" name="user_id">
      
      <div class="form-group">
        <label for="editUsername" style="color: black;">Username</label>
        <input type="text" class="form-control" id="editUsername" name="username" required>
      </div>

      <div class="form-group">
        <label for="editPassword" style="color: black;">Password (leave blank to keep current password)</label>
        <input type="password" class="form-control" id="editPassword" name="password">
        
        <div class="form-check mt-2">
          <input type="checkbox" class="form-check-input" id="editShowPasswordToggle" onclick="toggleEditPasswordVisibility()">
          <label class="form-check-label" for="editShowPasswordToggle">Show Password</label>
        </div>
      </div>
      
      <div class="form-group">
        <label for="editFirstname" style="color: black;">Firstname</label>
        <input type="text" class="form-control" id="editFirstname" name="firstname" required>
      </div>

      <div class="form-group">
        <label for="editLastname" style="color: black;">Lastname</label>
        <input type="text" class="form-control" id="editLastname" name="lastname" required>
      </div>

      <div class="form-group">
        <label for="editEmail" style="color: black;">Email (leave blank to keep current email)</label>
        <input type="email" class="form-control" id="editEmail" name="email">
      </div>

      <div class="form-group">
        <label for="editPhone" style="color: black;">Phone Number (leave blank to keep current phone number)</label>
        <input type="tel" class="form-control" id="editPhoneNumber" name="phone_number" 
               pattern="[0-9]{11}" placeholder="09XXXXXXXXX">
      </div>

      <div class="form-group">
        <label for="editRole" style="color: black;">Role</label>
        <select class="form-control" id="editRole" name="role" required>
          <option value="Client">Client</option>
          <option value="Staff">Staff</option>
          <option value="Admin">Admin</option>
        </select>
        <small class="form-text text-muted">Note: Changing role will move user data between tables.</small>
      </div>
    </form>
  </div>

  <!-- Bootstrap 4 JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> <!-- FULL jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script> <!-- Popper.js -->
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> <!-- Bootstrap 4 JS -->

  <!-- Custom Scripts -->
  <script src="../admin_scripts/password_utils.js"></script>
  <script src="../admin_scripts/user_management.js"></script>
  <script src="../admin_scripts/dashboard_init.js"></script>
  <script src="../admin_scripts/locker_management.js"></script>
  <script src="../admin_scripts/analytics.js"></script>
  <script src="../admin_scripts/logs.js"></script>
  <script src="../admin_and_staff_scripts/rental_management.js"></script>          

</body>
</html>