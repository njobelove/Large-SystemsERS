<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

/*// Ensure user is instructor
if (!isInstructor()) {
    header('Location: ../index.html');
    exit();
}
*/

$userId = getCurrentUserId();

$submissionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$submissionId) {
    header('Location: assignment-submissions.php');
    exit();
}

// Get submission details and verify instructor owns the assignment
$submission = getSingleRow("
    SELECT s.*, a.course_id, c.instructor_id
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.id = ? AND c.instructor_id = ?
", [$submissionId, $userId], 'ii');

if (!$submission) {
    header('Location: assignment-submissions.php');
    exit();
}

if (empty($submission['file_path'])) {
    header('Location: assignment-submissions.php');
    exit();
}

// Check if file exists
$filePath = '../' . $submission['file_path'];
if (!file_exists($filePath)) {
    header('Location: assignment-submissions.php');
    exit();
}

// Get file info
$fileName = basename($filePath);
$fileSize = filesize($filePath);

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Clear output buffer
ob_clean();
flush();

// Read and output file
readfile($filePath);
exit();
?>
