<?php
session_start();

$errors = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Hardcoded credentials
    $validUsers = [
        'Prof. Jane Doe' => 'jane123',
        'Dr. John Smith' => 'john123'
    ];

    if (isset($validUsers[$username]) && $validUsers[$username] === $password) {
        // Set session variables
        $_SESSION['user_role'] = 'instructor';
        $_SESSION['user_name'] = $username;
        $_SESSION['user_id'] = $username === 'Prof. Jane Doe' ? 2 : 3; // Assuming IDs from DB

        // Redirect to instructor dashboard
        header('Location: dashboard.php');
        exit();
    } else {
        $errors = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Instructor Login</title>
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 320px;
        }
        .login-container h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .login-container label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }
        .login-container .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Instructor Login</h2>
        <?php if ($errors): ?>
            <div class="error"><?php echo htmlspecialchars($errors); ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="username">Name</label>
            <input type="text" id="username" name="username" required placeholder="Enter your name" />
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password" />
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
