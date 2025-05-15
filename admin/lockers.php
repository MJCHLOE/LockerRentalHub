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