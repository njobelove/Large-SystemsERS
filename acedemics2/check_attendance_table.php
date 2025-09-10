<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if 'notes' column exists in attendance table
$result = $conn->query("SHOW COLUMNS FROM attendance LIKE 'notes'");
if ($result->num_rows === 0) {
    // Add 'notes' column
    $alterSql = "ALTER TABLE attendance ADD COLUMN notes TEXT NULL";
    if ($conn->query($alterSql) === TRUE) {
        echo "Column 'notes' added successfully to attendance table.\n";
    } else {
        echo "Error adding column 'notes': " . $conn->error . "\n";
    }
} else {
    echo "Column 'notes' already exists in attendance table.\n";
}

$conn->close();
?>
