<?php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    header('Location: list_leads.php?error=No+ID+provided');
    exit;
}

$id = (int)$_GET['id'];

if ($id <= 0) {
    header('Location: list_leads.php?error=Invalid+ID');
    exit;
}

$stmt = $conn->prepare("DELETE FROM leads WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header('Location: list_leads.php?message=Lead+deleted+successfully');
} else {
    header('Location: list_leads.php?error=Error+deleting+lead');
}

$stmt->close();
$conn->close();
exit;
?>