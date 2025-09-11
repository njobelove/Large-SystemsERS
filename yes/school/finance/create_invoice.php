<?php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

$errors = [];
$student_id = '';
$amount = '';
$date = date('Y-m-d');
$due_date = date('Y-m-d', strtotime('+30 days')); // Default due date
$status = 'due'; // Default status

// Fetch students to populate the dropdown
$studentsResult = $conn->query("SELECT id, username FROM users WHERE role = 'student' ORDER BY username ASC");
$students = [];
if ($studentsResult) {
    while ($row = $studentsResult->fetch_assoc()) {
        $students[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $due_date = $_POST['due_date'] ?? null;
    $status = 'due'; // Status is always 'due' on creation

    if (empty($student_id)) $errors[] = 'Student is required.';
    if ($amount <= 0) $errors[] = 'Amount must be a positive number.';
    if (empty($date)) $errors[] = 'Date is required.';
    if (empty($due_date)) $errors[] = 'Due date is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO invoices (student_id, amount, date, due_date, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("idsss", $student_id, $amount, $date, $due_date, $status);
        if ($stmt->execute()) {
            header("Location: list_invoices.php?message=Invoice+created+successfully");
            exit;
        } else {
            $errors[] = "Error creating invoice: " . $conn->error;
        }
        $stmt->close();
    }
}
include '../include/finance_header.php';
?>

<h2>Create New Invoice</h2>

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
            <option value="">Select a student</option>
            <?php foreach ($students as $student): ?>
                <option value="<?= $student['id'] ?>" <?= $student_id == $student['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($student['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="amount">Amount:</label>
        <input type="number" id="amount" name="amount" step="0.01" value="<?= htmlspecialchars($amount) ?>" required>
    </div>

    <div class="form-group">
        <label for="date">Invoice Date:</label>
        <input type="date" id="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
    </div>

    <div class="form-group">
        <label for="due_date">Due Date:</label>
        <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($due_date) ?>" required>
    </div>

    <div class="form-group">
        <button type="submit">Create Invoice</button>
    </div>
</form>

<a href="list_invoices.php" class="nav-link">Back to Invoice List</a>

<?php include '../include/finance_footer.php'; ?>