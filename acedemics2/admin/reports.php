<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

/*// Ensure user is admin
if (!isAdmin()) {
    header('Location: ../index.html');
    exit();
}
*/
$user = getCurrentUser();

// Get system statistics
$sql = "SELECT COUNT(*) as total_students FROM users WHERE role = 'student'";
$totalStudents = getSingleRow($sql)['total_students'] ?? 0;

$sql = "SELECT COUNT(*) as total_instructors FROM users WHERE role = 'instructor'";
$totalInstructors = getSingleRow($sql)['total_instructors'] ?? 0;

$sql = "SELECT COUNT(*) as total_courses FROM courses";
$totalCourses = getSingleRow($sql)['total_courses'] ?? 0;

$sql = "SELECT COUNT(*) as total_enrollments FROM enrollments WHERE status = 'enrolled'";
$totalEnrollments = getSingleRow($sql)['total_enrollments'] ?? 0;

// Get course enrollment statistics
$sql = "SELECT c.title, COUNT(e.student_id) as enrolled_count
        FROM courses c
        LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
        GROUP BY c.id
        ORDER BY enrolled_count DESC
        LIMIT 10";
$courseStats = getMultipleRows($sql);

// Get grade distribution
$sql = "SELECT
            COUNT(CASE WHEN grade >= 90 THEN 1 END) as a_count,
            COUNT(CASE WHEN grade >= 80 AND grade < 90 THEN 1 END) as b_count,
            COUNT(CASE WHEN grade >= 70 AND grade < 80 THEN 1 END) as c_count,
            COUNT(CASE WHEN grade >= 60 AND grade < 70 THEN 1 END) as d_count,
            COUNT(CASE WHEN grade < 60 THEN 1 END) as f_count
        FROM grades";
$gradeStats = getSingleRow($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
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
                <span><?php echo htmlspecialchars($user['name']); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="courses.php">Courses</a></li>
            <li><a href="programs.php">Programs</a></li>
            <li><a href="semester.php">Semesters</a></li>
            <li><a href="student-info.php">Students</a></li>
            <li><a href="instructor-info.php">Instructors</a></li>
            <li><a href="reports.php" class="active">Reports</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>System Reports</h2>
        <p>View comprehensive reports and statistics.</p>

        <div class="card-container">
            <div class="card">
                <h3>System Overview</h3>
                <p><strong>Total Students:</strong> <?php echo $totalStudents; ?></p>
                <p><strong>Total Instructors:</strong> <?php echo $totalInstructors; ?></p>
                <p><strong>Total Courses:</strong> <?php echo $totalCourses; ?></p>
                <p><strong>Total Enrollments:</strong> <?php echo $totalEnrollments; ?></p>
            </div>

            <div class="card">
                <h3>Grade Distribution</h3>
                <p><strong>A (90-100%):</strong> <?php echo $gradeStats['a_count'] ?? 0; ?></p>
                <p><strong>B (80-89%):</strong> <?php echo $gradeStats['b_count'] ?? 0; ?></p>
                <p><strong>C (70-79%):</strong> <?php echo $gradeStats['c_count'] ?? 0; ?></p>
                <p><strong>D (60-69%):</strong> <?php echo $gradeStats['d_count'] ?? 0; ?></p>
                <p><strong>F (0-59%):</strong> <?php echo $gradeStats['f_count'] ?? 0; ?></p>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Top Enrolled Courses</h3>
            <?php if (empty($courseStats)): ?>
                <p>No enrollment data available.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Title</th>
                                <th>Enrolled Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courseStats as $stat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['title']); ?></td>
                                <td><?php echo htmlspecialchars($stat['enrolled_count']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
