<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/auth_check.php';
checkRoleAccess(['finance', 'admin', 'marketing']);

// Determine the current page to set the active link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $_SESSION['role'] === 'marketing' ? 'Marketing Portal' : 'Finance Portal'; ?></title>
    <link rel="stylesheet" href="../css/finance_style.css" />
</head>
<body>
    <header class="top-bar">
        <h1><?php echo $_SESSION['role'] === 'marketing' ? 'Marketing Portal' : 'Finance Portal'; ?></h1>
        <div class="user-info">
            Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?> |
            <a href="../logout.php" style="color: white;">Logout</a>
        </div>
    </header>
    <nav class="top-nav">
        <ul>
            <?php if ($_SESSION['role'] === 'marketing'): ?>
                <li><a href="marketing_dashboard.php" class="<?= $current_page == 'marketing_dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="list_campaigns.php" class="<?= in_array($current_page, ['list_campaigns.php', 'create_campaign.php', 'edit_campaign.php']) ? 'active' : '' ?>">Campaigns</a></li>
                <li><a href="list_leads.php" class="<?= in_array($current_page, ['list_leads.php', 'create_lead.php', 'edit_lead.php']) ? 'active' : '' ?>">Leads</a></li>
                <li><a href="list_conversions.php" class="<?= in_array($current_page, ['list_conversions.php', 'record_conversion.php', 'edit_conversion.php']) ? 'active' : '' ?>">Conversions</a></li>
                <li><a href="marketing_report.php" class="<?= $current_page == 'marketing_report.php' ? 'active' : '' ?>">Reports</a></li>
            <?php else: ?>
                <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="list_invoices.php" class="<?= in_array($current_page, ['list_invoices.php', 'create_invoice.php', 'edit_invoice.php']) ? 'active' : '' ?>">Invoices</a></li>
                <li><a href="list_expenses.php" class="<?= in_array($current_page, ['list_expenses.php', 'create_expense.php', 'edit_expense.php']) ? 'active' : '' ?>">Expenses</a></li>
                <li><a href="list_payments.php" class="<?= $current_page == 'list_payments.php' ? 'active' : '' ?>">Payments</a></li>
                <li><a href="list_submissions.php" class="<?= in_array($current_page, ['list_submissions.php', 'review_submission.php']) ? 'active' : '' ?>">Submissions</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <main class="main-content">
