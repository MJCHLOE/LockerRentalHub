<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../db/database.php';

echo "<h1>Rentals Table Debugger</h1>";

$tableName = 'rentals'; // We know the table is likely 'rentals' now
$sql = "SHOW COLUMNS FROM $tableName";
$result = $conn->query($sql);

if ($result) {
    echo "<h2>Columns in '$tableName':</h2>";
    echo "<ul>";
    while($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<h2>Table '$tableName' check failed: " . $conn->error . "</h2>";
    
    // Fallback check for 'rental' just in case
    $tableName = 'rental';
    $sql = "SHOW COLUMNS FROM $tableName";
    $result = $conn->query($sql);
    if ($result) {
        echo "<h2>Columns in '$tableName' (Singular):</h2>";
        echo "<ul>";
        while($row = $result->fetch_assoc()) {
            echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
        }
        echo "</ul>";
    } else {
         echo "<h2>Table '$tableName' check failed: " . $conn->error . "</h2>";
    }
}

$conn->close();
?>
