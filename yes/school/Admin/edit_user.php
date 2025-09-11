<?php
session_start();
include '../include/auth_check.php';
checkRoleAccess(['admin']);
include '../include/db_connect.php';

$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: user_management.php?message=Invalid user ID");
    exit;
}

$username = $email = $role = "";
$available_roles = ['admin', 'finance', 'marketing', 'student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!in_array($role, $available_roles)) {
        $errors[] = "Invalid role selected.";
    }

    if (empty($errors)) {
        $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $username, $email, $role, $id);
            if ($stmt->execute()) {
                header("Location: user_management.php?message=User updated successfully");
                exit;
            } else {
                $errors[] = "Failed to update user.";
            }
            $stmt->close();
        }
    }
} else {
    $sql = "SELECT username, email, role FROM users WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($username, $email, $role);
        if (!$stmt->fetch()) {
            header("Location: user_management.php?message=User not found");
            exit;
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
    <title>Edit User</title>
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
        .errors {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-shadow: 0 1px 2px rgba(77,0,0,0.5);
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
        form select::placeholder {
            color: #b2dfdb;
        }
        form input[type="text"]:focus,
        form input[type="email"]:focus,
        form select:focus {
            background: rgba(255, 255, 255, 0.3);
            outline: none;
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
        <h1>Edit User</h1>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="edit_user.php?id=<?= $id ?>" method="post">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>">

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>">

            <label for="role">Role</label>
            <select id="role" name="role">
                <?php foreach ($available_roles as $r): ?>
                    <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Update User</button>
        </form>

        <p class="back-link"><a href="user_management.php">Back to User Management</a></p>
    </div>
</body>
</html>
