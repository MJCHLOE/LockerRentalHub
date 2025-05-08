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
                    <a class="nav-link" href="#view-lockers">View Lockers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#manage-rentals">Manage Rentals</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../backend/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid mt-5">
        
        <!-- Manage Clients Section -->
        <section id="clients" class="my-4">
            <h3>View Clients</h3>
            <p>View client information.</p>

            <!-- Search function -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <input type="text" id="searchInput" class="form-control w-50" placeholder="Search by ID, Username, Name, Email, or Phone" onkeyup="searchClients()">
            </div>

            <!-- Displaying all clients -->
            <div class="table-responsive bg-dark text-white p-3 rounded">
                <table class="table table-dark table-bordered">
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
        </section>

        <!-- Manage Rentals Section -->
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
                    <button type="button" class="btn btn-success" onclick="filterRentals('approved')">Active</button>
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
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Payment Status</th>
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../admin_and_staff_scripts/rental_management.js"></script>
    <script src="../staff_scripts/locker_management.js"></script>
    <script src="../staff_scripts/view_clients.js"></script>
</body>
</html>