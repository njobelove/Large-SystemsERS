<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = (int)($_POST['campaign_id'] ?? 0);
    $lead_id = (int)($_POST['lead_id'] ?? 0);
    $revenue = (float)($_POST['revenue'] ?? 0);
    $converted_at = $_POST['converted_at'] ?? date('Y-m-d');

    if ($campaign_id <= 0) $errors[] = 'Select a campaign';
    if ($revenue < 0) $errors[] = 'Revenue cannot be negative';

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO conversions (campaign_id, lead_id, revenue, converted_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iids', $campaign_id, $lead_id, $revenue, $converted_at);
        if ($stmt->execute()) {
            header('Location: list_conversions.php?message=Conversion+recorded');
            exit;
        } else {
            $errors[] = 'DB error: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch campaigns and leads for the form dropdowns
$campaigns = $conn->query('SELECT id, name FROM campaigns ORDER BY name');
$leads = $conn->query('SELECT id, name FROM leads ORDER BY created_at DESC');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Record Conversion</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h2>Record New Conversion</h2>

    <?php if ($errors): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="conversion-form">
        <p>
            <label for="campaign_id">Campaign:</label><br>
            <select id="campaign_id" name="campaign_id" required>
                <option value="">-- Select Campaign --</option>
                <?php while ($c = $campaigns->fetch_assoc()): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </p>
        <p>
            <label for="lead_id">Lead (Optional):</label><br>
            <select id="lead_id" name="lead_id">
                <option value="0">-- No Associated Lead --</option>
                <?php while ($l = $leads->fetch_assoc()): ?>
                    <option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </p>
        <p>
            <label for="revenue">Revenue:</label><br>
            <input type="number" id="revenue" name="revenue" step="0.01" value="0.00" required>
        </p>
        <p>
            <label for="converted_at">Conversion Date:</label><br>
            <input type="date" id="converted_at" name="converted_at" value="<?= date('Y-m-d') ?>" required>
        </p>
        <p>
            <button type="submit">Record Conversion</button>
        </p>
    </form>

    <p><a href="list_conversions.php">Back to Conversions</a></p>
</body>
</html>