<?php
// edit_payment.php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

$errors = [];
$payment = null;

if (!isset($_GET['id'])) {
    header("Location: list_payments.php?error=No payment ID provided.");
    exit;
}

$id = (int)$_GET['id'];

// Fetch the payment to edit
$stmt = $conn->prepare("SELECT id, invoice_id, amount, payment_date FROM payments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $payment = $result->fetch_assoc();
} else {
    header("Location: list_payments.php?error=Payment not found.");
    exit;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = (int)$_POST['invoice_id'];
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_date = trim($_POST['payment_date']);

    if (empty($invoice_id)) { $errors[] = "Invoice is required."; }
    if ($amount === false || $amount <= 0) { $errors[] = "A valid positive amount is required."; }
    if (empty($payment_date)) { $errors[] = "Payment date is required."; }

    if (empty($errors)) {
        $update_stmt = $conn->prepare("UPDATE payments SET invoice_id = ?, amount = ?, payment_date = ? WHERE id = ?");
        $update_stmt->bind_param("idsi", $invoice_id, $amount, $payment_date, $id);
        if ($update_stmt->execute()) {
            header("Location: list_payments.php?message=Payment updated successfully.");
            exit;
        } else {
            $errors[] = "Error updating payment: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
}

// Fetch invoices for the dropdown
$invoicesResult = $conn->query("SELECT invoices.id, users.username, invoices.amount, invoices.status 
                               FROM invoices 
                               JOIN users ON invoices.student_id = users.id
                               ORDER BY invoices.date DESC");

?>
<?php include '../include/finance_header.php'; ?>
    <h2>Edit Payment #<?= htmlspecialchars($payment['id']) ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-container">
        <div class="form-group">
            <label for="invoice_id">Invoice:</label>
            <select id="invoice_id" name="invoice_id" required>
                <option value="">-- Select Invoice --</option>
                <?php mysqli_data_seek($invoicesResult, 0); ?>
                <?php while ($invoice = $invoicesResult->fetch_assoc()): ?>
                    <option value="<?= $invoice['id'] ?>" <?= ($invoice['id'] == $payment['invoice_id']) ? 'selected' : '' ?>>
                        Inv #<?= htmlspecialchars($invoice['id']) ?> - <?= htmlspecialchars($invoice['username']) ?> (<?= htmlspecialchars(number_format($invoice['amount'], 2)) ?>) - <?= htmlspecialchars($invoice['status']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="amount">Amount:</label>
            <input type="number" id="amount" name="amount" step="0.01" value="<?= htmlspecialchars($payment['amount']) ?>" required>
        </div>

        <div class="form-group">
            <label for="payment_date">Payment Date:</label>
            <input type="date" id="payment_date" name="payment_date" value="<?= htmlspecialchars(date('Y-m-d', strtotime($payment['payment_date']))) ?>" required>
        </div>

        <div class="form-group">
            <button type="submit">Update Payment</button>
        </div>
    </form>

    <a href="list_payments.php" class="nav-link">Back to Payments List</a>

<?php include '../include/finance_footer.php'; ?>
