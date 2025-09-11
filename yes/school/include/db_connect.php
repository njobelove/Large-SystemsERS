<?php
// db_connect.php

$servername = "localhost";
$username = "root"; // change if different
$password = ""; // change if different
$dbname = "erp_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to avoid encoding issues
$conn->set_charset("utf8mb4");
?>
