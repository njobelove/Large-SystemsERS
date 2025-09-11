<?php
include '../include/db_connect.php';
// student/invoices.php
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /school/login.php');
    exit;
}
$student_id = (int)$_SESSION['user_id'];

// Fetch invoices for this student with total paid per invoice
$sql = "SELECT 
            i.id,
            i.amount,
            i.status,
            i.date,
            COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.invoice_id = i.id), 0) AS paid
        FROM invoices i
        WHERE i.student_id = ?
        ORDER BY i.date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result();

$invoices = [];
$total_amount = 0.0;
$total_paid = 0.0;
while ($row = $res->fetch_assoc()) {
    $row['outstanding'] = (float)$row['amount'] - (float)$row['paid'];
    $invoices[] = $row;
    $total_amount += (float)$row['amount'];
    $total_paid += (float)$row['paid'];
}
$stmt->close();
$outstanding_balance = $total_amount - $total_paid;
?>
<main>
<h2>My Invoices</h2>

<div class="cards">
    <div class="card">
        <h3>Outstanding Balance</h3>
        <div class="value"><?= number_format($outstanding_balance, 2) ?> CFA</div>
    </div>
    <div class="card">
        <h3>Total Invoiced</h3>
        <div class="value"><?= number_format($total_amount, 2) ?> CFA</div>
    </div>
    <div class="card">
        <h3>Total Paid</h3>
        <div class="value"><?= number_format($total_paid, 2) ?> CFA</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Invoice ID</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($invoices) > 0): ?>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?= htmlspecialchars($invoice['id']) ?></td>
                    <td><?= htmlspecialchars($invoice['date']) ?></td>
                    <td><?= number_format((float)$invoice['amount'], 2) ?> CFA</td>
                    <td><?= number_format((float)$invoice['paid'], 2) ?> CFA</td>
                    <td><?= number_format((float)$invoice['amount'] - (float)$invoice['paid'], 2) ?> CFA</td>
                    <td><?= htmlspecialchars(ucfirst($invoice['status'])) ?></td>
                    <td>
                        <?php if (((float)$invoice['amount'] - (float)$invoice['paid'] > 0) && $invoice['status'] !== 'paid'): ?>
                            <a href="submit_payment.php?invoice_id=<?= $invoice['id'] ?>">Pay Now</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">No invoices found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</main>

<?php include 'footer.php'; ?>
