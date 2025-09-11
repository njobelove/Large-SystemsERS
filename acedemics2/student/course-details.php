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

// Get course ID from URL
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$courseId) {
    header('Location: view-courses.php');
    exit();
}

// Check if student is enrolled in this course
$sql = "SELECT c.id, c.code, c.title, c.description, u.name as instructor_name
        FROM courses c
        JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN users u ON c.instructor_id = u.id
        WHERE c.id = ? AND e.student_id = ? AND e.status = 'enrolled'";
$course = getSingleRow($sql, [$courseId, $userId], 'ii');

if (!$course) {
    header('Location: view-courses.php');
    exit();
}

// Get course assignments
$assignments = getCourseAssignments($courseId);

// Get course materials (if table exists)
$materials = [];
try {
    $sql = "SELECT id, title, description, file_path, created_at as upload_date
            FROM course_materials
            WHERE course_id = ?
            ORDER BY created_at DESC";
    $materials = getMultipleRows($sql, [$courseId], 'i');
} catch (Exception $e) {
    // Table doesn't exist, materials will be empty
    $materials = [];
}

// Get recent announcements (if table exists)
$announcements = [];
try {
    $sql = "SELECT title, content, created_at
            FROM announcements
            WHERE course_id = ?
            ORDER BY created_at DESC
            LIMIT 5";
    $announcements = getMultipleRows($sql, [$courseId], 'i');
} catch (Exception $e) {
    // Table doesn't exist, announcements will be empty
    $announcements = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details - <?php echo htmlspecialchars($course['code']); ?></title>
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
                    <span><?php echo htmlspecialchars($user['name'] ?? ($_SESSION['user_name'] ?? 'Student')); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <?php $studentParam = isset($_GET['student_id']) ? '?student_id=' . $_GET['student_id'] : ''; ?>
            <li><a href="dashboard.php<?php echo $studentParam; ?>">Dashboard</a></li>
            <li><a href="register-courses.php<?php echo $studentParam; ?>">Register Courses</a></li>
            <li><a href="view-courses.php<?php echo $studentParam; ?>">View Courses</a></li>
            <li><a href="course-details.php<?php echo $studentParam; ?>" class="active">Course Details</a></li>
            <li><a href="view-attendance.php<?php echo $studentParam; ?>">View Attendance</a></li>
            <li><a href="assignments.php<?php echo $studentParam; ?>">Assignments</a></li>
            <li><a href="schedule.php<?php echo $studentParam; ?>">Schedule</a></li>
            <li><a href="transcript.php<?php echo $studentParam; ?>">Transcript</a></li>
            <li><a href="view-grades.php<?php echo $studentParam; ?>">View Grades</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2><?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?></h2>

        <div class="card">
            <h3>Course Information</h3>
            <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name'] ?? 'TBA'); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($course['description']); ?></p>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Assignments</h3>
            <?php if (empty($assignments)): ?>
                <p>No assignments available.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Due Date</th>
                                <th>Max Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['description']); ?></td>
                                <td><?php echo formatDateTime($assignment['due_date']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['max_points']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Course Materials</h3>
            <?php if (empty($materials)): ?>
                <p>No materials available.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($materials as $material): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($material['title']); ?></strong>
                        <p><?php echo htmlspecialchars($material['description']); ?></p>
                        <p>Uploaded: <?php echo formatDate($material['upload_date']); ?></p>
                        <?php if ($material['file_path']): ?>
                            <a href="download-material.php?id=<?php echo $material['id']; ?>" class="btn" style="padding: 5px 10px; background-color: #4caf50; color: white; text-decoration: none; border-radius: 4px;">Download</a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Recent Announcements</h3>
            <?php if (empty($announcements)): ?>
                <p>No announcements available.</p>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                <div class="announcement">
                    <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                    <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                    <p class="announcement-date"><?php echo formatDate($announcement['created_at']); ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
