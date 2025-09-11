<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    header('Location: list_conversions.php?error=No+ID+provided');
    exit;
}

$id = (int)$_GET['id'];

if ($id <= 0) {
    header('Location: list_conversions.php?error=Invalid+ID');
    exit;
}

// Optional: Check if the conversion exists before attempting to delete
$checkStmt = $conn->prepare("SELECT id FROM conversions WHERE id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();
if ($result->num_rows === 0) {
    header('Location: list_conversions.php?error=Conversion+not+found');
    exit;
}
$checkStmt->close();

// Proceed with deletion
$stmt = $conn->prepare("DELETE FROM conversions WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header('Location: list_conversions.php?message=Conversion+deleted+successfully');
} else {
    header('Location: list_conversions.php?error=Error+deleting+conversion');
}

$stmt->close();
$conn->close();
exit;
?>