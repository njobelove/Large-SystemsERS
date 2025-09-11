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

// For demo purposes, create a sample schedule
// In a real application, this would come from a schedule table
$schedule = [
    'Monday' => [
        ['time' => '09:00 - 10:30', 'course' => 'CS101', 'title' => 'Introduction to Programming', 'room' => 'Room 101'],
        ['time' => '11:00 - 12:30', 'course' => 'BUS201', 'title' => 'Business Management', 'room' => 'Room 205']
    ],
    'Tuesday' => [
        ['time' => '10:00 - 11:30', 'course' => 'CS101', 'title' => 'Introduction to Programming', 'room' => 'Room 101']
    ],
    'Wednesday' => [
        ['time' => '09:00 - 10:30', 'course' => 'BUS201', 'title' => 'Business Management', 'room' => 'Room 205'],
        ['time' => '14:00 - 15:30', 'course' => 'CS101', 'title' => 'Introduction to Programming', 'room' => 'Room 101']
    ],
    'Thursday' => [
        ['time' => '11:00 - 12:30', 'course' => 'BUS201', 'title' => 'Business Management', 'room' => 'Room 205']
    ],
    'Friday' => [
        ['time' => '10:00 - 11:30', 'course' => 'CS101', 'title' => 'Introduction to Programming', 'room' => 'Room 101']
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - Student</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">Academic System</div>
            <ul class="nav-links">
                <li><a href="../index.php">Logout</a></li>
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
            <li><a href="view-courses.php<?php echo $studentParam; ?>">View Courses</a></li>
            <li><a href="course-details.php<?php echo $studentParam; ?>">Course Details</a></li>
            <li><a href="view-attendance.php<?php echo $studentParam; ?>">View Attendance</a></li>
            <li><a href="assignments.php<?php echo $studentParam; ?>">Assignments</a></li>
            <li><a href="schedule.php<?php echo $studentParam; ?>" class="active">Schedule</a></li>
            <li><a href="transcript.php<?php echo $studentParam; ?>">Transcript</a></li>
            <li><a href="view-grades.php<?php echo $studentParam; ?>">View Grades</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>My Schedule</h2>
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
    </main>
</body>
</html>
