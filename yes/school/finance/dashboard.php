<?php
session_start();
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

// Fetch total revenue from paid invoices
$revenueResult = $conn->query("SELECT SUM(amount) as total_revenue FROM invoices WHERE status = 'paid'");
$totalRevenue = $revenueResult->fetch_assoc()['total_revenue'] ?? 0;

// Fetch total expenses
$expensesResult = $conn->query("SELECT SUM(amount) as total_expenses FROM expenses");
$totalExpenses = $expensesResult->fetch_assoc()['total_expenses'] ?? 0;

// Calculate Net Profit/Loss
$netProfit = $totalRevenue - $totalExpenses;

// Fetch outstanding revenue from due/overdue invoices
$outstandingResult = $conn->query("SELECT SUM(amount) as total_outstanding FROM invoices WHERE status IN ('due', 'overdue')");
$totalOutstanding = $outstandingResult->fetch_assoc()['total_outstanding'] ?? 0;

// Count students with outstanding balances
$studentsWithBalancesResult = $conn->query("SELECT COUNT(DISTINCT student_id) as student_count FROM invoices WHERE status IN ('due', 'overdue')");
$studentsWithBalances = $studentsWithBalancesResult->fetch_assoc()['student_count'] ?? 0;

// Count pending payment submissions
$submissionsResult = $conn->query("SELECT COUNT(*) as pending_submissions FROM payment_submissions WHERE status = 'pending'");
$pendingSubmissions = $submissionsResult->fetch_assoc()['pending_submissions'] ?? 0;

// Recent Activity: Last 5 payments
$recentPayments = [];
$recentResult = $conn->query("SELECT p.id, p.amount, p.payment_date, u.username AS student_name FROM payments p JOIN invoices i ON p.invoice_id = i.id JOIN users u ON i.student_id = u.id ORDER BY p.payment_date DESC LIMIT 5");
if ($recentResult) {
    while ($row = $recentResult->fetch_assoc()) {
        $recentPayments[] = $row;
    }
}

// Recent Expenses: Last 5 expenses
$recentExpenses = [];
$recentExpensesResult = $conn->query("SELECT id, description, amount, date FROM expenses ORDER BY date DESC LIMIT 5");
if ($recentExpensesResult) {
    while ($row = $recentExpensesResult->fetch_assoc()) {
        $recentExpenses[] = $row;
    }
}

// Statistics Summary: Current month totals
$currentMonth = date('Y-m-01');
$nextMonth = date('Y-m-01', strtotime('+1 month'));
$currentRevenue = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM invoices WHERE status = 'paid' AND date >= '$currentMonth' AND date < '$nextMonth'")->fetch_assoc()['total'] ?? 0;
$currentExpenses = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE date >= '$currentMonth' AND date < '$nextMonth'")->fetch_assoc()['total'] ?? 0;
$currentNet = $currentRevenue - $currentExpenses;

// Last 30 Reports: Last 30 days' net
$last30Days = [];
for ($i = 29; $i >= 0; $i--) {
    $dayStart = date('Y-m-d', strtotime("-$i days"));
    $dayEnd = date('Y-m-d', strtotime("-" . ($i - 1) . " days"));
    $rev = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM invoices WHERE status = 'paid' AND date >= '$dayStart' AND date < '$dayEnd'")->fetch_assoc()['total'] ?? 0;
    $exp = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE date >= '$dayStart' AND date < '$dayEnd'")->fetch_assoc()['total'] ?? 0;
    $net = $rev - $exp;
    $last30Days[] = [
        'day' => date('M d', strtotime($dayStart)),
        'revenue' => $rev,
        'expenses' => $exp,
        'net' => $net
    ];
}

$conn->close();

