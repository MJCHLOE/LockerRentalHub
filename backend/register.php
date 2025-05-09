<?php
session_start();

require '../db/database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if the connection is valid
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Sanitize inputs
    $username = mysqli_real_escape_string($conn, strtolower(trim($_POST["username"])));
    $passwordInput = trim($_POST["password"]);
    $firstname = mysqli_real_escape_string($conn, trim($_POST["firstname"]));
    $lastname = mysqli_real_escape_string($conn, trim($_POST["lastname"]));
    $email = mysqli_real_escape_string($conn, strtolower(trim($_POST["email"])));
    $phone_number = mysqli_real_escape_string($conn, trim($_POST["phone_number"]));

    // Check if username exists
    $checkUsername = $conn->prepare("SELECT username FROM Users WHERE username = ?");
    $checkUsername->bind_param("s", $username);
    $checkUsername->execute();
    $usernameResult = $checkUsername->get_result();

    if ($usernameResult->num_rows > 0) {
        echo "<script>alert('Username already exists!'); window.location.href='../RegisterPage.html';</script>";
        $checkUsername->close();
        $conn->close();
        exit();
    }
    $checkUsername->close();

    // Check if email exists
    $checkEmail = $conn->prepare("SELECT email FROM Users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $emailResult = $checkEmail->get_result();

    if ($emailResult->num_rows > 0) {
        echo "<script>alert('Email already registered!'); window.location.href='../RegisterPage.html';</script>";
        $checkEmail->close();
        $conn->close();
        exit();
    }
    $checkEmail->close();

    // Hash the password
    $hashedPassword = password_hash($passwordInput, PASSWORD_BCRYPT);

    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert into Users table
        $stmt = $conn->prepare("INSERT INTO Users (username, password, firstname, lastname, email, phone_number, role) VALUES (?, ?, ?, ?, ?, ?, 'Client')");
        $stmt->bind_param("ssssss", $username, $hashedPassword, $firstname, $lastname, $email, $phone_number);
        
        if ($stmt->execute()) {
            $conn->commit();
            echo "<script>alert('Registration successful!'); window.location.href='../LoginPage.html';</script>";
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error during registration: " . $e->getMessage() . "'); window.location.href='../RegisterPage.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
