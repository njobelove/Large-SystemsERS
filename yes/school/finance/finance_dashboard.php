<?php
// finance_dashboard.php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

// Summary Totals
$sumInv = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM invoices");
$sumPay = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM payments");
$sumExp = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM expenses");

$totalInvoices = $sumInv && ($r=$sumInv->fetch_assoc()) ? (float)$r['s'] : 0.0;
$totalPayments = $sumPay && ($r=$sumPay->fetch_assoc()) ? (float)$r['s'] : 0.0;
$totalExpenses = $sumExp && ($r=$sumExp->fetch_assoc()) ? (float)$r['s'] : 0.0;
$outstanding = $totalInvoices - $totalPayments;
$totalRevenue = $totalPayments - $totalExpenses;

// Count pending payment submissions
$pendingSubmissionsCount = 0;
if ($conn->query("SHOW TABLES LIKE 'payment_submissions'")->num_rows > 0) {
    $res = $conn->query("SELECT COUNT(*) AS c FROM payment_submissions WHERE status = 'pending'");
    if ($res) {
        $pendingSubmissionsCount = (int)$res->fetch_assoc()['c'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Finance Dashboard</title>
    <link rel="stylesheet" href="../css/finance_style.css">
</head>
<body>
    <h2>Finance Dashboard</h2>

    <div class="cards">
        <div class="card">
            <h3>Total Invoices</h3>
            <div class="value">FCFA <?= number_format($totalInvoices, 2) ?></div>
        </div>
        <div class="card">
            <h3>Total Payments</h3>
            <div class="value">FCFA <?= number_format($totalPayments, 2) ?></div>
        </div>
        <div class="card">
            <h3>Total Expenses</h3>
            <div class="value">FCFA <?= number_format($totalExpenses, 2) ?></div>
        </div>
        <div class="card">
            <h3>Outstanding Amount</h3>
            <div class="value">FCFA <?= number_format($outstanding, 2) ?></div>
        </div>
        <div class="card">
            <h3>Total Revenue</h3>
            <div class="value">FCFA <?= number_format($totalRevenue, 2) ?></div>
        </div>
        <div class="card">
            <h3>Pending Payment Submissions</h3>
            <div class="value"><?= $pendingSubmissionsCount ?></div>
        </div>
    </div>

    <!-- Additional content like charts and tables can be added here -->

    <div class="logout-button" style="position: fixed; top: 10px; right: 10px;">
        <a href="/school/logout.php" style="color: red; font-weight: bold; text-decoration: none;">Logout</a>
    </div>
</body>
</html>
