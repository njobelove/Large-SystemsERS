<?php
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth();
$pdo = getDBConnection();

echo "<h1>Login Debug Information</h1>";

// Check database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Database connection: OK</p>";
    echo "<p>Total users in database: " . $result['count'] . "</p>";
} catch(PDOException $e) {
    echo "<p>Database connection: FAILED - " . $e->getMessage() . "</p>";
}

// Check users table structure
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Users Table Structure:</h2>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
    }
    echo "</ul>";
} catch(PDOException $e) {
    echo "<p>Error getting table structure: " . $e->getMessage() . "</p>";
}

// Show sample users (without passwords)
try {
    $stmt = $pdo->query("SELECT id, username, full_name, role, registration_status, email FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Sample Users:</h2>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th><th>Email</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['full_name'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . $user['registration_status'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch(PDOException $e) {
    echo "<p>Error getting users: " . $e->getMessage() . "</p>";
}

// Test login with a specific user (if provided)
if (isset($_GET['test_username']) && isset($_GET['test_password']) && isset($_GET['test_role'])) {
    $test_username = $_GET['test_username'];
    $test_password = $_GET['test_password'];
    $test_role = $_GET['test_role'];

    echo "<h2>Testing Login:</h2>";
    echo "<p>Username: $test_username</p>";
    echo "<p>Role: $test_role</p>";

    // Check if user exists
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND role = :role");
        $stmt->bindParam(':username', $test_username);
        $stmt->bindParam(':role', $test_role);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo "<p>User found in database: YES</p>";
            echo "<p>Registration status: " . $user['registration_status'] . "</p>";

            // Test password verification
            if (password_verify($test_password, $user['password'])) {
                echo "<p>Password verification: SUCCESS</p>";
            } else {
                echo "<p>Password verification: FAILED</p>";
                echo "<p>Stored hash: " . substr($user['password'], 0, 20) . "...</p>";
            }

            // Test auth login method
            $auth_result = $auth->login($test_username, $test_password, $test_role);
            echo "<p>Auth login result: " . ($auth_result ? "SUCCESS" : "FAILED") . "</p>";
        } else {
            echo "<p>User found in database: NO</p>";
        }
    } catch(PDOException $e) {
        echo "<p>Error testing login: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h2>Test Login:</h2>";
    echo "<p>Use URL parameters: ?test_username=your_username&test_password=your_password&test_role=your_role</p>";
}
?>
