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
                <input type="text" id="searchInput" class="form-control w-50" placeholder="Search by ID, Username, Name, Email, or Phone">
            </div>

            <!-- Displaying all clients -->
            <div class="table-container">
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
                <!-- Pagination will be added here by JavaScript -->
            </div>
        </section>


    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../admin_and_staff_scripts/rental_management.js"></script>
    <script src="../staff_scripts/locker_management.js"></script>
    <script src="../staff_scripts/view_clients.js"></script>
    
</body>
</html>