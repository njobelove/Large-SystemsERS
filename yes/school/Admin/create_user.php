<?php
require_once '../include/auth_check.php';
checkRoleAccess(['admin']);
require_once '../include/db_connect.php';

$username = $email = $role = $password = $confirm_password = "";
$username_err = $email_err = $role_err = $password_err = $confirm_password_err = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = trim($_POST["username"]);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Invalid email format.";
        }
    }

    // Validate role
    if (empty(trim($_POST["role"]))) {
        $role_err = "Please select a role.";
    } else {
        $role = trim($_POST["role"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }

    // Check input errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($role_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $param_username, $param_email, $param_password, $param_role);
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_role = $role;

            if ($stmt->execute()) {
                $success_message = "User created successfully.";
                // Clear form data
                $username = $email = $role = $password = $confirm_password = "";
            } else {
                echo "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create User</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #4a90e2, #50e3c2);
            margin: 0;
            padding: 20px;
            color: #fff;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        h1 {
            color: #e0f7fa;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .success-message {
            background-color: #a7ffeb;
            color: #004d40;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #4a90e2;
            text-shadow: 0 1px 2px rgba(0,77,64,0.5);
        }
        form label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #a7ffeb;
            text-shadow: 0 1px 2px rgba(0,77,64,0.5);
        }
        form input[type="text"],
        form input[type="email"],
        form input[type="password"],
        form select {
            width: 100%;
            padding: 8px 12px;
            margin-bottom: 15px;
            border: 1px solid #4a90e2;
            border-radius: 8px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.15);
            color: #e0f7fa;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
            transition: background 0.3s ease;
        }
        form input[type="text"]::placeholder,
        form input[type="email"]::placeholder,
        form input[type="password"]::placeholder,
        form select::placeholder {
            color: #b2dfdb;
        }
        form input[type="text"]:focus,
        form input[type="email"]:focus,
        form input[type="password"]:focus,
        form select:focus {
            background: rgba(255, 255, 255, 0.3);
            outline: none;
        }
        form .error {
            color: #d9534f;
            margin-top: -10px;
            margin-bottom: 10px;
            font-size: 13px;
            text-shadow: 0 1px 2px rgba(77,0,0,0.5);
        }
        form button {
            background: linear-gradient(45deg, #4a90e2, #50e3c2);
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(74,144,226,0.6);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }
        form button:hover {
            background: linear-gradient(45deg, #357ABD, #3bb9a9);
            box-shadow: 0 6px 16px rgba(53,122,189,0.8);
            color: #e0f2f1;
        }
        p.back-link {
            text-align: center;
            margin-top: 20px;
        }
        p.back-link a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: bold;
            text-shadow: 0 1px 2px rgba(0,77,64,0.5);
        }
        p.back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create New User</h1>

        <?php if (!empty($success_message)): ?>
            <p class="success-message"><?= $success_message ?></p>
        <?php endif; ?>

        <form action="create_user.php" method="post">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>">
            <?php if (!empty($username_err)): ?><div class="error"><?= $username_err ?></div><?php endif; ?>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>">
            <?php if (!empty($email_err)): ?><div class="error"><?= $email_err ?></div><?php endif; ?>

            <label for="role">Role</label>
            <select name="role" id="role">
                <option value="">Select Role</option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="finance" <?= $role === 'finance' ? 'selected' : '' ?>>Finance</option>
                <option value="marketing" <?= $role === 'marketing' ? 'selected' : '' ?>>Marketing</option>
                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
            </select>
            <?php if (!empty($role_err)): ?><div class="error"><?= $role_err ?></div><?php endif; ?>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" value="">
            <?php if (!empty($password_err)): ?><div class="error"><?= $password_err ?></div><?php endif; ?>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" value="">
            <?php if (!empty($confirm_password_err)): ?><div class="error"><?= $confirm_password_err ?></div><?php endif; ?>

            <button type="submit">Create User</button>
        </form>

        <p class="back-link"><a href="user_management.php">Back to User Management</a></p>
    </div>
</body>
</html>
