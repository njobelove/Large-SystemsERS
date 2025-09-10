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

// Get course materials for enrolled courses
$sql = "SELECT m.id, m.title, m.description, m.file_path, m.upload_date,
               c.code, c.title as course_title
        FROM materials m
        JOIN courses c ON m.course_id = c.id
        JOIN enrollments e ON c.id = e.course_id
        WHERE e.student_id = ? AND e.status = 'enrolled'
        ORDER BY m.upload_date DESC";
$materials = getMultipleRows($sql, [$userId], 'i');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - Student</title>
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
            <li><a href="register-courses.php">Register Courses</a></li>
            <li><a href="view-courses.php">View Courses</a></li>
            <li><a href="course-details.php">Course Details</a></li>
            <li><a href="view-attendance.php">View Attendance</a></li>
            <li><a href="assignments.php">Assignments</a></li>
            <li><a href="schedule.php">Schedule</a></li>
            <li><a href="transcript.php">Transcript</a></li>
            <li><a href="view-grades.php">View Grades</a></li>
            <li><a href="resources.php" class="active">Resources</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Course Resources</h2>
        <p>Access materials and resources for your enrolled courses.</p>

        <?php if (empty($materials)): ?>
            <div class="card">
                <p>No resources available at this time.</p>
            </div>
        <?php else: ?>
            <div class="card-container">
                <?php foreach ($materials as $material): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($material['title']); ?></h3>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($material['code']); ?> - <?php echo htmlspecialchars($material['course_title']); ?></p>
                    <p><?php echo htmlspecialchars($material['description']); ?></p>
                    <p><strong>Uploaded:</strong> <?php echo formatDate($material['upload_date']); ?></p>
                    <?php if ($material['file_path']): ?>
                        <a href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank" class="view-link">Download/View Resource</a>
                    <?php else: ?>
                        <p><em>File not available</em></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
