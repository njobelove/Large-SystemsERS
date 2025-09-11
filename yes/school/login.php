<?php
session_start();
include 'include/db_connect.php'; // Include your DB connection script

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $errors[] = "Please enter both username and password.";
    } else {
        // Prepare and execute query
$stmt = $conn->prepare("SELECT id, password_hash AS password, role, username FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Login success: store user info in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];

                // Redirect based on role
                switch ($user['role']) {
                    case 'student':
                        header("Location: student/dashboard.php");
                        break;
                    case 'finance':
                        header("Location: finance/dashboard.php");
                        break;
case 'marketing':
                header("Location: marketing/marketing_dashboard.php"); // Corrected to actual directory name
                break;
                    case 'admin':
                        header("Location: Admin/dashboard.php");
                        break;
                    default:
                        // Fallback for undefined roles
                        header("Location: login.php");
                }
                exit;
            } else {
                // Generic error for security
                $errors[] = "Invalid username or password.";
            }
        } else {
            // Generic error for security
            $errors[] = "Invalid username or password.";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/new_style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Login</h1>
        </div>

        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

<form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <p>
                Don't have an account? <a href="signup.php">Sign up here</a>
            </p>
        </div>
    </div>
</body>
</html>