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

require_once "db/database.php";

try {
    // Start transaction
    $conn->begin_transaction();

    // First insert into users table
    $stmt = $conn->prepare("INSERT INTO users (username, password, firstname, lastname, email, phone_number, role) VALUES (?, ?, ?, ?, ?, ?, 'Client')");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $hashedPassword = password_hash($testUser["password"], PASSWORD_BCRYPT);
    $stmt->bind_param("ssssss", 
        $testUser["username"],
        $hashedPassword,
        $testUser["firstname"],
        $testUser["lastname"],
        $testUser["email"],
        $testUser["phone_number"]
    );

    if ($stmt->execute()) {
        echo "<p style='color: green'>✓ User table insert successful!</p>";
        
        // Get the inserted user_id
        $user_id = $conn->insert_id;
        
        // Then insert into clients table
        $fullname = $testUser["firstname"] . " " . $testUser["lastname"];
        $client_stmt = $conn->prepare("INSERT INTO clients (user_id, full_name) VALUES (?, ?)");
        $client_stmt->bind_param("is", $user_id, $fullname);
        
        if ($client_stmt->execute()) {
            echo "<p style='color: green'>✓ Client table insert successful!</p>";
            $conn->commit();
            echo "<p style='color: green'>✓ Transaction committed successfully!</p>";
        } else {
            throw new Exception("Client insert failed: " . $client_stmt->error);
        }
    } else {
        throw new Exception("User insert failed: " . $stmt->error);
    }

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo "<p style='color: red; font-weight: bold'>✗ ERROR: " . $e->getMessage() . "</p>";
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($client_stmt)) $client_stmt->close();
    if (isset($conn)) $conn->close();
}

// Display test parameters
echo "<h3>Test Parameters:</h3>";
echo "<ul>";
foreach ($testUser as $key => $value) {
    echo "<li>$key: " . ($key === "password" ? str_repeat("*", strlen($value)) : $value) . "</li>";
}
echo "</ul>";
?>