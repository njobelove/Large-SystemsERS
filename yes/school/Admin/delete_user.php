
<?php
session_start();
include '../include/auth_check.php';
checkRoleAccess(['admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    header('Location: user_management.php?message=Error:+User+ID+not+specified.');
    exit;
}

$id = (int)$_GET['id'];
if ($id <= 0) {
    header('Location: user_management.php?message=Error:+Invalid+User+ID.');
    exit;
}

// Prevent an admin from deleting their own account
if (isset($_SESSION['user_id']) && $id === (int)$_SESSION['user_id']) {
    header('Location: user_management.php?message=Error:+You+cannot+delete+your+own+account.');
    exit;
}

// Prepare and execute the DELETE statement
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Redirect on success
    header('Location: user_management.php?message=User+deleted+successfully.');
    exit;
} else {
    // Redirect on failure
    header('Location: user_management.php?message=Error:+Could+not+delete+user.');
    exit;
}

$stmt->close();
$conn->close();
?>
