<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Registration System Test</h2>";

// Test data
$testUser = [
    "username" => "testuser_" . time(),
    "password" => "Test123!",
    "firstname" => "Test",
    "lastname" => "User",
    "email" => "test" . time() . "@example.com",
    "phone_number" => "1234567890"
];

// Database connection
require_once "db/database.php";

try {
    // Test 1: Database Connection
    echo "<h3>1. Testing Database Connection</h3>";
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<p style='color: green'>✓ Database connection successful!</p>";

    // Test 2: Password Hashing
    echo "<h3>2. Testing Password Hashing</h3>";
    $hashedPassword = password_hash($testUser["password"], PASSWORD_BCRYPT);
    if (password_verify($testUser["password"], $hashedPassword)) {
        echo "<p style='color: green'>✓ Password hashing working correctly!</p>";
    } else {
        throw new Exception("Password hashing verification failed");
    }

    // Test 3: User Registration
    echo "<h3>3. Testing User Registration</h3>";
    $stmt = $conn->prepare("INSERT INTO Users (username, password, firstname, lastname, email, phone_number, role) VALUES (?, ?, ?, ?, ?, ?, 'Client')");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssss", 
        $testUser["username"],
        $hashedPassword,
        $testUser["firstname"],
        $testUser["lastname"],
        $testUser["email"],
        $testUser["phone_number"]
    );

    if ($stmt->execute()) {
        echo "<p style='color: green'>✓ Test user registration successful!</p>";
        
        // Verify the user was actually inserted
        $verify_stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $verify_stmt->bind_param("s", $testUser["username"]);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<p style='color: green'>✓ User verification successful!</p>";
            
            // Clean up - delete test user
            $delete_stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
            $delete_stmt->bind_param("s", $testUser["username"]);
            if ($delete_stmt->execute()) {
                echo "<p style='color: blue'>ℹ Test user cleaned up from database</p>";
            }
        } else {
            throw new Exception("User verification failed");
        }
    } else {
        throw new Exception("User registration failed: " . $stmt->error);
    }

} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold'>✗ ERROR: " . $e->getMessage() . "</p>";
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($verify_stmt)) $verify_stmt->close();
    if (isset($delete_stmt)) $delete_stmt->close();
    $conn->close();
}

// Display test parameters
echo "<h3>Test Parameters:</h3>";
echo "<ul>";
foreach ($testUser as $key => $value) {
    if ($key === "password") {
        echo "<li>$key: " . str_repeat("*", strlen($value)) . "</li>";
    } else {
        echo "<li>$key: $value</li>";
    }
}
echo "</ul>";

// Display server info
echo "<h3>PHP & Server Information:</h3>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>Script Path: " . $_SERVER['SCRIPT_FILENAME'] . "</li>";
echo "</ul>";
?>