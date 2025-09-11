<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = (float)($_POST['budget'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if ($name === '') $errors[] = 'Name is required';
    if ($budget < 0) $errors[] = 'Budget cannot be negative';
    if ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) $errors[] = 'Start date cannot be after end date';

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO campaigns (name, description, budget, spend, start_date, end_date) VALUES (?, ?, ?, 0, ?, ?)");
        $stmt->bind_param('ssdss', $name, $description, $budget, $start_date, $end_date);
        if ($stmt->execute()) {
            $success = 'Campaign created successfully!';
            // Reset form fields
            $name = $description = $start_date = $end_date = '';
            $budget = 0;
        } else {
            $errors[] = 'DB error: ' . $conn->error;
        }
        $stmt->close();
    }
}
?>
<?php
include '../include/finance_header.php';
?>

<h2>Create Campaign</h2>
<?php if ($errors): ?>
<div class="message error">
    <ul>
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="message success">
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<form method="POST" class="form-container" onsubmit="return validateForm()">
    <div class="form-group">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= htmlspecialchars($description ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="budget">Budget (€)</label>
        <input type="number" id="budget" step="0.01" name="budget" value="<?= htmlspecialchars($budget ?? 0) ?>" min="0">
    </div>

    <div class="form-group">
        <label for="start_date">Start Date</label>
        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="end_date">End Date</label>
        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date ?? '') ?>">
    </div>

    <button type="submit" class="action-button">Create Campaign</button>
</form>
<div class="back-link">
    <a href="list_campaigns.php" class="nav-link">Back to Campaigns</a>
</div>

<script>
    function validateForm() {
        const name = document.getElementById('name').value.trim();
        const budget = parseFloat(document.getElementById('budget').value);
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        if (!name) {
            alert('Name is required.');
            return false;
        }
        if (budget < 0) {
            alert('Budget cannot be negative.');
            return false;
        }
        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            alert('Start date cannot be after end date.');
            return false;
        }
        return true;
    }
</script>

<?php
include '../include/finance_footer.php';
?>
