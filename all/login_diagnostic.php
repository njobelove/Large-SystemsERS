<?php
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth();
$pdo = getDBConnection();

echo "<h1>Login System Diagnostic</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style>";

// Check database connection
echo "<h2>1. Database Connection</h2>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p class='success'>✓ Database connection successful</p>";
} catch(PDOException $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Check users table
echo "<h2>2. Users Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check for required columns
    $requiredColumns = ['username', 'password', 'role', 'registration_status'];
    $missingColumns = [];
    $existingColumns = array_column($columns, 'Field');

    foreach ($requiredColumns as $col) {
        if (!in_array($col, $existingColumns)) {
            $missingColumns[] = $col;
        }
    }

    if (empty($missingColumns)) {
        echo "<p class='success'>✓ All required columns present</p>";
    } else {
        echo "<p class='error'>✗ Missing columns: " . implode(', ', $missingColumns) . "</p>";
    }

} catch(PDOException $e) {
    echo "<p class='error'>✗ Error checking table structure: " . $e->getMessage() . "</p>";
}

// Check user statistics
echo "<h2>3. User Statistics</h2>";
try {
    $stmt = $pdo->query("SELECT registration_status, COUNT(*) as count FROM users GROUP BY registration_status");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td>" . $stat['registration_status'] . "</td>";
        echo "<td>" . $stat['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check for approved users
    $approvedCount = 0;
    foreach ($stats as $stat) {
        if (strtolower($stat['registration_status']) === 'approved') {
            $approvedCount = $stat['count'];
        }
    }

    if ($approvedCount > 0) {
        echo "<p class='success'>✓ Found $approvedCount approved users</p>";
    } else {
        echo "<p class='warning'>⚠ No approved users found</p>";
    }

} catch(PDOException $e) {
    echo "<p class='error'>✗ Error getting user statistics: " . $e->getMessage() . "</p>";
}

// Test login with sample data
echo "<h2>4. Login Test</h2>";
if (isset($_GET['test_user']) && isset($_GET['test_pass']) && isset($_GET['test_role'])) {
    $testUser = $_GET['test_user'];
    $testPass = $_GET['test_pass'];
    $testRole = $_GET['test_role'];

    echo "<h3>Testing login for: $testUser ($testRole)</h3>";

    // Check if user exists
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND role = :role");
        $stmt->bindParam(':username', $testUser);
        $stmt->bindParam(':role', $testRole);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo "<p class='success'>✓ User found in database</p>";
            echo "<p>Status: " . $user['registration_status'] . "</p>";

            // Test password
            if (password_verify($testPass, $user['password'])) {
                echo "<p class='success'>✓ Password verification successful</p>";
            } elseif (md5($testPass) === $user['password']) {
                echo "<p class='warning'>⚠ Password uses MD5 hash (will be updated on login)</p>";
            } else {
                echo "<p class='error'>✗ Password verification failed</p>";
            }

            // Test auth login
            $loginResult = $auth->login($testUser, $testPass, $testRole);
            if ($loginResult) {
                echo "<p class='success'>✓ Auth login successful</p>";
            } else {
                echo "<p class='error'>✗ Auth login failed</p>";
            }
        } else {
            echo "<p class='error'>✗ User not found</p>";
        }
    } catch(PDOException $e) {
        echo "<p class='error'>✗ Error during login test: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Use URL parameters to test login: ?test_user=username&test_pass=password&test_role=role</p>";
}

// Check registration_requests table
echo "<h2>5. Registration Requests Table</h2>";
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'registration_requests'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p class='success'>✓ registration_requests table exists</p>";

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM registration_requests");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total registration requests: " . $count['count'] . "</p>";
    } else {
        echo "<p class='warning'>⚠ registration_requests table does not exist</p>";
        echo "<p>This may cause issues with the admin approval system</p>";
    }
} catch(PDOException $e) {
    echo "<p class='error'>✗ Error checking registration_requests table: " . $e->getMessage() . "</p>";
}

// Recommendations
echo "<h2>6. Recommendations</h2>";
echo "<ul>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE registration_status = 'approved'");
    $approved = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($approved['count'] == 0) {
        echo "<li class='warning'>No approved users found. Make sure to approve users through admin_approval.php</li>";
    }

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE registration_status = 'pending'");
    $pending = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pending['count'] > 0) {
        echo "<li class='warning'>{$pending['count']} users are pending approval</li>";
    }

} catch(PDOException $e) {
    echo "<li class='error'>Unable to check user approval status</li>";
}

echo "<li>Check PHP error logs for detailed error messages</li>";
echo "<li>Ensure database credentials in config.php are correct</li>";
echo "<li>Verify that approved users have correct passwords</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Debug Information:</strong></p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Database: " . DB_NAME . "</p>";
?>
