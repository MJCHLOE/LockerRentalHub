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

// Calculate logic
$start = new DateTime($rental['rental_date']);
$end = $rental['end_date'] ? new DateTime($rental['end_date']) : clone $start;
if (!$rental['end_date']) $end->modify('+1 month'); 

$days = $end->diff($start)->days;
$months = ceil($days / 30);
if ($months < 1) $months = 1;
$totalPrice = $rental['price'] * $months;

// Status Badge Colors (Map to reference)
$statusColor = '#ffc107'; // Default warning/pending (Yellow)
$statusText = '#000';
if ($rental['status'] === 'active' || $rental['status'] === 'completed') {
    $statusColor = '#28a745'; // Green
    $statusText = '#fff';
} elseif ($rental['status'] === 'denied' || $rental['status'] === 'cancelled') {
    $statusColor = '#dc3545'; // Red
    $statusText = '#fff';
}

// Logic for receipt type
$isReservation = ($rental['status'] === 'pending' || $rental['payment_status'] === 'unpaid');
$headerTitle = "Reservation Details";
$barcodeInstruction = $isReservation ? "Present to cashier for payment" : "Scan for locker access";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $rental_id; ?></title>
    <!-- Bootstrap 4 (for grid mainly) -->
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <!-- Iconify -->
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <!-- Barcode -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <style>
        body {
            background-color: #1a1a1a; /* Dark background behind modal */
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            color: #ccc;
        }
        
        .receipt-card {
            width: 100%;
            max-width: 600px;
            background-color: #222; /* Card bg */
            border-radius: 10px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
            overflow: hidden;
            border: 1px solid #333;
        }

        /* Header */
        .card-header {
            background-color: #222;
            padding: 15px 20px;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffc107; /* Gold color */
        }
        .header-title {
            font-size: 1.1rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .close-btn {
            background: none;
            border: none;
            color: #999;
            font-size: 1.2rem;
            cursor: pointer;
        }
        .close-btn:hover { color: #fff; }

        /* Body */
        .card-body {
            padding: 20px;
        }

        /* Ref & Status Row */
        .ref-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        .ref-number {
            font-size: 1.2rem;
            color: #fff;
            font-weight: bold;
        }
        .ref-number span { color: #ffc107; }
        .status-badge {
            background-color: <?php echo $statusColor; ?>;
            color: <?php echo $statusText; ?>;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        /* Grid Section */
        .info-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        .col-left, .col-right {
            flex: 1;
        }
        
        /* Section Headers like PARKING / TIMING */
        .section-label {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }

        /* Locker Block */
        .locker-block {
            background-color: #2c2c2c;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 15px;
            border: 1px solid #333;
        }
        .locker-id {
            color: #28a745; /* Success Green */
            font-size: 1.8rem;
            font-weight: bold;
            display: block;
        }
        .locker-size {
            color: #999;
            font-size: 0.9rem;
        }

        /* Vehicle/Client Block */
        .client-info h6 {
            color: #fff;
            margin: 0;
            font-weight: bold;
        }
        .client-info p {
            margin: 0;
            font-size: 0.9rem;
            color: #999;
        }

        /* Timing Block */
        .timing-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .timing-label {
            width: 50px;
            color: #999;
        }
        .timing-value {
            color: #fff;
            font-weight: 500;
        }
        .timing-value.highlight { color: #3498db; }

        /* Payment Section */
        .payment-section {
            background-color: #2a2a2a; /* Slightly lighter than body */
            padding: 15px;
            border-radius: 6px;
        }
        .total-paid-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .total-label { color: #ccc; }
        .total-amount { 
            color: #ffc107; 
            font-weight: bold; 
            font-size: 1.1rem; 
        }

        /* Payment Table (Mini) */
        .payment-table {
            width: 100%;
            font-size: 0.85rem;
            color: #aaa;
        }
        .payment-table th { font-weight: 600; text-align: left; padding: 5px 0; border-bottom: 1px solid #444; }
        .payment-table td { padding: 5px 0; border-bottom: 1px solid #333; color: #eee; }

        /* Footer */
        .card-footer-custom {
            padding: 15px 20px;
            text-align: center;
            border-top: 1px solid #333;
        }
        .btn-close-custom {
            background-color: #6c757d;
            color: #fff;
            border: none;
            padding: 8px 30px;
            border-radius: 20px;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-close-custom:hover { background-color: #5a6268; }

        /* Print Override */
        @media print {
            body { background: #fff; color: #000; }
            .receipt-card { box-shadow: none; border: 1px solid #ccc; max-width: 100%; background: #fff; }
            .card-header, .ref-row, .locker-block, .payment-section, .total-paid-row { 
                background: #fff; color: #000; border-color: #ccc; 
            }
            .locker-id, .total-amount, .ref-number span { color: #000 !important; }
            .btn-close-custom, .close-btn { display: none; }
            .status-badge { border: 1px solid #000; }
        }
    </style>
</head>
<body>

    <div class="receipt-card">
        <!-- Header -->
        <div class="card-header">
            <div class="header-title">
                <iconify-icon icon="mdi:ticket-confirmation"></iconify-icon> 
                <?php echo $headerTitle; ?>
            </div>
            <button class="close-btn" onclick="window.close()">
                <iconify-icon icon="mdi:close"></iconify-icon>
            </button>
        </div>

        <div class="card-body">
            <!-- Ref & Status -->
            <div class="ref-row">
                <div class="ref-number">
                    Ref: <span>#<?php echo str_pad($rental_id, 4, '0', STR_PAD_LEFT); ?></span>
                </div>
                <span class="status-badge"><?php echo $rental['status']; ?></span>
            </div>

            <!-- Grid Layout -->
            <div class="info-grid">
                <!-- Left Col (Locker) -->
                <div class="col-left">
                    <div class="section-label">
                        <iconify-icon icon="mdi:locker"></iconify-icon> LOCKER
                    </div>
                    <div class="locker-block">
                        <span class="locker-id">LOCK-<?php echo $rental['locker_id']; ?></span>
                        <span class="locker-size"><?php echo ucfirst($rental['size']); ?> Size</span>
                    </div>

                    <div class="section-label mt-3">
                        <iconify-icon icon="mdi:account"></iconify-icon> CLIENT
                    </div>
                    <div class="client-info pl-2">
                        <h6><?php echo htmlspecialchars($rental['firstname'] . ' ' . $rental['lastname']); ?></h6>
                        <p><?php echo $rental_id; ?></p>
                    </div>
                </div>

                <!-- Right Col (Timing) -->
                <div class="col-right">
                    <div class="section-label">
                        <iconify-icon icon="mdi:clock-outline"></iconify-icon> TIMING
                    </div>
                    <div class="pl-2">
                        <div class="timing-row">
                            <div class="timing-label">Start:</div>
                            <div class="timing-value"><?php echo $start->format('M d, Y'); ?></div>
                        </div>
                        <div class="timing-row">
                            <div class="timing-label">End:</div>
                            <div class="timing-value"><?php echo $end->format('M d, Y'); ?></div>
                        </div>
                        <div class="timing-row">
                            <div class="timing-label">Dur:</div>
                            <div class="timing-value highlight"><?php echo $months; ?> Month(s)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Section -->
            <div class="section-label">
                <iconify-icon icon="mdi:receipt-text-outline"></iconify-icon> PAYMENT
            </div>
            <div class="payment-section">
                <div class="total-paid-row">
                    <span class="total-label">Total Amount</span>
                    <span class="total-amount">₱ <?php echo number_format($totalPrice, 2); ?></span>
                </div>
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-right">Amt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo date('M d', strtotime($rental['rental_date'])); ?></td>
                            <td><?php echo ucfirst($rental['payment_status']); ?></td>
                            <td class="text-right">₱<?php echo number_format($totalPrice, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Barcode Section (Presumed for Cashier/Kiosk) -->
            <div class="text-center mt-4">
               <svg id="barcode"></svg>
               <p class="text-muted small mt-1"><?php echo $barcodeInstruction; ?></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="card-footer-custom">
            <button class="btn-close-custom" onclick="window.close()">Close</button>
            <button class="btn-close-custom bg-info ml-2" onclick="window.print()">Print</button>
        </div>
    </div>

    <script>
        JsBarcode("#barcode", "LOCK-<?php echo $rental['locker_id']; ?>-<?php echo $rental['rental_id']; ?>", {
            format: "CODE128",
            lineColor: "#999", /* Grayscale barcode */
            width: 1.5,
            height: 40,
            displayValue: false,
            background: "transparent"
        });
    </script>
</body>
</html>
