<?php
session_start();
require '../db/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if (!isset($_GET['rental_id'])) {
    die("Rental ID required");
}

$rental_id = $_GET['rental_id'];
$user_id = $_SESSION['user_id'];

// Fetch rental details
$sql = "SELECT r.*, l.size, l.price, u.firstname, u.lastname, u.email 
        FROM rentals r 
        JOIN lockers l ON r.locker_id = l.locker_id 
        JOIN users u ON r.user_id = u.user_id 
        WHERE r.rental_id = ?";

// Allow Admin to view any receipt, Client only their own
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff') {
    $sql .= " AND r.user_id = ?";
}

$stmt = $conn->prepare($sql);

if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff') {
    $stmt->bind_param("ii", $rental_id, $user_id);
} else {
    $stmt->bind_param("i", $rental_id);
}

$stmt->execute();
$result = $stmt->get_result();
$rental = $result->fetch_assoc();

if (!$rental) {
    die("Receipt not found or access denied.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Receipt #<?php echo $rental_id; ?></title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <style>
        body { background: #f4f6f9; padding: 40px 0; }
        .receipt-card {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .brand-header {
            text-align: center;
            border-bottom: 2px dashed #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .barcode-container {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #eee;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
    </style>
</head>
<body>

    <div class="receipt-card">
        <div class="brand-header">
            <h4>Locker Rental Hub</h4>
            <p class="text-muted mb-0">Official Rental Receipt</p>
        </div>

        <div class="text-center mb-4">
            <span class="status-badge status-<?php echo strtolower($rental['status']); ?>">
                <?php echo $rental['status']; ?>
            </span>
        </div>

        <div class="detail-row">
            <span class="text-muted">Receipt ID:</span>
            <span class="font-weight-bold">#<?php echo str_pad($rental_id, 6, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="detail-row">
            <span class="text-muted">Customer:</span>
            <span><?php echo htmlspecialchars($rental['firstname'] . ' ' . $rental['lastname']); ?></span>
        </div>
        <div class="detail-row">
            <span class="text-muted">Locker ID:</span>
            <span class="font-weight-bold"><?php echo htmlspecialchars($rental['locker_id']); ?></span>
        </div>
        <div class="detail-row">
            <span class="text-muted">Size:</span>
            <span><?php echo $rental['size']; ?></span>
        </div>
        <div class="detail-row">
            <span class="text-muted">Started:</span>
            <span><?php echo date('M d, Y', strtotime($rental['rental_date'])); ?></span>
        </div>
        
        <?php if($rental['end_date']): ?>
        <div class="detail-row">
            <span class="text-muted">Expires:</span>
            <span class="text-danger"><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></span>
        </div>
        <?php endif; ?>

        <hr>

        <div class="detail-row" style="font-size: 1.2rem;">
            <span class="font-weight-bold">Total Paid:</span>
            <span class="font-weight-bold text-success">₱<?php echo number_format($rental['price'], 2); ?></span>
        </div>

        <div class="barcode-container">
            <svg id="barcode"></svg>
            <p class="text-muted small mt-2">Scan this barcode at the kiosk for access</p>
        </div>
        
        <div class="text-center mt-4 d-print-none">
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm">Print Receipt</button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm">Close</button>
        </div>
    </div>

    <script>
        JsBarcode("#barcode", "LOCK-<?php echo $rental['locker_id']; ?>-<?php echo $rental['rental_id']; ?>", {
            format: "CODE128",
            lineColor: "#333",
            width: 2,
            height: 50,
            displayValue: true
        });
    </script>
</body>
</html>
