<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) { die('Conversion ID is required.'); }
$id = (int)$_GET['id'];
if ($id <= 0) { die('Invalid conversion ID.'); }

$stmt = $conn->prepare('SELECT id, campaign_id, lead_id, revenue, converted_at FROM conversions WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows !== 1) { $stmt->close(); die('Conversion not found.'); }
$conversion = $res->fetch_assoc();
$stmt->close();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = (int)($_POST['campaign_id'] ?? 0);
    $lead_id = (int)($_POST['lead_id'] ?? 0);
    $revenue = isset($_POST['revenue']) ? (float)$_POST['revenue'] : 0.0;
    $converted_at = $_POST['converted_at'] ?? date('Y-m-d');

    if ($campaign_id <= 0) $errors[] = 'Select a campaign';
    if ($revenue < 0) $errors[] = 'Revenue cannot be negative';

    if (!$errors) {
        $upd = $conn->prepare('UPDATE conversions SET campaign_id = ?, lead_id = ?, revenue = ?, converted_at = ? WHERE id = ?');
        $upd->bind_param('iidsi', $campaign_id, $lead_id, $revenue, $converted_at, $id);
        if ($upd->execute()) {
            $upd->close();
            header('Location: list_conversions.php?message=Conversion+updated');
            exit;
        } else {
            $errors[] = 'DB error: ' . $conn->error;
        }
        $upd->close();
    }
}
$campaigns = $conn->query('SELECT id, name FROM campaigns ORDER BY name');
$leads = $conn->query('SELECT id, name FROM leads ORDER BY created_at DESC');
?>
<h2>Edit Conversion #<?= (int)$conversion['id'] ?></h2>
<?php if ($errors): ?>
<div style="color:red;">
    <ul>
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<form method="POST">
    <label>Campaign</label><br>
    <select name="campaign_id" required>
        <?php if ($campaigns && $campaigns->num_rows): ?>
            <?php while ($c = $campaigns->fetch_assoc()): ?>
                <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id'] === (int)$conversion['campaign_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endwhile; ?>
        <?php endif; ?>
    </select><br><br>

    <label>Lead (optional)</label><br>
    <select name="lead_id">
        <option value="0">-- None --</option>
        <?php if ($leads && $leads->num_rows): ?>
            <?php while ($l = $leads->fetch_assoc()): ?>
                <option value="<?= (int)$l['id'] ?>" <?= ((int)$l['id'] === (int)$conversion['lead_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['name']) ?>
                </option>
            <?php endwhile; ?>
        <?php endif; ?>
    </select><br><br>

    <label>Revenue</label><br>
    <input type="number" step="0.01" name="revenue" value="<?= htmlspecialchars((string)$conversion['revenue']) ?>" required><br><br>

    <label>Converted At</label><br>
    <input type="date" name="converted_at" value="<?= htmlspecialchars((string)$conversion['converted_at']) ?>"><br><br>

    <button type="submit">Update</button>
</form>
<p><a href="list_conversions.php">Back to Conversions</a></p>
