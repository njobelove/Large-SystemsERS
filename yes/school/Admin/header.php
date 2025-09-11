<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../include/auth_check.php';
checkRoleAccess(['admin']);

// Determine the current page to set the active link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Portal</title>
    <link rel="stylesheet" href="../css/finance_style.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f8f9fa;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-weight: 600;
        }
        nav a.active {
            text-decoration: underline;
        }
        nav a:hover {
            opacity: 0.8;
        }
        .container {
            max-width: 960px;
            margin: 20px auto;
            padding: 0 15px;
        }
        @media (max-width: 600px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }
            nav {
                margin-top: 10px;
            }
            nav a {
                margin-left: 0;
                margin-right: 15px;
            }
        }
    </style>
</head>
<body>
<header>
    <h1>Admin Portal</h1>
    <nav>
        <!-- Removed Dashboard link as per request -->
        <a href="user_management.php" class="<?= $current_page == 'user_management.php' ? 'active' : '' ?>">User Management</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>
<div class="container">
</div>
</body>
</html>
