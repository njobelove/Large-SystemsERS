<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    die('Campaign ID is required.');
}
$id = (int)$_GET['id'];
if ($id <= 0) { die('Invalid campaign ID.'); }

// Fetch existing campaign
$stmt = $conn->prepare('SELECT id, name, description, budget, spend, start_date, end_date FROM campaigns WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows !== 1) {
    $stmt->close();
    die('Campaign not found.');
}
$campaign = $res->fetch_assoc();
$stmt->close();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = isset($_POST['budget']) ? (float)$_POST['budget'] : 0.0;
    $spend = isset($_POST['spend']) ? (float)$_POST['spend'] : 0.0;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    if ($name === '') $errors[] = 'Name is required';
    if ($budget < 0) $errors[] = 'Budget cannot be negative';
    if ($spend < 0) $errors[] = 'Spend cannot be negative';

    if (!$errors) {
        $upd = $conn->prepare('UPDATE campaigns SET name = ?, description = ?, budget = ?, spend = ?, start_date = ?, end_date = ? WHERE id = ?');
        $upd->bind_param('ssddssi', $name, $description, $budget, $spend, $start_date, $end_date, $id);
        if ($upd->execute()) {
            $upd->close();
            header('Location: list_campaigns.php?message=Campaign+updated');
            exit;
        } else {
            $errors[] = 'DB error: ' . $conn->error;
        }
        $upd->close();
    }
}

include '../include/finance_header.php';
?>

<h2>Edit Campaign #<?= (int)$campaign['id'] ?></h2>
<?php if ($errors): ?>
<div class="message error">
    <ul>
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<form method="POST" class="form-container">
    <div class="form-group">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($campaign['name']) ?>" required>
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= htmlspecialchars($campaign['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="budget">Budget (€)</label>
        <input type="number" id="budget" step="0.01" name="budget" value="<?= htmlspecialchars((string)$campaign['budget']) ?>" min="0">
    </div>

    <div class="form-group">
        <label for="spend">Spend (€)</label>
        <input type="number" id="spend" step="0.01" name="spend" value="<?= htmlspecialchars((string)$campaign['spend']) ?>" min="0">
    </div>

    <div class="form-group">
        <label for="start_date">Start Date</label>
        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars((string)$campaign['start_date']) ?>">
    </div>

    <div class="form-group">
        <label for="end_date">End Date</label>
        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars((string)$campaign['end_date']) ?>">
    </div>

    <button type="submit" class="action-button">Update Campaign</button>
</form>
<div class="back-link">
    <a href="list_campaigns.php" class="nav-link">Back to Campaigns</a>
</div>

<?php include '../include/finance_footer.php'; ?>
