<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

/*// Ensure user is student
if (!isStudent()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}
*/

$user = getCurrentUser();
$userId = getCurrentUserId();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$assignmentId = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if (!$assignmentId) {
    echo json_encode(['success' => false, 'message' => 'Invalid assignment ID']);
    exit();
}

// Check if assignment exists and student is enrolled
$assignment = getSingleRow("
    SELECT a.*, c.code, c.title as course_title
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE a.id = ? AND e.student_id = ? AND e.status = 'enrolled'
", [$assignmentId, $userId], 'ii');

if (!$assignment) {
    echo json_encode(['success' => false, 'message' => 'Assignment not found or you are not enrolled in this course']);
    exit();
}

// Check if assignment is overdue
if (strtotime($assignment['due_date']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Assignment is overdue and cannot be submitted']);
    exit();
}

// Check if student has already submitted
$existingSubmission = getSingleRow("SELECT id FROM submissions WHERE assignment_id = ? AND student_id = ?", [$assignmentId, $userId], 'ii');
if ($existingSubmission) {
    echo json_encode(['success' => false, 'message' => 'You have already submitted this assignment']);
    exit();
}

// Handle file upload
$filePath = null;
if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['assignment_file'];

    // Validate file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 10MB limit']);
        exit();
    }

    // Validate file type
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'application/zip'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP']);
        exit();
    }

    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueFilename = uniqid('submission_' . $assignmentId . '_', true) . '.' . $fileExtension;
    $uploadDir = '../uploads/submissions/';
    $filePath = $uploadDir . $uniqueFilename;

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit();
    }
}

// Insert submission into database
try {
    $sql = "INSERT INTO submissions (assignment_id, student_id, submitted_at, file_path, feedback) VALUES (?, ?, NOW(), ?, ?)";
    $result = executeQuery($sql, [$assignmentId, $userId, $filePath, $notes], 'iiss');

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Assignment submitted successfully']);
    } else {
        // Clean up uploaded file if database insert failed
        if ($filePath && file_exists($filePath)) {
            unlink($filePath);
        }
        echo json_encode(['success' => false, 'message' => 'Failed to save submission']);
    }
} catch (Exception $e) {
    // Clean up uploaded file if database insert failed
    if ($filePath && file_exists($filePath)) {
        unlink($filePath);
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
