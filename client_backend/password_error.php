<?php
session_start();

// If there's no error message, redirect to homepage
if (!isset($_SESSION['error_message'])) {
    header('Location: ../index.php');
    exit();
}

$errorMessage = $_SESSION['error_message'];
// Keep the message in session so it can be displayed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Change Error</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .error-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff0f0;
            border: 1px solid #e74c3c;
            border-radius: 5px;
            text-align: center;
        }
        .error-message {
            color: #e74c3c;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .countdown {
            font-size: 16px;
            color: #555;
        }
        .actions {
            margin-top: 20px;
        }
        .actions a {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            text-decoration: none;
            color: #fff;
            background-color: #3498db;
            border-radius: 4px;
        }
        .actions a:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h2>Password Change Error</h2>
        <div class="error-message">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
        <div class="countdown">
            You will be redirected to the home page in <span id="seconds">5</span> seconds...
        </div>
        <div class="actions">
            <a href="change_password.php">Try Again</a>
            <a href="home.php">Go to Home</a>
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
                window.location.href = 'home.php';
            }
        }, 1000);
    </script>
</body>
</html>