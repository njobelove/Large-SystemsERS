<?php
// list_leads.php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

// Inputs & Filters
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build WHERE conditions
$conditions = [];
$params = [];
$types = '';

if ($status !== '') {
    $conditions[] = 'l.status = ?';
    $types .= 's';
    $params[] = $status;
}
if ($campaign_id > 0) {
    $conditions[] = 'l.campaign_id = ?';
    $types .= 'i';
    $params[] = $campaign_id;
}

$whereSql = '';
if (!empty($conditions)) {
    $whereSql = " WHERE " . implode(' AND ', $conditions);
}

// Count total for pagination
$countSql = "SELECT COUNT(*) as cnt FROM leads l" . $whereSql;
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
$sql = "SELECT l.id, l.name, l.email, l.status, l.created_at, c.name as campaign_name
        FROM leads l
        LEFT JOIN campaigns c ON l.campaign_id = c.id" . $whereSql . "
        ORDER BY l.created_at DESC
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
$limit_types = $types . 'ii';
$limit_params = array_merge($params, [$offset, $perPage]);
$stmt->bind_param($limit_types, ...$limit_params);
$stmt->execute();
$result = $stmt->get_result();

// For filter dropdowns
$campaignsResult = $conn->query("SELECT id, name FROM campaigns ORDER BY name");
$statusesResult = $conn->query("SELECT DISTINCT status FROM leads ORDER BY status");

// Helper for pagination links
function build_query($overrides = []) {
    $query = array_merge($_GET, $overrides);
    return http_build_query($query);
}

include '../include/finance_header.php';
?>

<h2>Marketing Leads</h2>

<?php if (isset($_GET['message'])): ?>
    <div class="message success"><?= htmlspecialchars($_GET['message']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="message error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="actions">
    <a href="create_lead.php" class="nav-link">Create New Lead</a>
</div>

<form method="GET" class="filter-form">
    <div class="filter-group">
        <label for="status">Status:</label>
        <select name="status" id="status">
            <option value="">All</option>
            <?php while ($s = $statusesResult->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($s['status']) ?>" <?= $status === $s['status'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['status']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="filter-group">
        <label for="campaign_id">Campaign:</label>
        <select name="campaign_id" id="campaign_id">
            <option value="">All</option>
            <?php while ($c = $campaignsResult->fetch_assoc()): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $campaign_id === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <button type="submit" class="action-button">Filter</button>
    <a href="list_leads.php" class="nav-link">Reset</a>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Campaign</th>
            <th>Date Added</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                    <td><?= htmlspecialchars($row['campaign_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <a href="edit_lead.php?id=<?= (int)$row['id'] ?>" class="nav-link">Edit</a> |
                        <a href="delete_lead.php?id=<?= (int)$row['id'] ?>" onclick="return confirm('Are you sure you want to delete this lead?')" class="nav-link">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">No leads found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<div class="pagination">
    <?php if ($totalPages > 1): ?>
        <?php if ($page > 1): ?>
            <a href="?<?= build_query(['page' => $page - 1]) ?>">&laquo; Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?<?= build_query(['page' => $i]) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?<?= build_query(['page' => $page + 1]) ?>">Next &raquo;</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="back-link">
    <a href="marketing_dashboard.php" class="nav-link">Back to Dashboard</a>
</div>

<?php include '../include/finance_footer.php'; ?>
