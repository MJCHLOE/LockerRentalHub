<?php
session_start();

require "../db/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Sanitize and validate inputs
        $username = strtolower(trim($_POST["username"])); // Convert username to lowercase
        $passwordInput = trim($_POST["password"]);
        $firstname = trim($_POST["firstname"]);
        $lastname = trim($_POST["lastname"]);
        $email = strtolower(trim($_POST["email"])); // Convert email to lowercase
        $phone_number = trim($_POST["phone_number"]);

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Validate phone number format (11 digits)
        if (!preg_match("/^[0-9]{11}$/", $phone_number)) {
            throw new Exception("Phone number must be 11 digits");
        }

        // Check if username already exists
        $checkUser = $conn->prepare("SELECT username FROM users WHERE LOWER(username) = LOWER(?)");
        $checkUser->bind_param("s", $username);
        $checkUser->execute();
        $result = $checkUser->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Username already exists");
        }
        $checkUser->close();

        // Check if email already exists
        $checkEmail = $conn->prepare("SELECT email FROM users WHERE LOWER(email) = LOWER(?)");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Email already registered");
        }
        $checkEmail->close();

        // Hash the password
        $hashedPassword = password_hash($passwordInput, PASSWORD_BCRYPT);

        // Prepare SQL to insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, password, firstname, lastname, email, phone_number, role) VALUES (?, ?, ?, ?, ?, ?, 'Client')");
        
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssssss", $username, $hashedPassword, $firstname, $lastname, $email, $phone_number);

        if (!$stmt->execute()) {
            throw new Exception("Registration failed: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();

        // Registration successful
        echo "<script>
                alert('Registration successful!');
                window.location.href='../LoginPage.html';
              </script>";

    } catch (Exception $e) {
        $conn->close();
        echo "<script>
                alert('Error: " . addslashes($e->getMessage()) . "');
                window.location.href='../RegisterPage.html';
              </script>";
    }
} else {
    // If not POST request, redirect to registration page
    header("Location: ../RegisterPage.html");
    exit();
}
?>
