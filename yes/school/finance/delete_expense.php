<?php
include '../include/auth_check.php';
checkRoleAccess(['finance', 'admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: list_expenses.php?error=No ID specified.");
    exit;
}

$id = (int)$_GET['id'];

// Prepare the DELETE statement
$stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Redirect back to the list view on success
    header("Location: list_expenses.php?message=Expense deleted successfully.");
    exit;
} else {
    // Handle error
    header("Location: list_expenses.php?error=Error deleting expense: " . urlencode($stmt->error));
    exit;
}

$stmt->close();
$conn->close();
?>