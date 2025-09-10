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

// Handle admin or instructor viewing student's courses
if ((isAdmin() || isInstructor()) && isset($_GET['student_id'])) {
    $userId = (int)$_GET['student_id'];
    $user = getUserById($userId);
}

// Fetch enrolled courses
$courses = getStudentCourses($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Courses - Student</title>
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
            <?php $studentParam = isset($_GET['student_id']) ? '?student_id=' . $_GET['student_id'] : ''; ?>
            <li><a href="dashboard.php<?php echo $studentParam; ?>">Dashboard</a></li>
            <li><a href="register-courses.php<?php echo $studentParam; ?>">Register Courses</a></li>
            <li><a href="view-courses.php<?php echo $studentParam; ?>" class="active">View Courses</a></li>
            <li><a href="course-details.php<?php echo $studentParam; ?>">Course Details</a></li>
            <li><a href="view-attendance.php<?php echo $studentParam; ?>">View Attendance</a></li>
            <li><a href="assignments.php<?php echo $studentParam; ?>">Assignments</a></li>
            <li><a href="schedule.php<?php echo $studentParam; ?>">Schedule</a></li>
            <li><a href="transcript.php<?php echo $studentParam; ?>">Transcript</a></li>
            <li><a href="view-grades.php<?php echo $studentParam; ?>">View Grades</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>My Courses</h2>
        <p>Here are the courses you are currently enrolled in.</p>

        <?php if (empty($courses)): ?>
            <div class="card">
                <p>You are not enrolled in any courses yet. <a href="register-courses.php">Register for courses</a>.</p>
            </div>
        <?php else: ?>
            <div class="card-container">
                <?php foreach ($courses as $course): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?></h3>
                    <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name'] ?? 'TBA'); ?></p>
                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                    <a href="course-details.php?course_id=<?php echo $course['id']; ?>" class="view-link">View Details</a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
