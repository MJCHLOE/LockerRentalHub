<?php
// Display all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details
$host = "194.59.164.68";
$user = "u130348899_u130348899_";
$password = "LockerRentThing_69"; 
$dbname = "u130348899_LockerRental";

echo "<h2>Database Connection Test</h2>";

// Attempt to connect
try {
    $conn = new mysqli($host, $user, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green; font-weight: bold;'>✓ Database connection successful!</p>";
    
    // Test query - get table count (this is a simple query that should work on any database)
    $query = "SHOW TABLES";
    $result = $conn->query($query);
    
    if ($result) {
        echo "<p>Tables in the database:</p>";
        echo "<ul>";
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
        } else {
            echo "<li>No tables found</li>";
        }
        
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>Query failed: " . $conn->error . "</p>";
    }
    
    // Test Users table specifically
    $query = "SELECT COUNT(*) as count FROM Users";
    $result = $conn->query($query);
    
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Number of users in the Users table: " . $row['count'] . "</p>";
    } else {
        echo "<p style='color: red;'>Users table query failed: " . $conn->error . "</p>";
        echo "<p>This could indicate the Users table doesn't exist or there's a permissions issue.</p>";
    }
    
    // Close connection
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ ERROR: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database credentials and make sure the MySQL server is running and accessible.</p>";
}

// Display the current connection parameters (for verification)
echo "<h3>Current Connection Parameters:</h3>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>User: $user</li>";
echo "<li>Password: " . str_repeat("*", strlen($password)) . "</li>";
echo "<li>Database: $dbname</li>";
echo "</ul>";

// Display server info
echo "<h3>PHP & Server Information:</h3>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li>Script Path: " . $_SERVER['SCRIPT_FILENAME'] . "</li>";
echo "</ul>";
?>