<?php
// edit_invoice.php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: list_invoices.php?error=No+invoice+ID+provided.");
    exit;
}

$id = (int)$_GET['id'];
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $date = $_POST['date'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? '';

    if (empty($student_id)) $errors[] = 'Student is required.';
    if ($amount <= 0) $errors[] = 'Amount must be a positive number.';
    if (empty($date)) $errors[] = 'Invoice date is required.';
    if (empty($due_date)) $errors[] = 'Due date is required.';
    if (!in_array($status, ['due', 'paid', 'overdue'])) $errors[] = 'Invalid status selected.';

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE invoices SET student_id = ?, amount = ?, status = ?, date = ?, due_date = ? WHERE id = ?");
        $stmt->bind_param("idssssi", $student_id, $amount, $status, $date, $due_date, $id);

        if ($stmt->execute()) {
            header("Location: list_invoices.php?message=Invoice+updated+successfully");
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch invoice data for the form
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

// Fetch all students for dropdown selection
$students_result = $conn->query("SELECT id, username FROM users WHERE role = 'student' ORDER BY username ASC");

include '../include/finance_header.php';
?>

<h2>Edit Invoice #<?= htmlspecialchars($invoice['id']) ?></h2>

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
        <label for="student_id">Student:</label>
        <select id="student_id" name="student_id" required>
            <?php mysqli_data_seek($students_result, 0); // Reset pointer ?>
            <?php while ($student = $students_result->fetch_assoc()): ?>
                <option value="<?= $student['id'] ?>" <?= $student['id'] == $invoice['student_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($student['username']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="amount">Amount:</label>
        <input type="number" id="amount" name="amount" step="0.01" value="<?= htmlspecialchars($invoice['amount']) ?>" required>
    </div>

    <div class="form-group">
        <label for="date">Invoice Date:</label>
        <input type="date" id="date" name="date" value="<?= htmlspecialchars($invoice['date']) ?>" required>
    </div>

    <div class="form-group">
        <label for="due_date">Due Date:</label>
        <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($invoice['due_date']) ?>" required>
    </div>

    <div class="form-group">
        <label for="status">Status:</label>
        <select id="status" name="status">
            <option value="due" <?= $invoice['status'] === 'due' ? 'selected' : '' ?>>Due</option>
            <option value="paid" <?= $invoice['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
            <option value="overdue" <?= $invoice['status'] === 'overdue' ? 'selected' : '' ?>>Overdue</option>
        </select>
    </div>

    <div class="form-group">
        <button type="submit">Update Invoice</button>
    </div>
</form>

<a href="list_invoices.php" class="nav-link">Back to Invoice List</a>
<?php include '../include/finance_footer.php'; ?>