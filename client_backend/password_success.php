<?php
session_start();

// If there's no success message, redirect to homepage
if (!isset($_SESSION['success_message'])) {
    header('Location: ../index.php');
    exit();
}

$successMessage = $_SESSION['success_message'];
// Keep the message in session so it can be displayed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .success-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 20px;
            background-color: #f0fff0;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            text-align: center;
        }
        .success-message {
            color: #4CAF50;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .countdown {
            font-size: 16px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <h2>Success!</h2>
        <div class="success-message">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
        <div class="countdown">
            You will be logged out in <span id="seconds">5</span> seconds...
        </div>
    </div>

    <script>
        // Countdown timer
        let seconds = 5;
        const countdown = setInterval(function() {
            seconds--;
            document.getElementById('seconds').textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = '../backend/logout.php';
            }
        }, 1000);
    </script>
</body>
</html>