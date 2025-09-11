<?php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($submission_id <= 0) {
    header("Location: list_submissions.php?error=Invalid submission ID.");
    exit;
}

// --- Handle Form Submission (Approve/Reject) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $review_notes = trim($_POST['review_notes'] ?? '');

    // Re-fetch to ensure it's still pending before processing
    $stmt = $conn->prepare("SELECT * FROM payment_submissions WHERE id = ?");
    $stmt->bind_param("i", $submission_id);
    $stmt->execute();
    $current_submission = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current_submission) {
        header("Location: list_submissions.php?error=Submission not found.");
        exit;
    }
    if ($current_submission['status'] !== 'pending') {
        header("Location: list_submissions.php?error=This submission has already been " . $current_submission['status'] . ".");
        exit;
    }

    $conn->begin_transaction();
    try {
        if ($action === 'approve') {
            // 1. Update submission status
            $stmt1 = $conn->prepare("UPDATE payment_submissions SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
            $stmt1->bind_param("isi", $_SESSION['user_id'], $review_notes, $submission_id);
            $stmt1->execute();
            $stmt1->close();

            // 2. Create a corresponding payment record
            $stmt2 = $conn->prepare("INSERT INTO payments (invoice_id, amount, payment_date, payment_method, notes, created_by) VALUES (?, ?, ?, 'bank_transfer', ?, ?)");
            $payment_note = "Payment auto-created from approved submission ID: " . $submission_id;
            $stmt2->bind_param("isdsi", $current_submission['invoice_id'], $current_submission['amount'], $current_submission['payment_date'], $payment_note, $_SESSION['user_id']);
            $stmt2->execute();
            $payment_id = $stmt2->insert_id;
            $stmt2->close();

            // 3. Update the invoice status to 'paid'
            $stmt3 = $conn->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
            $stmt3->bind_param("i", $current_submission['invoice_id']);
            $stmt3->execute();
            $stmt3->close();

            $message = "Submission approved. Payment record #{$payment_id} created.";

        } elseif ($action === 'reject') {
            if (empty($review_notes)) {
                throw new Exception("Rejection reason (in review notes) is required.");
            }
            // Update submission status to 'rejected'
            $stmt = $conn->prepare("UPDATE payment_submissions SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
            $stmt->bind_param("isi", $_SESSION['user_id'], $review_notes, $submission_id);
            $stmt->execute();
            $stmt->close();
            $message = "Submission rejected.";
        } else {
            throw new Exception("Invalid action.");
        }

        $conn->commit();
        header("Location: list_submissions.php?message=" . urlencode($message));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        // Redirect back with an error
        header("Location: review_submission.php?id=" . $submission_id . "&error=" . urlencode($e->getMessage()));
        exit;
    }
}

// --- Fetch Submission Details for Display ---
$sql = "SELECT 
            ps.*,
            u.username AS student_username,
            i.amount AS invoice_amount,
            i.date AS invoice_date,
            i.status AS invoice_status
        FROM payment_submissions ps
        JOIN users u ON ps.student_id = u.id
        JOIN invoices i ON ps.invoice_id = i.id
        WHERE ps.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $submission_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list_submissions.php?error=Submission not found.");
    exit;
}
$submission = $result->fetch_assoc();
$stmt->close();

// --- REMOVED REDIRECT BLOCK ---

$receipt_url = "/yes/yes/school/" . htmlspecialchars(ltrim(str_replace('\\', '/', $submission['receipt_path']), './'));
?>
<?php include '../include/finance_header.php'; ?>

<h2>Review Payment Submission #<?= $submission['id'] ?></h2>

<?php if (isset($_GET['error'])): ?>
    <div class="message error"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="review-grid">
    <div class="details-card">
        <h3>Submission Details</h3>
        <div class="row"><strong>Status:</strong>
            <span class="status-<?= htmlspecialchars($submission['status']) ?>">
                <?= htmlspecialchars(ucfirst($submission['status'])) ?>
            </span>
        </div>
        <div class="row"><strong>Student:</strong> <?= htmlspecialchars($submission['student_username']) ?></div>
        <div class="row"><strong>Submitted Amount:</strong> €<?= number_format($submission['amount'], 2) ?></div>
        <div class="row"><strong>Declared Payment Date:</strong> <?= htmlspecialchars($submission['payment_date']) ?></div>
        <div class="row"><strong>Submission Date:</strong> <?= htmlspecialchars($submission['submitted_at']) ?></div>
        <hr>
        <h3>Invoice Details</h3>
        <div class="row"><strong>Invoice #:</strong> <?= htmlspecialchars($submission['invoice_id']) ?></div>
        <div class="row"><strong>Invoice Amount:</strong> €<?= number_format($submission['invoice_amount'], 2) ?></div>
        <div class="row"><strong>Invoice Date:</strong> <?= htmlspecialchars($submission['invoice_date']) ?></div>
        <div class="row"><strong>Invoice Status:</strong>
            <span class="status-<?= htmlspecialchars($submission['invoice_status']) ?>">
                <?= htmlspecialchars(ucfirst($submission['invoice_status'])) ?>
            </span>
        </div>
    </div>

    <div class="actions-card">
        <h3>Actions</h3>
        <form method="POST" id="review-form" class="form-container">
            <div class="form-group">
                <label for="review_notes">Review Notes (Required for rejection):</label>
                <textarea id="review_notes" name="review_notes" rows="4"></textarea>
            </div>

            <div class="button-group">
                <button type="submit" name="action" value="approve" class="action-button approve-btn" onclick="return confirm('Are you sure you want to APPROVE this submission? This will create a permanent payment record.')">Approve</button>
                <button type="submit" name="action" value="reject" class="action-button reject-btn" onclick="return confirmReject()">Reject</button>
            </div>
        </form>
    </div>
</div>

<div class="receipt-viewer">
    <h3>Receipt Viewer</h3>
    <iframe src="<?= $receipt_url ?>" width="100%" height="400"></iframe>
</div>

<a href="list_submissions.php" class="nav-link">Back to Submissions List</a>

<script>
    function confirmReject() {
        const notes = document.getElementById('review_notes').value.trim();
        if (notes === '') {
            alert('Rejection reason (in review notes) is required to reject a submission.');
            return false;
        }
        return confirm('Are you sure you want to REJECT this submission?');
    }
</script>

<?php include '../include/finance_footer.php'; ?>
