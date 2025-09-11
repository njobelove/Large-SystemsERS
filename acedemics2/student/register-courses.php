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

// Get available courses (not enrolled and not full)
$sql = "SELECT c.id, c.code, c.title, c.description, c.credits, c.max_students,
               COUNT(e.student_id) as enrolled_count, u.name as instructor_name
        FROM courses c
        LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
        LEFT JOIN users u ON c.instructor_id = u.id
        WHERE c.id NOT IN (
            SELECT course_id FROM enrollments
            WHERE student_id = ? AND status = 'enrolled'
        )
        GROUP BY c.id
        HAVING enrolled_count < c.max_students
        ORDER BY c.title";
$availableCourses = getMultipleRows($sql, [$userId], 'i');

// Handle course registration
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $courseId = (int)$_POST['course_id'];

    // Check if already enrolled
    $checkSql = "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ? AND status = 'enrolled'";
    $existing = getSingleRow($checkSql, [$userId, $courseId], 'ii');

    if ($existing) {
        $message = 'You are already enrolled in this course.';
    } else {
        // Register for course
        $insertSql = "INSERT INTO enrollments (student_id, course_id, enrollment_date, status) VALUES (?, ?, NOW(), 'enrolled')";
        $success = executeNonQuery($insertSql, [$userId, $courseId], 'ii');

        if ($success) {
            $message = 'Successfully registered for the course!';
            // Refresh available courses
            $availableCourses = getMultipleRows($sql, [$userId], 'i');
        } else {
            $message = 'Failed to register for the course. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Courses - Student</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">Academic System</div>
            <ul class="nav-links">
                <li><a href="#" onclick="logout()">Logout</a></li>
            </ul>
            <div class="user-info">
                <img src="../images/user-avatar.png" alt="User">
                <span><?php echo htmlspecialchars($user['name']); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="register-courses.php" class="active">Register Courses</a></li>
            <li><a href="view-courses.php">View Courses</a></li>
            <li><a href="course-details.php">Course Details</a></li>
            <li><a href="view-attendance.php">View Attendance</a></li>
            <li><a href="assignments.php">Assignments</a></li>
            <li><a href="schedule.php">Schedule</a></li>
            <li><a href="transcript.php">Transcript</a></li>
            <li><a href="view-grades.php">View Grades</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Register for Courses</h2>
        <p>Select courses to register for the current semester.</p>

        <?php if ($message): ?>
            <div class="card" style="margin-bottom: 20px; background-color: #e8f5e8; border-left: 5px solid #4caf50;">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($availableCourses)): ?>
            <div class="card">
                <p>No courses available for registration at this time.</p>
            </div>
        <?php else: ?>
            <div class="card-container">
                <?php foreach ($availableCourses as $course): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?></h3>
                    <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name'] ?? 'TBA'); ?></p>
                    <p><strong>Credits:</strong> <?php echo htmlspecialchars($course['credits']); ?></p>
                    <p><strong>Enrolled:</strong> <?php echo htmlspecialchars($course['enrolled_count']); ?>/<?php echo htmlspecialchars($course['max_students']); ?></p>
                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                    <form method="post" style="margin-top: 15px;">
                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                        <button type="submit" class="btn" style="background: #4caf50; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Register</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
