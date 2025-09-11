<?php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

// Inputs & Filters
$description = isset($_GET['description']) ? trim($_GET['description']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$min_amount = isset($_GET['min_amount']) && $_GET['min_amount'] !== '' ? (float)$_GET['min_amount'] : null;
$max_amount = isset($_GET['max_amount']) && $_GET['max_amount'] !== '' ? (float)$_GET['max_amount'] : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build WHERE conditions
$conditions = [];
$params = [];
$types = '';

if ($description !== '') { $conditions[] = 'description LIKE ?'; $types .= 's'; $params[] = '%' . $description . '%'; }
if ($start_date !== '') { $conditions[] = 'date >= ?'; $types .= 's'; $params[] = $start_date; }
if ($end_date !== '') { $conditions[] = 'date <= ?'; $types .= 's'; $params[] = $end_date; }
if ($min_amount !== null) { $conditions[] = 'amount >= ?'; $types .= 'd'; $params[] = $min_amount; }
if ($max_amount !== null) { $conditions[] = 'amount <= ?'; $types .= 'd'; $params[] = $max_amount; }

$whereSql = '';
if (!empty($conditions)) {
    $whereSql = " WHERE " . implode(' AND ', $conditions);
}

// Count total for pagination
$countSql = "SELECT COUNT(*) as cnt FROM expenses" . $whereSql;
$stmtCount = $conn->prepare($countSql);
if ($types !== '') {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$countResult = $stmtCount->get_result()->fetch_assoc();
$totalRows = $countResult['cnt'] ?? 0;
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}
$stmtCount->close();

// Main query with LIMIT/OFFSET
$sql = "SELECT id, description, amount, date FROM expenses" . $whereSql . " ORDER BY date DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$limit_types = $types . 'ii';
$limit_params = array_merge($params, [$offset, $perPage]);
$stmt->bind_param($limit_types, ...$limit_params);
$stmt->execute();
$result = $stmt->get_result();

// Helper for pagination and filter links
function build_query($overrides = []) {
    return http_build_query(array_merge($_GET, $overrides));
}
?>
<?php include '../include/finance_header.php'; ?>
    <h2>Expenses List</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="message success"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a href="create_expense.php" class="button">Add New Expense</a>
    </div>

    <form method="GET" class="filter-form">
        <div class="form-group">
            <label for="description">Description:</label>
            <input type="text" id="description" name="description" value="<?= htmlspecialchars($description) ?>">
        </div>
        <div class="form-group">
            <label for="start_date">From:</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="form-group">
            <label for="end_date">To:</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="form-group">
            <label for="min_amount">Min Amount:</label>
            <input type="number" id="min_amount" name="min_amount" step="0.01" value="<?= $min_amount !== null ? htmlspecialchars((string)$min_amount) : '' ?>">
        </div>
        <div class="form-group">
            <label for="max_amount">Max Amount:</label>
            <input type="number" id="max_amount" name="max_amount" step="0.01" value="<?= $max_amount !== null ? htmlspecialchars((string)$max_amount) : '' ?>">
        </div>
        <button type="submit" class="button">Filter</button>
        <a href="list_expenses.php" class="button reset">Reset</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars(number_format($row['amount'], 2)) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td class="actions">
                            <a href="edit_expense.php?id=<?= $row['id'] ?>">Edit</a> |
                            <a href="delete_expense.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this expense?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No expenses found.</td>
                </tr>
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

    <p><a href="finance_dashboard.php">Back to Finance Dashboard</a></p>

<?php include '../include/finance_footer.php'; ?>
