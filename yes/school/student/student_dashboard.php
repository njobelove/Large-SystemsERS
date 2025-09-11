<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: /school/login.php'); // Corrected path
    exit;
}

// Ensure the user has the 'student' role
if ($_SESSION['role'] !== 'student') {
    // Optional: Redirect to their own dashboard or show an error
    header('Location: /school/login.php?error=access_denied');
    exit;
}

include '../include/db_connect.php';

$student_id = (int)$_SESSION['user_id'];

$summary = [
    'total_invoiced' => 0.0,
    'total_paid' => 0.0,
    'due_invoices_count' => 0,
    'outstanding_balance' => 0.0
];

// Get total fixed fees assigned to student
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_fees FROM student_fees WHERE student_id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) {
    $summary['total_invoiced'] = (float)$res['total_fees'];
}
$stmt->close();

// Get count of due/overdue invoices
$stmt = $conn->prepare("SELECT COALESCE(SUM(CASE WHEN status IN ('due', 'overdue') THEN 1 ELSE 0 END), 0) as due_count FROM invoices WHERE student_id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) {
    $summary['due_invoices_count'] = (int)$res['due_count'];
}
$stmt->close();

// Get total paid amount
$stmt = $conn->prepare("SELECT COALESCE(SUM(p.amount), 0) as total_paid
                        FROM payments p
                        JOIN invoices i ON p.invoice_id = i.id
                        WHERE i.student_id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) {
    $summary['total_paid'] = (float)$res['total_paid'];
}
$stmt->close();

// Calculate outstanding balance
$summary['outstanding_balance'] = $summary['total_invoiced'] - $summary['total_paid'];

?>
<?php include 'header.php'; ?>
<main>
    <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
    <p>This is your student dashboard.</p>

    <h2>Summary</h2>
    <div class="cards">
        <div class="card">
            <h3>Total Fee</h3>
            <div class="value"><?= number_format($summary['total_invoiced'], 2) ?> CFA</div>
        </div>
        <div class="card">
            <h3>Outstanding Balance</h3>
            <div class="value"><?= number_format($summary['outstanding_balance'], 2) ?> CFA</div>
        </div>
        <div class="card">
            <h3>Total Paid</h3>
            <div class="value"><?= number_format($summary['total_paid'], 2) ?> CFA</div>
        </div>
        <div class="card">
            <h3>Due Invoices</h3>
            <div class="value"><?= $summary['due_invoices_count'] ?></div>
        </div>
    </div>

    <div class="progress-container" style="margin-top: 20px; max-width: 400px;">
        <h3>Payment Progress</h3>
        <?php
            $progressPercent = $summary['total_invoiced'] > 0 ? round(($summary['total_paid'] / $summary['total_invoiced']) * 100) : 0;
            $progressColor = $progressPercent >= 50 ? '#2196F3' : '#f44336'; // blue if >= 50%, else red
        ?>
        <div class="progress-bar" aria-label="Payment progress bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $progressPercent ?>" style="background: #e0e0e0; border-radius: 20px; height: 25px;">
            <div class="progress" style="width: <?= $progressPercent ?>%; background: <?= $progressColor ?>; height: 25px; border-radius: 20px; color: white; text-align: center; line-height: 25px; font-weight: bold;">
                <?= $progressPercent ?>%
            </div>
        </div>
    </div>

    <?php if ($summary['due_invoices_count'] > 0): ?>
        <div class="alert alert-warning">
            <strong>You have <?= $summary['due_invoices_count'] ?> due or overdue invoices. Please make payments promptly.</strong>
        </div>
    <?php endif; ?>

    <div class="actions">
        <a href="invoices.php">View My Invoices</a>
        <a href="payments.php">View My Payments</a>
        <a href="submit_payment.php">Submit a Payment</a>
    </div>
</main>

<?php include 'footer.php'; ?>
