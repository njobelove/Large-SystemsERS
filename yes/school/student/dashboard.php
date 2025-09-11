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

// --- Data Fetching ---
$summary = [
    'total_invoiced' => 0.0,
    'total_paid' => 0.0,
    'due_invoices_count' => 0,
    'outstanding_balance' => 0.0
];

// Get total invoiced amount and count of due/overdue invoices
$stmt = $conn->prepare("SELECT 
                            COALESCE(SUM(amount), 0) as total_invoiced,
                            COALESCE(SUM(CASE WHEN status IN ('due', 'overdue') THEN 1 ELSE 0 END), 0) as due_count
                        FROM invoices 
                        WHERE student_id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) {
    $summary['total_invoiced'] = (float)$res['total_invoiced'];
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

    <h2>Dashboard</h2>

    <div class="cards">
        <div class="card">
            <h3>Outstanding Balance</h3>
            <div class="val">€<?= number_format($summary['outstanding_balance'], 2) ?></div>
        </div>
        <div class="card">
            <h3>Total Paid</h3>
            <div class="val">€<?= number_format($summary['total_paid'], 2) ?></div>
        </div>
        <div class="card">
            <h3>Due Invoices</h3>
            <div class="val"><?= $summary['due_invoices_count'] ?></div>
        </div>
    </div>

    <div class="actions">
        <a href="invoices.php">View My Invoices</a>
        <a href="payments.php">View My Payments</a>
        <a href="submit_payment.php">Submit a Payment</a>
    </div>

<?php include 'footer.php'; ?>
