<?php
// delete_payment.php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    die("Payment ID missing.");
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: list_payments.php?message=Payment+deleted+successfully");
    exit;
} else {
    echo "Error deleting payment: " . $conn->error;
}
