<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

/*// Ensure user is student
if (!isStudent()) {
    header('Location: ../index.html');
    exit();
}
*/
$user = getCurrentUser();
$userId = getCurrentUserId();

if (!isset($_GET['id'])) {
    die('Material ID not provided');
}

$materialId = (int)$_GET['id'];

// Get material details and verify student has access to this course
$material = getSingleRow("
    SELECT cm.*, c.title as course_title
    FROM course_materials cm
    JOIN courses c ON cm.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE cm.id = ? AND e.student_id = ? AND e.status = 'enrolled'
", [$materialId, $userId], 'ii');

if (!$material) {
    die('Material not found or you do not have access to this material');
}

// Check if file exists
if (!$material['file_path'] || !file_exists($material['file_path'])) {
    die('File not found on server');
}

// Get file information
$filePath = $material['file_path'];
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
if (ob_get_level()) {
    ob_clean();
}

// Read and output file
readfile($filePath);
exit();
?>
