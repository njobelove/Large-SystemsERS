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

// Get instructor's courses for schedule
$courses = getInstructorCourses($instructorId);

// For demo purposes, create a sample schedule
// In a real application, this would come from a schedule table
$schedule = [
    'Monday' => [
        ['time' => '09:00 - 10:30', 'course' => 'CS101', 'title' => 'Introduction to Programming', 'room' => 'Room 101'],
        ['time' => '11:00 - 12:30', 'course' => 'CS201', 'title' => 'Data Structures', 'room' => 'Room 203']
    ],
    'Tuesday' => [
        ['time' => '10:00 - 11:30', 'course' => 'CS101', 'title' => 'Introduction to Programming', 'room' => 'Room 101'],
        ['time' => '14:00 - 15:30', 'course' => 'CS201', 'title' => 'Data Structures', 'room' => 'Room 203']
    ],
    'Wednesday' => [
        ['time' => '09:00 - 10:30', 'course' => 'CS101', 'title' => 'Introduction to Programming', 'room' => 'Room 101']
    ],
    'Thursday' => [
        ['time' => '11:00 - 12:30', 'course' => 'CS201', 'title' => 'Data Structures', 'room' => 'Room 203']
    ],
    'Friday' => [
        ['time' => '10:00 - 11:30', 'course' => 'CS101', 'title' => 'Introduction to Programming', 'room' => 'Room 101'],
        ['time' => '13:00 - 14:30', 'course' => 'CS201', 'title' => 'Data Structures', 'room' => 'Room 203']
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teaching Schedule - Instructor</title>
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
            <li><a href="courses.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Courses</a></li>
            <li><a href="attendance.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Attendance</a></li>
            <li><a href="gradebook.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Gradebook</a></li>
            <li><a href="assignments.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Assignments</a></li>
            <li><a href="course-materials.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Materials</a></li>
            <li><a href="analytics.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Analytics</a></li>
            <li><a href="schedule.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>" class="active">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>My Teaching Schedule</h2>
        <p>Your weekly class schedule.</p>

        <div class="card">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <?php foreach ($schedule as $day => $classes): ?>
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
                    <h3><?php echo htmlspecialchars($day); ?></h3>
                    <?php if (empty($classes)): ?>
                        <p>No classes</p>
                    <?php else: ?>
                        <?php foreach ($classes as $class): ?>
                        <div style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                            <p><strong><?php echo htmlspecialchars($class['time']); ?></strong></p>
                            <p><?php echo htmlspecialchars($class['course']); ?> - <?php echo htmlspecialchars($class['title']); ?></p>
                            <p><em><?php echo htmlspecialchars($class['room']); ?></em></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>My Courses</h3>
            <p>Quick access to course management.</p>
            <?php if (empty($courses)): ?>
                <p>You are not assigned to any courses yet.</p>
            <?php else: ?>
                <div class="card-container">
                    <?php foreach ($courses as $course): ?>
                    <div class="card">
                        <h4><?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?></h4>
                        <p>Enrolled Students: <?php echo htmlspecialchars($course['enrolled_students']); ?></p>
                        <div style="margin-top: 10px;">
                            <a href="course-details.php?course_id=<?php echo $course['id']; ?>" class="view-link">View Details</a> |
                            <a href="attendance.php?course_id=<?php echo $course['id']; ?>" class="view-link">Attendance</a> |
                            <a href="assignments.php?course_id=<?php echo $course['id']; ?>" class="view-link">Assignments</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
