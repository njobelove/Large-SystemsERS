<?php
// delete_invoice.php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    die("Invoice ID missing.");
}

$id = (int)$_GET['id'];

// Prepare the DELETE statement
$stmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: list_invoices.php?message=Invoice+deleted+successfully");
    exit;
} else {
    echo "Error deleting invoice: " . $conn->error;
}
?>
