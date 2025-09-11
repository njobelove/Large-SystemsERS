<?php
require_once '../config.php';
require_once '../functions.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

if (isset($_GET['code'])) {
    $courseCode = $_GET['code'];

    // Query based on user role
    if ($userRole === 'instructor') {
        $course = getSingleRow("
            SELECT id, code, title FROM courses
            WHERE code = ? AND instructor_id = ?
        ", [$courseCode, $userId], 'si');
    } elseif ($userRole === 'student') {
        $course = getSingleRow("
            SELECT c.id, c.code, c.title FROM courses c
            JOIN enrollments e ON c.id = e.course_id
            WHERE c.code = ? AND e.student_id = ?
        ", [$courseCode, $userId], 'si');
    } elseif ($userRole === 'admin') {
        $course = getSingleRow("
            SELECT id, code, title FROM courses
            WHERE code = ?
        ", [$courseCode], 's');
    } else {
        echo json_encode(['error' => 'Invalid user role']);
        exit();
    }

    if ($course) {
        echo json_encode([
            'id' => $course['id'],
            'code' => $course['code'],
            'title' => $course['title']
        ]);
    } else {
        echo json_encode(['error' => 'Course not found']);
    }
} else {
    echo json_encode(['error' => 'Course code required']);
}
?>
