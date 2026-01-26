<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../db/database.php';

echo "<h1>Archive Table Debugger</h1>";

$tablesToCheck = ['rental_archives', 'rental_archive', 'rentals_archive'];

foreach ($tablesToCheck as $tableName) {
    echo "<h2>Checking '$tableName'...</h2>";
    $sql = "SHOW TABLES LIKE '$tableName'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<p style='color:green'>Found table: <strong>$tableName</strong></p>";
        $colSql = "SHOW COLUMNS FROM $tableName";
        $colResult = $conn->query($colSql);
        if ($colResult) {
            echo "<ul>";
            while($row = $colResult->fetch_assoc()) {
                echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color:red'>Table '$tableName' NOT found.</p>";
    }
}

$conn->close();
?>
