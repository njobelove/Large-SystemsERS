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

// Get course ID from URL
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Verify instructor teaches this course
if ($courseId) {
    $sql = "SELECT id, code, title FROM courses WHERE id = ? AND instructor_id = ?";
    $course = getSingleRow($sql, [$courseId, $userId], 'ii');
}

// Get material statistics
$materialStats = [];
if ($courseId) {
    // Total materials
    $sql = "SELECT COUNT(*) as total_materials FROM materials WHERE course_id = ?";
    $totalMaterials = getSingleRow($sql, [$courseId], 'i')['total_materials'] ?? 0;

    // Materials by type (assuming file extensions indicate type)
    $sql = "SELECT
                CASE
                    WHEN file_path LIKE '%.pdf' THEN 'PDF'
                    WHEN file_path LIKE '%.doc%' THEN 'Document'
                    WHEN file_path LIKE '%.ppt%' THEN 'Presentation'
                    WHEN file_path LIKE '%.mp4' OR file_path LIKE '%.avi' THEN 'Video'
                    WHEN file_path LIKE '%.jpg' OR file_path LIKE '%.png' THEN 'Image'
                    ELSE 'Other'
                END as material_type,
                COUNT(*) as count
            FROM materials
            WHERE course_id = ?
            GROUP BY material_type";
    $materialTypes = getMultipleRows($sql, [$courseId], 'i');

    // Recent uploads
    $sql = "SELECT title, upload_date FROM materials WHERE course_id = ? ORDER BY upload_date DESC LIMIT 5";
    $recentUploads = getMultipleRows($sql, [$courseId], 'i');

    // Student access statistics (simulated since we don't have access logs)
    $sql = "SELECT COUNT(*) as enrolled_students FROM enrollments WHERE course_id = ? AND status = 'enrolled'";
    $enrolledStudents = getSingleRow($sql, [$courseId], 'i')['enrolled_students'] ?? 0;

    $materialStats = [
        'total_materials' => $totalMaterials,
        'material_types' => $materialTypes,
        'recent_uploads' => $recentUploads,
        'enrolled_students' => $enrolledStudents
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Statistics - Instructor</title>
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
            <li><a href="courses.php">Courses</a></li>
            <li><a href="attendance.php">Attendance</a></li>
            <li><a href="gradebook.php">Gradebook</a></li>
            <li><a href="assignments.php">Assignments</a></li>
            <li><a href="course-materials.php">Materials</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="schedule.php">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Material Statistics</h2>
        <?php if ($course): ?>
            <p>Statistics for: <strong><?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?></strong></p>
        <?php endif; ?>

        <?php if ($course && !empty($materialStats)): ?>
            <div class="card-container">
                <div class="card">
                    <h3>Overview</h3>
                    <p><strong>Total Materials:</strong> <?php echo htmlspecialchars($materialStats['total_materials']); ?></p>
                    <p><strong>Enrolled Students:</strong> <?php echo htmlspecialchars($materialStats['enrolled_students']); ?></p>
                </div>

                <div class="card">
                    <h3>Material Types</h3>
                    <?php if (empty($materialStats['material_types'])): ?>
                        <p>No materials uploaded yet.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($materialStats['material_types'] as $type): ?>
                            <li><?php echo htmlspecialchars($type['material_type']); ?>: <?php echo htmlspecialchars($type['count']); ?> files</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3>Recent Uploads</h3>
                    <?php if (empty($materialStats['recent_uploads'])): ?>
                        <p>No recent uploads.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($materialStats['recent_uploads'] as $upload): ?>
                            <li><?php echo htmlspecialchars($upload['title']); ?> - <?php echo formatDate($upload['upload_date']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($course): ?>
            <div class="card">
                <p>No materials have been uploaded for this course yet.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <p>Please select a course to view material statistics.</p>
                <a href="courses.php" class="view-link">Go to My Courses</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
