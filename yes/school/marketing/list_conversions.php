<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

$title = 'Conversions';
$sql = "SELECT 
            conv.id, 
            conv.revenue, 
            conv.converted_at, 
            c.name AS campaign_name, 
            l.name AS lead_name 
        FROM conversions conv
        LEFT JOIN campaigns c ON conv.campaign_id = c.id
        LEFT JOIN leads l ON conv.lead_id = l.id
        ORDER BY conv.converted_at DESC";
$res = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h2><?= $title ?></h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="message"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="errors"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a href="record_conversion.php">Record New Conversion</a>
    </div>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
        <tr>
            <th>ID</th>
            <th>Campaign</th>
            <th>Lead</th>
            <th>Revenue</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($res && $res->num_rows): ?>
            <?php while ($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= htmlspecialchars($row['campaign_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['lead_name'] ?? '-') ?></td>
                    <td><?= number_format((float)$row['revenue'], 2) ?></td>
                    <td><?= htmlspecialchars($row['converted_at']) ?></td>
                    <td>
                        <a href="edit_conversion.php?id=<?= (int)$row['id'] ?>">Edit</a> |
                        <a href="delete_conversion.php?id=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this conversion?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No conversions found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <p><a href="marketing_dashboard.php">Back to Dashboard</a></p>
</body>
</html>