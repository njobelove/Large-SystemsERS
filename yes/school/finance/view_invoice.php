<?php
// view_invoice.php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: list_invoices.php?error=No+invoice+ID+provided.");
    exit;
}

$id = (int)$_GET['id'];

// Fetch invoice data
$stmt = $conn->prepare("SELECT id, student_id, amount, status, date, due_date FROM invoices WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: list_invoices.php?error=Invoice not found.");
    exit;
}

$invoice = $result->fetch_assoc();
$stmt->close();

// Fetch student name
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $invoice['student_id']);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

// Fetch payments for this invoice
$payments_stmt = $conn->prepare("SELECT id, amount, payment_date FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC");
$payments_stmt->bind_param("i", $id);
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();
$payments_stmt->close();

include '../include/finance_header.php';
?>

<h2>View Invoice #<?= htmlspecialchars($invoice['id']) ?></h2>

<div class="invoice-details">
    <div class="detail-group">
        <label>Student:</label>
        <span><?= htmlspecialchars($student['username']) ?></span>
    </div>

    <div class="detail-group">
        <label>Amount:</label>
        <span>€<?= number_format((float)$invoice['amount'], 2) ?></span>
    </div>

    <div class="detail-group">
        <label>Invoice Date:</label>
        <span><?= htmlspecialchars(date('M d, Y', strtotime($invoice['date']))) ?></span>
    </div>

    <div class="detail-group">
        <label>Due Date:</label>
        <span><?= htmlspecialchars(date('M d, Y', strtotime($invoice['due_date']))) ?></span>
    </div>

    <div class="detail-group">
        <label>Status:</label>
        <span class="status-<?= htmlspecialchars($invoice['status']) ?>"><?= ucfirst(htmlspecialchars($invoice['status'])) ?></span>
    </div>
</div>

<h3>Payments</h3>
<table>
    <thead>
        <tr>
            <th>Payment ID</th>
            <th>Amount</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($payments_result && $payments_result->num_rows > 0): ?>
            <?php while ($payment = $payments_result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$payment['id'] ?></td>
                    <td>€<?= number_format((float)$payment['amount'], 2) ?></td>
        <td><?= htmlspecialchars(date('M d, Y', strtotime($payment['payment_date']))) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">No payments found for this invoice.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="actions">
    <a href="edit_invoice.php?id=<?= (int)$invoice['id'] ?>" class="nav-link">Edit Invoice</a>
    <a href="list_invoices.php" class="nav-link">Back to Invoice List</a>
</div>

<?php include '../include/finance_footer.php'; ?>
