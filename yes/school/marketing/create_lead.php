<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

$errors = [];
$name = '';
$email = '';
$status = 'new';
$campaign_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = trim($_POST['status'] ?? 'new');
    $campaign_id = (int)($_POST['campaign_id'] ?? 0);

    if (empty($name)) { $errors[] = 'Name is required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email is required.'; }
    if ($campaign_id === 0) { $campaign_id = null; } // Allow no campaign

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO leads (name, email, status, campaign_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssi", $name, $email, $status, $campaign_id);

        if ($stmt->execute()) {
            header('Location: list_leads.php?message=Lead+created+successfully');
            exit;
        } else {
            $errors[] = 'Error creating lead: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch campaigns for dropdown
$campaignsResult = $conn->query("SELECT id, name FROM campaigns ORDER BY name");
$leadStatuses = ['new', 'contacted', 'qualified', 'converted', 'unqualified'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Lead</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h2>Create New Lead</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="lead-form">
        <div>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <div>
            <label for="status">Status:</label>
            <select id="status" name="status">
                <?php foreach ($leadStatuses as $s): ?>
                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="campaign_id">Campaign:</label>
            <select id="campaign_id" name="campaign_id">
                <option value="0">(None)</option>
                <?php while ($campaign = $campaignsResult->fetch_assoc()): ?>
                    <option value="<?= (int)$campaign['id'] ?>" <?= $campaign_id === (int)$campaign['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($campaign['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <button type="submit">Create Lead</button>
        </div>
    </form>

    <p><a href="list_leads.php">Back to Lead List</a></p>
</body>
</html>