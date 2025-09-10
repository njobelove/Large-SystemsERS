<?php
require_once '../config.php';
require_once '../functions.php';

// Start session
session_start();

// Check if user is logged in as instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header('Location: ../index.html');
    exit();
}

$userId = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die('Material ID not provided');
}

$materialId = (int)$_GET['id'];

// Get material details and verify ownership
$material = getSingleRow("
    SELECT cm.*, c.instructor_id, c.title as course_title
    FROM course_materials cm
    JOIN courses c ON cm.course_id = c.id
    WHERE cm.id = ?
", [$materialId], 'i');

if (!$material) {
    die('Material not found');
}

if ($material['instructor_id'] !== $userId) {
    die('Unauthorized access');
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
