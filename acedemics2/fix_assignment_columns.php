<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Fixing Assignment Database Columns</h1>";

// Add file_path column to assignments table if not exists
$result = $conn->query("SHOW COLUMNS FROM assignments LIKE 'file_path'");
if ($result && $result->num_rows == 0) {
    $alterSql = "ALTER TABLE assignments ADD COLUMN file_path VARCHAR(255) DEFAULT NULL";
    if ($conn->query($alterSql) === TRUE) {
        echo "<p style='color: green;'>✓ file_path column added to assignments table successfully.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding file_path column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ file_path column already exists in assignments table.</p>";
}

// Add grade column to submissions table if not exists
$result = $conn->query("SHOW COLUMNS FROM submissions LIKE 'grade'");
if ($result && $result->num_rows == 0) {
    $alterSql = "ALTER TABLE submissions ADD COLUMN grade VARCHAR(10) DEFAULT NULL";
    if ($conn->query($alterSql) === TRUE) {
        echo "<p style='color: green;'>✓ grade column added to submissions table successfully.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding grade column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ grade column already exists in submissions table.</p>";
}

// Add graded column to submissions table if not exists
$result = $conn->query("SHOW COLUMNS FROM submissions LIKE 'graded'");
if ($result && $result->num_rows == 0) {
    $alterSql = "ALTER TABLE submissions ADD COLUMN graded TINYINT(1) DEFAULT 0";
    if ($conn->query($alterSql) === TRUE) {
        echo "<p style='color: green;'>✓ graded column added to submissions table successfully.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding graded column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ graded column already exists in submissions table.</p>";
}

$conn->close();

echo "<p><a href='instructor/create-assignment.php'>Go to Create Assignment</a></p>";
?>
