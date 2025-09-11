<?php
// list_payments.php (enhanced with filters + pagination)
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

// Inputs
$student = isset($_GET['student']) ? trim($_GET['student']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$min_amount = isset($_GET['min_amount']) && $_GET['min_amount'] !== '' ? (float)$_GET['min_amount'] : null;
$max_amount = isset($_GET['max_amount']) && $_GET['max_amount'] !== '' ? (float)$_GET['max_amount'] : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$conditions = [];
$params = [];
$types = '';

if ($student !== '') { $conditions[] = 'users.username LIKE ?'; $types .= 's'; $params[] = '%'.$student.'%'; }
if ($start_date !== '') { $conditions[] = 'payments.payment_date >= ?'; $types .= 's'; $params[] = $start_date . ' 00:00:00'; }
if ($end_date !== '') { $conditions[] = 'payments.payment_date <= ?'; $types .= 's'; $params[] = $end_date . ' 23:59:59'; }
if ($min_amount !== null) { $conditions[] = 'payments.amount >= ?'; $types .= 'd'; $params[] = $min_amount; }
if ($max_amount !== null) { $conditions[] = 'payments.amount <= ?'; $types .= 'd'; $params[] = $max_amount; }

$whereSql = '';
if (!empty($conditions)) {
    $whereSql = ' WHERE ' . implode(' AND ', $conditions);
}

// Count for pagination
$countSql = "SELECT COUNT(payments.id) AS cnt 
            FROM payments 
            JOIN invoices ON payments.invoice_id = invoices.id 
            JOIN users ON invoices.student_id = users.id" . $whereSql;
$stmtCount = $conn->prepare($countSql);
if ($types !== '') { $stmtCount->bind_param($types, ...$params); }
$stmtCount->execute();
$countRes = $stmtCount->get_result();
$totalRows = ($countRes && ($row = $countRes->fetch_assoc())) ? (int)$row['cnt'] : 0;
$stmtCount->close();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

// Main query
$sql = "SELECT payments.id, payments.amount, payments.payment_date, 
               invoices.id AS invoice_id, users.username 
        FROM payments
        JOIN invoices ON payments.invoice_id = invoices.id
        JOIN users ON invoices.student_id = users.id" . $whereSql . "
        ORDER BY payments.payment_date DESC
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
$final_params = $params;
$final_types = $types . 'ii';
$final_params[] = $offset;
$final_params[] = $perPage;
if ($types !== '') { 
    $stmt->bind_param($final_types, ...$final_params); 
} else {
    $stmt->bind_param('ii', $offset, $perPage);
}
$stmt->execute();
$result = $stmt->get_result();

function build_query($overrides = []) {
    return http_build_query(array_merge($_GET, $overrides));
}
?>

<?php include '../include/finance_header.php'; ?>

<h2>Payments List</h2>

<?php if (isset($_GET['message'])): ?>
    <div class="message success"><?= htmlspecialchars($_GET['message']) ?></div>
<?php endif; ?>

<div class="actions">
    <a href="create_payment.php">Record New Payment</a>
</div>

<form method="GET" class="filter-form">
    <div class="filter-group">
        <label for="student">Student:</label>
        <input type="text" id="student" name="student" value="<?= htmlspecialchars($student) ?>" placeholder="username">
    </div>
    <div class="filter-group">
        <label for="start_date">From:</label>
        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
    </div>
    <div class="filter-group">
        <label for="end_date">To:</label>
        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
    </div>
    <div class="filter-group">
        <label for="min_amount">Min Amount:</label>
        <input type="number" step="0.01" id="min_amount" name="min_amount" value="<?= $min_amount !== null ? htmlspecialchars((string)$min_amount) : '' ?>">
    </div>
    <div class="filter-group">
        <label for="max_amount">Max Amount:</label>
        <input type="number" step="0.01" id="max_amount" name="max_amount" value="<?= $max_amount !== null ? htmlspecialchars((string)$max_amount) : '' ?>">
    </div>
    <div class="filter-group">
        <button type="submit" class="action-button">Filter</button>
    </div>
    <div class="filter-group">
        <a href="list_payments.php" class="action-link">Reset</a>
    </div>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Amount</th>
            <th>Payment Date</th>
            <th>Invoice ID</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td>FCFA <?= htmlspecialchars(number_format($row['amount'], 2)) ?></td>
                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($row['payment_date']))) ?></td>
                    <td><a href="edit_invoice.php?id=<?= $row['invoice_id'] ?>"><?= htmlspecialchars($row['invoice_id']) ?></a></td>
                    <td class="actions">
                        <a href="edit_payment.php?id=<?= $row['id'] ?>">Edit</a>
                        <a href="delete_payment.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this payment?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No payments found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?<?= build_query(['page' => $page - 1]) ?>">&laquo; Prev</a>
    <?php endif; ?>

    <span>Page <?= $page ?> of <?= $totalPages ?></span>

    <?php if ($page < $totalPages): ?>
        <a href="?<?= build_query(['page' => $page + 1]) ?>">Next &raquo;</a>
    <?php endif; ?>
</div>

<a href="finance_dashboard.php" class="nav-link">Back to Finance Dashboard</a>

<?php include '../include/finance_footer.php'; ?>
