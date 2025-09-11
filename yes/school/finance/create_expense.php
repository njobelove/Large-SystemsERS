<?php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

$errors = [];
$amount = '';
$expense_date = date('Y-m-d H:i:s');
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d H:i:s');
    $description = trim($_POST['description'] ?? '');

    if ($amount <= 0) $errors[] = 'Amount must be a positive number.';
    if (empty($expense_date)) $errors[] = 'Expense date is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO expenses (amount, date, description) VALUES (?, ?, ?)");
        $stmt->bind_param("dss", $amount, $expense_date, $description);

        if ($stmt->execute()) {
            header("Location: list_expenses.php?message=Expense+added+successfully");
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

include '../include/finance_header.php';
?>

<h2>Add New Expense</h2>

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
        <label for="amount">Amount:</label>
        <input type="number" id="amount" name="amount" step="0.01" value="<?= htmlspecialchars($amount) ?>" required>
    </div>

    <div class="form-group">
        <label for="expense_date">Expense Date:</label>
        <input type="date" id="expense_date" name="expense_date" value="<?= htmlspecialchars($expense_date) ?>" required>
    </div>

    <div class="form-group">
        <label for="description">Description:</label>
        <textarea id="description" name="description" required><?= htmlspecialchars($description) ?></textarea>
    </div>

    <div class="form-group">
        <button type="submit">Add Expense</button>
    </div>
</form>

<a href="list_expenses.php" class="nav-link">Back to Expense List</a>

<?php include '../include/finance_footer.php'; ?>