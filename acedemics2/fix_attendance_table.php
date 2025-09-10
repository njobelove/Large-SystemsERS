<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if 'updated_at' column exists in attendance table
$result = $conn->query("SHOW COLUMNS FROM attendance LIKE 'updated_at'");
if ($result->num_rows === 0) {
    // Add 'updated_at' column
    $alterSql = "ALTER TABLE attendance ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    if ($conn->query($alterSql) === TRUE) {
        echo "Column 'updated_at' added successfully to attendance table.\n";
    } else {
        echo "Error adding column 'updated_at': " . $conn->error . "\n";
    }
} else {
    echo "Column 'updated_at' already exists in attendance table.\n";
}

$conn->close();
?>
