<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';
/*
// Ensure user is instructor
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

// Get instructor's courses with enrollment details
$courses = getInstructorCourses($instructorId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Instructor</title>
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
                <span><?php echo htmlspecialchars($instructorName); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Dashboard</a></li>
            <li><a href="courses.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>" class="active">Courses</a></li>
            <li><a href="attendance.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Attendance</a></li>
            <li><a href="gradebook.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Gradebook</a></li>
            <li><a href="assignments.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Assignments</a></li>
            <li><a href="materials.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Materials</a></li>
            <li><a href="analytics.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Analytics</a></li>
            <li><a href="schedule.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>My Courses</h2>
        <p>Manage the courses you are teaching.</p>

        <?php if (!empty($courses)): ?>
            <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #c3e6cb;">
                <strong>✓</strong> You have <?php echo count($courses); ?> course(s) assigned to you.
            </div>
        <?php endif; ?>

        <?php if (empty($courses)): ?>
            <div class="card">
                <p>You are not assigned to any courses yet.</p>
            </div>
        <?php else: ?>
            <div class="card-container">
                <?php foreach ($courses as $course): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?></h3>
                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                    <p><strong>Enrolled Students:</strong> <?php echo htmlspecialchars($course['enrolled_students']); ?></p>
                    <div style="margin-top: 15px;">
                        <a href="course-details.php?course_id=<?php echo $course['id']; ?>" class="view-link">View Details</a> |
                        <a href="attendance.php?course_id=<?php echo $course['id']; ?>" class="view-link">Manage Attendance</a> |
                        <a href="assignments.php?course_id=<?php echo $course['id']; ?>" class="view-link">Assignments</a> |
                        <a href="materials.php?course_id=<?php echo $course['id']; ?>" class="view-link">Materials</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
