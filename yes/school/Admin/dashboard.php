<?php
session_start();
include '../include/auth_check.php';
checkRoleAccess(['admin']);
include '../include/db_connect.php';

// Simple admin dashboard with links to fee management and user management
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/new_style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #4a90e2, #50e3c2);
            margin: 0;
            padding: 20px;
            color: #fff;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            text-align: center;
        }
        h1 {
            color: #e0f7fa;
            margin-bottom: 24px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        p {
            color: #e0f7fa;
        }
        a.button {
            background: linear-gradient(45deg, #4a90e2, #50e3c2);
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            margin: 10px 15px;
            box-shadow: 0 4px 12px rgba(74,144,226,0.6);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }
        a.button:hover {
            background: linear-gradient(45deg, #357ABD, #3bb9a9);
            box-shadow: 0 6px 16px rgba(53,122,189,0.8);
            color: #e0f2f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
        <a href="fee_management.php" class="button">Manage Fees</a>
        <a href="assign_fees.php" class="button">Assign Fees to Students</a>
        <a href="user_management.php" class="button">Manage Users</a>
        <a href="/school/logout.php" class="button">Logout</a>
    </div>
</body>
</html>
