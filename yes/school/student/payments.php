<?php
// student/payments.php
include 'header.php';
include '../include/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /school/login.php');
    exit;
}
$student_id = (int)$_SESSION['user_id'];

$sql = "SELECT 
            p.id,
            p.amount,
            p.payment_date,
            i.id AS invoice_id
        FROM payments p
        JOIN invoices i ON p.invoice_id = i.id
        WHERE i.student_id = ?
        ORDER BY p.payment_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result();

$payments = [];
$total_paid = 0.0;
while ($row = $res->fetch_assoc()) {
    $payments[] = $row;
    $total_paid += (float)$row['amount'];
}
$stmt->close();
?>
<h2>My Payments</h2>

<div class="cards">
    <div class="card">
        <h3>Total Paid</h3>
        <div class="val"><?= number_format($total_paid, 2) ?> CFA</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Payment ID</th>
            <th>Invoice ID</th>
            <th>Amount</th>
            <th>Payment Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($payments) > 0): ?>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= htmlspecialchars($payment['id']) ?></td>
                    <td><a href="invoices.php">#<?= htmlspecialchars($payment['invoice_id']) ?></a></td>
                    <td><?= number_format((float)$payment['amount'], 2) ?> CFA</td>
                    <td><?= htmlspecialchars($payment['payment_date']) ?></td>
                    <td>
                        <?php if (!empty($payment['receipt_path'])): ?>
                            <a href="/yes/yes/school/finance/payment_receipt.php?id=<?= $payment['id'] ?>" download>Download Receipt</a>
                        <?php else: ?>
                            <span>No receipt available</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No payments found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include 'footer.php'; ?>
