<?php
// edit_expense.php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

$errors = [];
$expense_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($expense_id <= 0) {
    header("Location: list_expenses.php?error=Invalid expense ID.");
    exit;
}

// Fetch the expense to edit
$stmt = $conn->prepare("SELECT id, description, amount, date FROM expenses WHERE id = ?");
$stmt->bind_param("i", $expense_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $expense = $result->fetch_assoc();
} else {
    header("Location: list_expenses.php?error=Expense not found.");
    exit;
}
$stmt->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $expense_date = trim($_POST['expense_date']);

    if (empty($description)) { $errors[] = "Description is required."; }
    if ($amount === false || $amount <= 0) { $errors[] = "A valid positive amount is required."; }
    if (empty($expense_date)) { $errors[] = "Expense date is required."; }

    if (empty($errors)) {
        $update_stmt = $conn->prepare("UPDATE expenses SET description = ?, amount = ?, date = ? WHERE id = ?");
        $update_stmt->bind_param("sdsi", $description, $amount, $expense_date, $expense_id);
        if ($update_stmt->execute()) {
            header("Location: list_expenses.php?message=Expense updated successfully.");
            exit;
        } else {
            $errors[] = "Error updating expense: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
}
$conn->close();
?>
<?php include '../include/finance_header.php'; ?>
    <h2>Edit Expense #<?= htmlspecialchars($expense['id']) ?></h2>

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
            <label for="description">Description:</label>
            <input type="text" id="description" name="description" value="<?= htmlspecialchars($expense['description']) ?>" required>
        </div>
        <div class="form-group">
            <label for="amount">Amount:</label>
            <input type="number" id="amount" name="amount" step="0.01" value="<?= htmlspecialchars($expense['amount']) ?>" required>
        </div>
        <div class="form-group">
            <label for="expense_date">Expense Date:</label>
            <input type="date" id="expense_date" name="expense_date" value="<?= htmlspecialchars(date('Y-m-d', strtotime($expense['date']))) ?>" required>
        </div>
        <button type="submit" class="button">Update Expense</button>
    </form>

<?php include '../include/finance_footer.php'; ?>
