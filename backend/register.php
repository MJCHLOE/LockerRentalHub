<?php
session_start();

require_once "../db/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $passwordInput = trim($_POST["password"]);
    $confirmPassword = trim($_POST["confirm_password"]);
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $email = trim($_POST["email"]);
    $phone_number = trim($_POST["phone_number"]);

    if ($passwordInput !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    // Hash the password using password_hash
    $hashedPassword = password_hash($passwordInput, PASSWORD_BCRYPT);

    // Prepare SQL to insert new user
    // TEMPORARY: Set role to 'Admin' for registration
    $stmt = $conn->prepare("INSERT INTO users (username, password, firstname, lastname, email, phone_number, role) VALUES (?, ?, ?, ?, ?, ?, 'Admin')");

    if (!$stmt) {
        die("Prepare failed (Registration): (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("ssssss", $username, $hashedPassword, $firstname, $lastname, $email, $phone_number);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='../LoginPage.html';</script>";
    } else {
        echo "<script>alert('Error during registration: " . $conn->error . "'); window.location.href='../RegisterPage.html';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
