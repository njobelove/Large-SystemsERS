<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

/* Ensure user is admin
if (!isAdmin()) {
    header('Location: ../instructor/admin');
    exit();
}
*/

$user = getCurrentUser();

// Get total students
$sql = "SELECT COUNT(*) as total_students FROM users WHERE role = 'student'";
$totalStudents = getSingleRow($sql)['total_students'] ?? 0;

// Get total instructors
$sql = "SELECT COUNT(*) as total_instructors FROM users WHERE role = 'instructor'";
$totalInstructors = getSingleRow($sql)['total_instructors'] ?? 0;

// Get total courses
$sql = "SELECT COUNT(*) as total_courses FROM courses";
$totalCourses = getSingleRow($sql)['total_courses'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">Academic System</div>
            <ul class="nav-links">
                <li><a href="#" onclick="logout()">Logout</a></li>
            </ul>
            <div class="user-info">
                <img src="../images/user-avatar.png" alt="User" />
                <span>Administrator</span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="courses.php">Courses</a></li>
            <li><a href="programs.php">Programs</a></li>
            <li><a href="semester.php">Semesters</a></li>
            <li><a href="student-info.php">Students</a></li>
            <li><a href="instructor-info.php">Instructors</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Admin Dashboard</h2>
        <p>Welcome to the administration panel.</p>

        <div class="card-container">
            <div class="card">
                <h3>Total Students</h3>
                <p><?php echo $totalStudents; ?></p>
            </div>
            <div class="card">
                <h3>Total Instructors</h3>
                <p><?php echo $totalInstructors; ?></p>
            </div>
            <div class="card">
                <h3>Total Courses</h3>
                <p><?php echo $totalCourses; ?></p>
            </div>
        </div>
    </main>

    <script src="../js/admin.js"></script>
</body>
</html>
