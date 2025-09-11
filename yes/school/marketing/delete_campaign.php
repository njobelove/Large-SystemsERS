<?php
// delete_campaign.php
include '../include/auth_check.php';
checkRoleAccess(['marketing', 'admin']);
include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    die('Campaign ID missing.');
}

$id = (int)$_GET['id'];
if ($id <= 0) {
    die('Invalid campaign ID.');
}

$stmt = $conn->prepare('DELETE FROM campaigns WHERE id = ?');
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    header('Location: list_campaigns.php?message=Campaign+deleted+successfully');
    exit;
} else {
    echo 'Error deleting campaign: ' . $conn->error;
}
