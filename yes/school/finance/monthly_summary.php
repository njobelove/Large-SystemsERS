<?php
// monthly_summary.php
include '../include/auth_check.php';
include '../include/db_connect.php';

$user_role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

header('Content-Type: application/json');

try {
    $now = new DateTime('first day of this month');
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $start = (clone $now)->modify("-$i months");
        $end = (clone $start)->modify('first day of next month');
        $months[] = [
            'label' => $start->format('Y-m'),
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ];
    }

    if ($user_role === 'student') {
        $invoiceStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM invoices WHERE student_id = ? AND date >= ? AND date < ?");
        $paymentStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM payments p JOIN invoices i ON p.invoice_id = i.id WHERE i.student_id = ? AND p.date >= ? AND p.date < ?");
    } else {
        $invoiceStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM invoices WHERE date >= ? AND date < ?");
        $paymentStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE date >= ? AND date < ?");
    }
    $expenseStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE date >= ? AND date < ?");

    $data = [];
    $totals = ['invoices' => 0.0, 'payments' => 0.0, 'expenses' => 0.0, 'net' => 0.0];

    foreach ($months as $m) {
        $start = $m['start'];
        $end = $m['end'];

        if ($user_role === 'student') {
            $invoiceStmt->bind_param('iss', $user_id, $start, $end);
            $invoiceStmt->execute();
            $res = $invoiceStmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $inv = $row && isset($row['total']) ? (float)$row['total'] : 0.0;

            $paymentStmt->bind_param('iss', $user_id, $start, $end);
            $paymentStmt->execute();
            $res = $paymentStmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $pay = $row && isset($row['total']) ? (float)$row['total'] : 0.0;
        } else {
            $invoiceStmt->bind_param('ss', $start, $end);
            $invoiceStmt->execute();
            $res = $invoiceStmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $inv = $row && isset($row['total']) ? (float)$row['total'] : 0.0;

            $paymentStmt->bind_param('ss', $start, $end);
            $paymentStmt->execute();
            $res = $paymentStmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $pay = $row && isset($row['total']) ? (float)$row['total'] : 0.0;
        }

        // Expenses
        $expenseStmt->bind_param('ss', $start, $end);
        $expenseStmt->execute();
        $res = $expenseStmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $exp = $row && isset($row['total']) ? (float)$row['total'] : 0.0;

        $data[] = [
            'month' => $m['label'],
            'invoices' => $inv,
            'payments' => $pay,
            'expenses' => $exp,
            'net' => $pay - $exp,
        ];

        $totals['invoices'] += $inv;
        $totals['payments'] += $pay;
        $totals['expenses'] += $exp;
    }

    $totals['net'] = $totals['payments'] - $totals['expenses'];

    echo json_encode([
        'months' => $data,
        'totals' => $totals,
        'generated_at' => date('c')
    ]);

    $invoiceStmt->close();
    $paymentStmt->close();
    $expenseStmt->close();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to build monthly summary', 'details' => $e->getMessage()]);
}
