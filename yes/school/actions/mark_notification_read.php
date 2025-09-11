
<?php
session_start();
include '../include/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

$notification_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
    exit;
}

// Mark the notification as read, ensuring it belongs to the current user
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $notification_id, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not update notification or permission denied.']);
}

$stmt->close();
$conn->close();
?>
