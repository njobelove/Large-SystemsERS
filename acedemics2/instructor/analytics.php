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
$user = getCurrentUser();
$userId = getCurrentUserId();

// Check if viewing specific instructor (from admin)
$viewInstructorId = isset($_GET['instructor_id']) ? (int)$_GET['instructor_id'] : null;
$instructorId = $viewInstructorId ?: $userId;

// Get instructor details
$instructor = getSingleRow("SELECT name FROM users WHERE id = ? AND role = 'instructor'", [$instructorId], 'i');
$instructorName = $instructor ? $instructor['name'] : 'Instructor';

// Get instructor's courses
$courses = getInstructorCourses($instructorId);

// Get analytics data
$analytics = [];
foreach ($courses as $course) {
    $courseId = $course['id'];

    // Get enrollment count
    $sql = "SELECT COUNT(*) as count FROM enrollments WHERE course_id = ? AND status = 'enrolled'";
    $enrollment = getSingleRow($sql, [$courseId], 'i');

    // Get average grade
    $sql = "SELECT AVG(g.grade) as avg_grade FROM grades g WHERE g.course_id = ?";
    $avgGrade = getSingleRow($sql, [$courseId], 'i');

    // Get attendance rate
    $sql = "SELECT
                COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
                COUNT(*) as total_count
            FROM attendance a
            WHERE a.course_id = ?";
    $attendance = getSingleRow($sql, [$courseId], 'i');

    // Get assignment submission rate
    $sql = "SELECT
                COUNT(DISTINCT s.student_id) as submitted_count,
                COUNT(DISTINCT e.student_id) as total_students
            FROM enrollments e
            LEFT JOIN submissions s ON e.student_id = s.student_id
            WHERE e.course_id = ? AND e.status = 'enrolled'";
    $submissions = getSingleRow($sql, [$courseId], 'i');

    $analytics[] = [
        'course' => $course,
        'enrollment' => $enrollment['count'] ?? 0,
        'avg_grade' => $avgGrade['avg_grade'] ?? 0,
        'attendance_rate' => $attendance['total_count'] > 0 ? round(($attendance['present_count'] / $attendance['total_count']) * 100, 1) : 0,
        'submission_rate' => $submissions['total_students'] > 0 ? round(($submissions['submitted_count'] / $submissions['total_students']) * 100, 1) : 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Instructor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">Academic System</div>
            <ul class="nav-links">
                <li><a href="../index.html" onclick="return confirm('Logout?')">Logout</a></li>
            </ul>
            <div class="user-info">
                <img src="../images/user-avatar.png" alt="User">
                <span><?php echo htmlspecialchars($instructorName); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Dashboard</a></li>
            <li><a href="courses.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Courses</a></li>
            <li><a href="attendance.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Attendance</a></li>
            <li><a href="gradebook.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Gradebook</a></li>
            <li><a href="assignments.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Assignments</a></li>
            <li><a href="course-materials.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Materials</a></li>
            <li><a href="analytics.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>" class="active">Analytics</a></li>
            <li><a href="schedule.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Course Analytics</h2>
        <p>View performance metrics for your courses.</p>

        <?php if (empty($analytics)): ?>
            <div class="card">
                <p>No analytics data available.</p>
            </div>
        <?php else: ?>
            <div class="card-container">
                <?php foreach ($analytics as $data): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($data['course']['code']); ?> - <?php echo htmlspecialchars($data['course']['title']); ?></h3>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                        <div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                            <strong><?php echo $data['enrollment']; ?></strong><br>
                            <small>Students</small>
                        </div>
                        <div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                            <strong><?php echo number_format($data['avg_grade'], 1); ?>%</strong><br>
                            <small>Avg Grade</small>
                        </div>
                        <div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                            <strong><?php echo $data['attendance_rate']; ?>%</strong><br>
                            <small>Attendance</small>
                        </div>
                        <div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                            <strong><?php echo $data['submission_rate']; ?>%</strong><br>
                            <small>Submissions</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
