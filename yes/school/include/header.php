<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/auth_check.php';
checkRoleAccess(['finance', 'admin']);

// Determine the current page to set the active link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Portal</title>
    <link rel="stylesheet" href="../css/finance_style.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            display: flex;
            flex-direction: column;
            padding: 20px;
            flex-shrink: 0;
        }
        .sidebar-header h2 {
            margin-top: 0;
            color: #fff;
            border-bottom: 1px solid #555;
            padding-bottom: 10px;
        }
        .sidebar-nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-nav li {
            margin-bottom: 5px;
        }
        .sidebar-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .sidebar-nav a:hover {
            background-color: #575757;
        }
        .sidebar-nav a.active {
            background-color: #007bff;
        }
        .sidebar-footer {
            margin-top: auto;
        }
        .sidebar-footer a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            border-radius: 4px;
            background-color: #dc3545;
            text-align: center;
        }
        .sidebar-footer a:hover {
            background-color: #c82333;
        }
        .main-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Finance Portal</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="list_invoices.php" class="<?= $current_page == 'list_invoices.php' || $current_page == 'create_invoice.php' || $current_page == 'edit_invoice.php' ? 'active' : '' ?>">Invoices</a></li>
                <li><a href="list_expenses.php" class="<?= $current_page == 'list_expenses.php' || $current_page == 'create_expense.php' || $current_page == 'edit_expense.php' ? 'active' : '' ?>">Expenses</a></li>
                <li><a href="list_payments.php" class="<?= $current_page == 'list_payments.php' ? 'active' : '' ?>">Payments</a></li>
                <li><a href="list_submissions.php" class="<?= $current_page == 'list_submissions.php' || $current_page == 'review_submission.php' ? 'active' : '' ?>">Submissions</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
