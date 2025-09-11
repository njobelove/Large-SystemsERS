<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../include/auth_check.php';
checkRoleAccess(['student']);

// Determine the current page to set the active link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Portal</title>
    <link rel="stylesheet" href="../css/new_style.css" />
</head>
<body>
    <header class="top-bar">
        <h1>Student Portal</h1>
        <div class="user-info">
            Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Student') ?> |
            <a href="../logout.php" style="color: white;">Logout</a>
        </div>
    </header>
    <nav class="top-nav">
        <ul>
            <li><a href="student_dashboard.php" class="<?= $current_page == 'student_dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="invoices.php" class="<?= $current_page == 'invoices.php' ? 'active' : '' ?>">My Invoices</a></li>
            <li><a href="payments.php" class="<?= $current_page == 'payments.php' ? 'active' : '' ?>">My Payments</a></li>
            <li><a href="submit_payment.php" class="<?= $current_page == 'submit_payment.php' ? 'active' : '' ?>">Submit Payment</a></li>
        </ul>
    </nav>
    <main class="main-content">
