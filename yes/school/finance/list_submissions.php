<?php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/finance_header.php';
include '../include/db_connect.php';

// --- Filtering ---
$status = isset($_GET['status']) ? $_GET['status'] : 'pending'; // Default to 'pending'
$validStatuses = ['pending', 'approved', 'rejected'];
if (!in_array($status, $validStatuses)) {
    $status = 'pending'; // Default to pending if status is invalid
}

// Fetch payment submissions based on status
$sql = "SELECT 
            ps.id,
            ps.amount,
            ps.payment_date,
            ps.receipt_path,
            ps.invoice_id,
            ps.status,
            ps.reviewed_at,
            u.username AS student_username
        FROM payment_submissions ps
        JOIN users u ON ps.student_id = u.id
        WHERE ps.status = ?
        ORDER BY ps.payment_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $status);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Submissions</title>
    <style>
        /* Basic styling for status */
        .status-approved { color: green; }
        .status-rejected { color: red; }
        .status-pending { color: orange; }
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions a { margin-right: 10px; text-decoration: none; }
        .message { padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; margin-bottom: 15px; }
        .error { padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>Payment Submissions (<?= ucfirst(htmlspecialchars($status)) ?>)</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="message"><?= htmlspecialchars($_GET['message']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="filters" style="margin-bottom: 15px;">
        <strong>Status:</strong>
        <a href="?status=pending" style="<?= $status === 'pending' ? 'font-weight:bold;' : '' ?>">Pending</a> |
        <a href="?status=approved" style="<?= $status === 'approved' ? 'font-weight:bold;' : '' ?>">Approved</a> |
        <a href="?status=rejected" style="<?= $status === 'rejected' ? 'font-weight:bold;' : '' ?>">Rejected</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Submission ID</th>
                <th>Student</th>
                <th>Invoice #</th>
                <th>Amount Submitted</th>
                <th>Payment Date</th>
                <th>Receipt</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($sub = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($sub['id']) ?></td>
                        <td><?= htmlspecialchars($sub['student_username']) ?></td>
                        <td><?= htmlspecialchars($sub['invoice_id']) ?></td>
                        <td>€<?= number_format($sub['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($sub['payment_date']) ?></td>
                        <td><a href="/yes/yes/school/<?= htmlspecialchars(ltrim(str_replace('\\', '/', $sub['receipt_path']), './')) ?>" target="_blank">View Receipt</a></td>
                        <td>
                            <span class="status-<?= htmlspecialchars($sub['status']) ?>">
                                <?= htmlspecialchars(ucfirst($sub['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($sub['status'] === 'pending'): ?>
                                <a href="review_submission.php?id=<?= $sub['id'] ?>">Review</a>
                            <?php else: ?>
                                Reviewed on <?= htmlspecialchars(substr($sub['reviewed_at'], 0, 10)) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No <?= htmlspecialchars($status) ?> submissions found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php include '../include/finance_footer.php'; ?>
</body>
</html>
