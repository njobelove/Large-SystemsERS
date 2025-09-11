<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

$errors = [];
$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($lead_id <= 0) {
    die('Invalid lead ID provided.');
}

// Fetch lead data
$stmt = $conn->prepare("SELECT id, name, email, status, campaign_id FROM leads WHERE id = ?");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    die('Lead not found.');
}
$lead = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $campaign_id = (int)($_POST['campaign_id'] ?? 0);

    if (empty($name)) { $errors[] = 'Name is required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email is required.'; }
    if ($campaign_id === 0) { $campaign_id = null; }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE leads SET name = ?, email = ?, status = ?, campaign_id = ? WHERE id = ?");
        $stmt->bind_param("sssii", $name, $email, $status, $campaign_id, $lead_id);

        if ($stmt->execute()) {
            header('Location: list_leads.php?message=Lead+updated+successfully');
            exit;
        } else {
            $errors[] = 'Error updating lead: ' . $conn->error;
        }
        $stmt->close();
    }
    // If there are errors, repopulate form with submitted data
    $lead['name'] = $name;
    $lead['email'] = $email;
    $lead['status'] = $status;
    $lead['campaign_id'] = $campaign_id;
}

// Fetch campaigns for dropdown
$campaignsResult = $conn->query("SELECT id, name FROM campaigns ORDER BY name");
$leadStatuses = ['new', 'contacted', 'qualified', 'converted', 'unqualified'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Lead</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h2>Edit Lead #<?= (int)$lead['id'] ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($lead['name']) ?>" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($lead['email']) ?>" required>
        </div>
        <div>
            <label for="status">Status:</label>
            <select id="status" name="status">
                <?php foreach ($leadStatuses as $s): ?>
                    <option value="<?= $s ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="campaign_id">Campaign:</label>
            <select id="campaign_id" name="campaign_id">
                <option value="0">(None)</option>
                <?php while ($campaign = $campaignsResult->fetch_assoc()): ?>
                    <option value="<?= (int)$campaign['id'] ?>" <?= (int)$lead['campaign_id'] === (int)$campaign['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($campaign['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <button type="submit">Update Lead</button>
        </div>
    </form>

    <p><a href="list_leads.php">Back to Lead List</a></p>
</body>
</html>
