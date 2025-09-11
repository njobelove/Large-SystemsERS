<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default XAMPP user
define('DB_PASS', ''); // Default XAMPP password (empty)
define('DB_NAME', 'academic_system');

// Create database connection
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }

            $conn->set_charset("utf8");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    return $conn;
}

// Close database connection
function closeDBConnection($conn = null) {
    if ($conn !== null) {
        $conn->close();
    } else {
        $conn = getDBConnection();
        $conn->close();
    }
}
?>