include '../include/finance_header.php';
?>

    <h1>Finance Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>! Here's a summary of the institution's financial status.</p>

    <div class="cards">
        <div class="card">
            <h3>Total Revenue</h3>
            <p class="value">FCFA <?= number_format($totalRevenue, 2) ?></p>

            <small>From all paid invoices.</small>
        </div>
        <div class="card">
            <h3>Total Expenses</h3>
            <p class="value">FCFA <?= number_format($totalExpenses, 2) ?></p>
            <small>All recorded expenses.</small>
        </div>
        <div class="card profit">
            <h3>Net Profit / Loss</h3>
            <p class="value" style="color: <?= $netProfit >= 0 ? '#198754' : '#dc3545' ?>;">
                FCFA <?= number_format($netProfit, 2) ?>
            </p>
            <small>Revenue minus expenses.</small>
        </div>
        <div class="card">
            <h3>Outstanding Revenue</h3>
            <p class="value">FCFA <?= number_format($totalOutstanding, 2) ?></p>
            <small><?= $studentsWithBalances ?> student(s) with balances.</small>
        </div>
        <div class="card">
            <h3>Pending Submissions</h3>
            <p class="value"><?= $pendingSubmissions ?></p>
            <?php if ($pendingSubmissions > 0): ?>
                <a href="list_submissions.php" class="action-link">Review Now</a>
            <?php else: ?>
                <small>No pending items.</small>
            <?php endif; ?>
        </div>
    </div>

    <h2>Recent Activity</h2>
    <div class="panel">
        <h3>Last 5 Payments</h3>
        <?php if ($recentPayments): ?>
            <ul>
                <?php foreach ($recentPayments as $payment): ?>
                    <li>FCFA <?= number_format($payment['amount'], 2) ?> by <?= htmlspecialchars($payment['student_name']) ?> on <?= htmlspecialchars($payment['payment_date']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No recent payments.</p>
        <?php endif; ?>

        <h3>Last 5 Expenses</h3>
        <?php if ($recentExpenses): ?>
            <ul>
                <?php foreach ($recentExpenses as $expense): ?>
                    <li>FCFA <?= number_format($expense['amount'], 2) ?> for <?= htmlspecialchars($expense['description']) ?> on <?= htmlspecialchars($expense['date']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No recent expenses.</p>
        <?php endif; ?>
    </div>

    <h2>Statistics Summary</h2>
    <div class="cards">
        <div class="card">
            <h3>Current Month Revenue</h3>
            <p class="value">FCFA <?= number_format($currentRevenue, 2) ?></p>
        </div>
        <div class="card">
            <h3>Current Month Expenses</h3>
            <p class="value">FCFA <?= number_format($currentExpenses, 2) ?></p>
        </div>
        <div class="card">
            <h3>Current Month Net</h3>
            <p class="value" style="color: <?= $currentNet >= 0 ? '#198754' : '#dc3545' ?>;">FCFA <?= number_format($currentNet, 2) ?></p>
        </div>
    </div>

    <h2>Last 30 Reports</h2>
    <div class="panel">
        <canvas id="financeChart" width="800" height="400"></canvas>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('financeChart').getContext('2d');
    const labels = <?php echo json_encode(array_column($last30Days, 'day')); ?>;
    const revenueData = <?php echo json_encode(array_column($last30Days, 'revenue')); ?>;
    const expensesData = <?php echo json_encode(array_column($last30Days, 'expenses')); ?>;
    const netData = <?php echo json_encode(array_column($last30Days, 'net')); ?>;

    const data = {
        labels: labels,
        datasets: [
            {
                label: 'Revenue',
                data: revenueData,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: true,
                tension: 0.3
            },
            {
                label: 'Expenses',
                data: expensesData,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                fill: true,
                tension: 0.3
            },
            {
                label: 'Net',
                data: netData,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true,
                tension: 0.3
            }
        ]
    };

    const config = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            stacked: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Financial Overview - Last 7 Days'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (FCFA)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Day'
                    }
                }
            }
        }
    };

    const financeChart = new Chart(ctx, config);
</script>

<?php include '../include/finance_footer.php'; ?>
