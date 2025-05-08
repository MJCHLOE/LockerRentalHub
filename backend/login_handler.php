<?php
session_start();

// Include database connection file
require_once '../db/database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $passwordInput = trim($_POST["password"]);

    // Check user credentials
    $stmt = $conn->prepare("SELECT user_id, username, password, role, firstname, lastname FROM Users WHERE username = ?");
    
    if (!$stmt) {
        die("Prepare failed (Login): (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $usernameDB, $hashedPassword, $role, $firstname, $lastname);
        $stmt->fetch();

        if (password_verify($passwordInput, $hashedPassword)) {
            // Set session variables
            $_SESSION["user_id"] = $user_id;
            $_SESSION["username"] = $usernameDB;
            $_SESSION["role"] = $role;
            $_SESSION["firstname"] = $firstname;
            $_SESSION["lastname"] = $lastname;

            // Redirect based on role
            if ($role === "Admin") {
                header("Location: ../admin/dashboard.php");
                exit();
            } elseif ($role === "Staff") {
                header("Location: ../staff/dashboard.php");
                exit();
            } else {
                header("Location: ../client/home.php");
                exit();
            }
        } else {
            echo "<script>alert('Invalid password.'); window.location.href='../LoginPage.html';</script>";
        }
    } else {
        echo "<script>alert('Username not found.'); window.location.href='../LoginPage.html';</script>";
    }

    $stmt->close();
}

$conn->close();
?>