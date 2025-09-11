<?php
include '../include/auth_check.php';
checkRoleAccess(['student']);
include '../include/db_connect.php';

$student_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

// Fetch student's due/overdue invoices for the dropdown
$invoices_sql = "SELECT id, amount, date FROM invoices WHERE student_id = ? AND status IN ('due', 'overdue') ORDER BY date DESC";
$stmt_invoices = $conn->prepare($invoices_sql);
$stmt_invoices->bind_param('i', $student_id);
$stmt_invoices->execute();
$invoices_result = $stmt_invoices->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = isset($_POST['invoice_id']) ? (int)$_POST['invoice_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $payment_date = isset($_POST['payment_date']) ? trim($_POST['payment_date']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $receipt = isset($_FILES['receipt']) ? $_FILES['receipt'] : null;

    if ($invoice_id <= 0 || $amount <= 0 || empty($payment_date) || !$receipt || $receipt['error'] !== UPLOAD_ERR_OK) {
        $error = "Please fill all required fields and upload a receipt.";
    } else {
        // Handle file upload
        $upload_dir = '../uploads/receipts/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = uniqid() . '-' . basename($receipt['name']);
        $upload_file = $upload_dir . $filename;
        $db_path = 'uploads/receipts/' . $filename; // Path to store in DB

        if (move_uploaded_file($receipt['tmp_name'], $upload_file)) {
            // Insert into payment_submissions table
            $sql = "INSERT INTO payment_submissions (student_id, invoice_id, amount, payment_date, receipt_path, notes, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iidsss', $student_id, $invoice_id, $amount, $payment_date, $db_path, $notes);
            
            if ($stmt->execute()) {
                $message = "Payment submitted successfully! It is now pending review by the finance department.";
                // To prevent re-submission on refresh
                header("Location: submit_payment.php?success=1");
                exit;
            } else {
                $error = "Database error: Could not submit payment.";
                // Clean up uploaded file if DB insert fails
                unlink($upload_file);
            }
            $stmt->close();
        } else {
            $error = "Error uploading receipt file.";
        }
    }
}

if (isset($_GET['success'])) {
    $message = "Payment submitted successfully! It is now pending review by the finance department.";
}
?>
<?php include 'header.php'; ?>
    <div class="form-container">
        <h2>Submit a Payment for Review</h2>
        <p>Fill out this form to submit your payment details. The finance team will review your submission and approve it.</p>

        <?php if ($message): ?><div class="message"><?= $message ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="invoice_id">Select Invoice:</label>
                <select id="invoice_id" name="invoice_id" required>
                    <option value="">-- Select an Invoice --</option>
                    <?php while($invoice = $invoices_result->fetch_assoc()): ?>
                        <option value="<?= $invoice['id'] ?>">
                            Invoice #<?= $invoice['id'] ?> - Amount: <?= number_format($invoice['amount'], 2) ?> CFA (Due: <?= $invoice['date'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="amount">Amount Paid (€):</label>
                <input type="number" id="amount" name="amount" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="payment_date">Payment Date:</label>
                <input type="date" id="payment_date" name="payment_date" required>
            </div>
            <div class="form-group">
                <label for="receipt">Proof of Payment (Receipt):</label>
                <input type="file" id="receipt" name="receipt" accept="image/*,application/pdf" required>
            </div>
            <div class="form-group">
                <label for="notes">Additional Notes (Optional):</label>
                <textarea id="notes" name="notes" rows="4"></textarea>
            </div>
            <div>
                <button type="submit">Submit Payment</button>
            </div>
        </form>
    </div>
<?php include 'footer.php'; ?>
