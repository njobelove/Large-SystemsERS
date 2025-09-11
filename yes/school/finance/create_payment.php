<?php
// create_payment.php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;

    if ($invoice_id <= 0 || $amount <= 0) {
        echo "Invalid invoice or amount.";
        exit;
    }

    // Insert payment record
    $stmt = $conn->prepare("INSERT INTO payments (invoice_id, amount) VALUES (?, ?)");
    $stmt->bind_param("id", $invoice_id, $amount);

    if ($stmt->execute()) {
        // Update invoice status if fully paid
        $paidStmt = $conn->prepare("SELECT SUM(amount) as total_paid FROM payments WHERE invoice_id = ?");
        $paidStmt->bind_param("i", $invoice_id);
        $paidStmt->execute();
        $paidRes = $paidStmt->get_result();
        $paidRow = $paidRes ? $paidRes->fetch_assoc() : null;
        $totalPaid = $paidRow && isset($paidRow['total_paid']) ? (float)$paidRow['total_paid'] : 0.0;
        $paidStmt->close();

        $invStmt = $conn->prepare("SELECT amount FROM invoices WHERE id = ?");
        $invStmt->bind_param("i", $invoice_id);
        $invStmt->execute();
        $invRes = $invStmt->get_result();
        $invRow = $invRes ? $invRes->fetch_assoc() : null;
        $invoiceAmount = $invRow && isset($invRow['amount']) ? (float)$invRow['amount'] : 0.0;
        $invStmt->close();

        $newStatus = ($totalPaid >= $invoiceAmount) ? 'paid' : 'due';

        $updateStmt = $conn->prepare("UPDATE invoices SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $invoice_id);
        $updateStmt->execute();
        $updateStmt->close();

        echo "Payment recorded successfully.";
    } else {
        echo "Error recording payment: " . $conn->error;
    }
    $stmt->close();
}

// Fetch invoices for dropdown
$invoicesResult = $conn->query("SELECT invoices.id, users.username, invoices.amount, invoices.status
                               FROM invoices
                               JOIN users ON invoices.student_id = users.id
                               ORDER BY invoices.date DESC");
?>

<?php include '../include/finance_header.php'; ?>

<h2>Record Payment</h2>

<form method="POST" class="form-container">
    <div class="form-group">
        <label for="invoice_id">Invoice:</label>
        <select id="invoice_id" name="invoice_id" required>
            <?php while ($invoice = $invoicesResult->fetch_assoc()): ?>
                <option value="<?= $invoice['id'] ?>">
                    Invoice #<?= $invoice['id'] ?> - <?= htmlspecialchars($invoice['username']) ?> - Amount: €<?= number_format($invoice['amount'], 2) ?> - Status: <?= $invoice['status'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="amount">Amount:</label>
        <input type="number" step="0.01" id="amount" name="amount" required>
    </div>

    <div class="form-group">
        <button type="submit">Record Payment</button>
    </div>
</form>

<a href="list_payments.php" class="nav-link">Back to Payments List</a>

<?php include '../include/finance_footer.php'; ?>
