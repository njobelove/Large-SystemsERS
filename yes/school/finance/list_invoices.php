<?php
// list_invoices.php (enhanced with filters + pagination)
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

// Inputs
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$student = isset($_GET['student']) ? trim($_GET['student']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$validStatuses = ['due','paid','overdue'];
if ($status !== '' && !in_array($status, $validStatuses, true)) {
    $status = '';
}

// Build WHERE conditions
$conditions = [];
$params = [];
$types = '';

if ($status !== '') { $conditions[] = 'invoices.status = ?'; $types .= 's'; $params[] = $status; }
if ($student !== '') { $conditions[] = 'users.username LIKE ?'; $types .= 's'; $params[] = '%'.$student.'%'; }
if ($start_date !== '') { $conditions[] = 'invoices.date >= ?'; $types .= 's'; $params[] = $start_date . ' 00:00:00'; }
if ($end_date !== '') { $conditions[] = 'invoices.date <= ?'; $types .= 's'; $params[] = $end_date . ' 23:59:59'; }

$whereSql = '';
if (!empty($conditions)) {
    $whereSql = ' WHERE ' . implode(' AND ', $conditions);
}

// Count total for pagination
$countSql = "SELECT COUNT(*) as cnt FROM invoices JOIN users ON invoices.student_id = users.id" . $whereSql;
if ($stmtCount = $conn->prepare($countSql)) {
    if ($types !== '') { $stmtCount->bind_param($types, ...$params); }
    $stmtCount->execute();
    $countRes = $stmtCount->get_result();
    $totalRows = ($countRes && ($row = $countRes->fetch_assoc())) ? (int)$row['cnt'] : 0;
    $stmtCount->close();
} else {
    // Fallback (should not happen)
    $totalRows = 0;
}
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

// Main query with LIMIT/OFFSET
$sql = "
    SELECT 
        i.id, i.student_id, i.amount, i.status, i.date,
        u.username AS student_name,
        COALESCE(p.paid_amount, 0) AS paid_amount
    FROM invoices i
    JOIN users u ON i.student_id = u.id
    LEFT JOIN (
        SELECT invoice_id, SUM(amount) as paid_amount 
        FROM payments 
        GROUP BY invoice_id
    ) p ON i.id = p.invoice_id
    " . $whereSql . "
    ORDER BY i.date DESC
    LIMIT $offset, $perPage
";
$stmt = $conn->prepare($sql);
if ($types !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

include '../include/finance_header.php';
?>

<h2>Invoice List</h2>
<div class="actions">
    <a href="create_invoice.php">Create Invoice</a>
</div>


<form method="GET" class="filter-form">
    <div class="filter-group">
        <label for="student">Student:</label>
        <input type="text" id="student" name="student" value="<?= htmlspecialchars($student) ?>">
    </div>
    <div class="filter-group">
        <label for="status">Status:</label>
        <select id="status" name="status">
            <option value="">All</option>
            <option value="due" <?= $status === 'due' ? 'selected' : '' ?>>Due</option>
            <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
            <option value="overdue" <?= $status === 'overdue' ? 'selected' : '' ?>>Overdue</option>
        </select>
    </div>
    <div class="filter-group">
        <label for="start_date">From:</label>
        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
    </div>
    <div class="filter-group">
        <label for="end_date">To:</label>
        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
    </div>
    <button type="submit" class="action-button">Filter</button>
</form>

<table>
    <thead>
        <tr>
            <th>Invoice #</th>
            <th>Student</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= number_format((float)$row['amount'], 2) ?></td>
                    <td><span class="status-<?= htmlspecialchars($row['status']) ?>"><?= ucfirst(htmlspecialchars($row['status'])) ?></span></td>
                    <td><?= htmlspecialchars(date('M d, Y', strtotime($row['date']))) ?></td>
                    <td>
                        <a href="view_invoice.php?id=<?= (int)$row['id'] ?>">View</a>
                        <a href="edit_invoice.php?id=<?= (int)$row['id'] ?>">Edit</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No invoices found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php
// Pagination controls
function build_query($overrides = []) {
    $params = array_merge($_GET, $overrides);
    return http_build_query($params);
}
?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="<?= build_query(['page' => $page - 1]) ?>">Previous</a>
    <?php endif; ?>

    <span>Page <?= $page ?> of <?= $totalPages ?></span>

    <?php if ($page < $totalPages): ?>
        <a href="<?= build_query(['page' => $page + 1]) ?>">Next</a>
    <?php endif; ?>
</div>

<?php include '../include/finance_footer.php'; ?>
