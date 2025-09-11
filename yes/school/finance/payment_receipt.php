<?php
// payment_receipt.php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin', 'student']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    die('Payment ID is required.');
}

$payment_id = (int)$_GET['id'];
if ($payment_id <= 0) {
    die('Invalid payment ID.');
}

$sql = "SELECT 
            p.id AS payment_id,
            p.amount AS payment_amount,
            p.payment_date,
            i.id AS invoice_id,
            i.amount AS invoice_amount,
            i.status AS invoice_status,
            i.date AS invoice_date,
            u.id AS student_id,
            u.username AS student_username,
            ps.receipt_path
        FROM payments p
        JOIN invoices i ON p.invoice_id = i.id
        JOIN users u ON i.student_id = u.id
        LEFT JOIN payment_submissions ps ON ps.invoice_id = i.id
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows !== 1) {
    $stmt->close();
    die('Payment not found.');
}
$data = $res->fetch_assoc();
$stmt->close();

// If a student is viewing, restrict to their own receipt if session has user id
if (isset($_SESSION['role']) && $_SESSION['role'] === 'student' && isset($_SESSION['user_id'])) {
    if ((int)$_SESSION['user_id'] !== (int)$data['student_id']) {
        http_response_code(403);
        die('You are not authorized to view this receipt.');
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt #<?= htmlspecialchars($data['payment_id']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .receipt { max-width: 600px; margin: 0 auto; border: 1px solid #ccc; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .row { display: flex; justify-content: space-between; margin: 6px 0; }
        .actions { text-align: center; margin-top: 20px; }
        .muted { color: #555; }
        @media print { .actions { display: none; } }
        .receipt-img {
            max-width: 100%;
            max-height: 400px;
            display: block;
            margin: 20px auto;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h2>Payment Receipt</h2>
            <div class="muted">Thank you. Please keep this for your records.</div>
        </div>

        <div class="row"><strong>Receipt #:</strong><span><?= htmlspecialchars($data['payment_id']) ?></span></div>
        <div class="row"><strong>Payment Date:</strong><span><?= htmlspecialchars($data['payment_date']) ?></span></div>

        <hr>

        <div class="row"><strong>Student:</strong><span><?= htmlspecialchars($data['student_username']) ?> (ID: <?= (int)$data['student_id'] ?>)</span></div>
        <div class="row"><strong>Invoice #:</strong><span><?= htmlspecialchars($data['invoice_id']) ?></span></div>
        <div class="row"><strong>Invoice Date:</strong><span><?= htmlspecialchars($data['invoice_date']) ?></span></div>
        <div class="row"><strong>Invoice Amount:</strong><span><?= number_format((float)$data['invoice_amount'], 2) ?></span></div>
        <div class="row"><strong>Invoice Status:</strong><span><?= htmlspecialchars($data['invoice_status']) ?></span></div>

        <hr>

        <div class="row"><strong>Payment Amount:</strong><span><?= number_format((float)$data['payment_amount'], 2) ?></span></div>

        <?php if (!empty($data['receipt_path'])): ?>
            <img src="/yes/yes/school/<?= htmlspecialchars($data['receipt_path']) ?>" alt="Receipt Image" class="receipt-img" />
        <?php else: ?>
            <div class="row"><em>No receipt uploaded.</em></div>
        <?php endif; ?>

        <div class="actions">
            <button onclick="window.print()">Print</button>
            <a href="list_payments.php" style="margin-left: 10px;">Back to Payments</a>
        </div>
    </div>
</body>
</html>
