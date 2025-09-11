<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

$sql = "SELECT id, name, budget, spend, start_date, end_date FROM campaigns ORDER BY id DESC";
$res = $conn->query($sql);

include '../include/finance_header.php';
?>

<h2>Campaigns</h2>
<div class="actions">
    <a href="create_campaign.php" class="nav-link">Create Campaign</a>
</div>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Budget</th>
        <th>Spend</th>
        <th>Start</th>
        <th>End</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($res && $res->num_rows): ?>
        <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>€<?= number_format((float)$row['budget'], 2) ?></td>
                <td>€<?= number_format((float)$row['spend'], 2) ?></td>
                <td><?= htmlspecialchars($row['start_date']) ?></td>
                <td><?= htmlspecialchars($row['end_date']) ?></td>
                <td>
                    <a href="edit_campaign.php?id=<?= (int)$row['id'] ?>" class="nav-link">Edit</a> |
                    <a href="delete_campaign.php?id=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this campaign? This will also remove related leads/conversions.')" class="nav-link">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="7">No campaigns</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?php include '../include/finance_footer.php'; ?>
