<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add file_path column to assignments table if not exists
$result = $conn->query("SHOW COLUMNS FROM assignments LIKE 'file_path'");
if ($result && $result->num_rows == 0) {
    $alterSql = "ALTER TABLE assignments ADD COLUMN file_path VARCHAR(255) DEFAULT NULL";
    if ($conn->query($alterSql) === TRUE) {
        echo "file_path column added to assignments table successfully.\n";
    } else {
        echo "Error adding file_path column: " . $conn->error . "\n";
    }
} else {
    echo "file_path column already exists in assignments table.\n";
}

// Add grade column to submissions table if not exists
$result = $conn->query("SHOW COLUMNS FROM submissions LIKE 'grade'");
if ($result && $result->num_rows == 0) {
    $alterSql = "ALTER TABLE submissions ADD COLUMN grade VARCHAR(10) DEFAULT NULL";
    if ($conn->query($alterSql) === TRUE) {
        echo "grade column added to submissions table successfully.\n";
    } else {
        echo "Error adding grade column: " . $conn->error . "\n";
    }
} else {
    echo "grade column already exists in submissions table.\n";
}

// Add graded column to submissions table if not exists
$result = $conn->query("SHOW COLUMNS FROM submissions LIKE 'graded'");
if ($result && $result->num_rows == 0) {
    $alterSql = "ALTER TABLE submissions ADD COLUMN graded TINYINT(1) DEFAULT 0";
    if ($conn->query($alterSql) === TRUE) {
        echo "graded column added to submissions table successfully.\n";
    } else {
        echo "Error adding graded column: " . $conn->error . "\n";
    }
} else {
    echo "graded column already exists in submissions table.\n";
}

$conn->close();
?>
