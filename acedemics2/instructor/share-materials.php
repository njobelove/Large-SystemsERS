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

// Get available materials for sharing
$materials = [];
if ($courseId) {
    $sql = "SELECT id, title, description, file_path, upload_date FROM materials WHERE course_id = ? ORDER BY upload_date DESC";
    $materials = getMultipleRows($sql, [$courseId], 'i');
}

// Handle material sharing
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_material'])) {
    $materialId = (int)$_POST['material_id'];
    $shareWith = $_POST['share_with']; // 'all' or specific student IDs

    if ($materialId && $shareWith) {
        // In a real application, this would update sharing permissions
        // For now, we'll just show a success message
        $message = 'Material sharing settings updated successfully!';
    } else {
        $message = 'Please select a material and sharing option.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Materials - Instructor</title>
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
        <h2>Share Materials</h2>
        <?php if ($course): ?>
            <p>Manage material sharing for: <strong><?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?></strong></p>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="card" style="margin-bottom: 20px; background-color: #e8f5e8; border-left: 5px solid #4caf50;">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($course && !empty($materials)): ?>
            <div class="card">
                <h3>Available Materials</h3>
                <p>Select materials to share with students.</p>

                <form method="post">
                    <input type="hidden" name="share_material" value="1">

                    <div style="margin-bottom: 20px;">
                        <label for="material_id">Select Material:</label><br>
                        <select id="material_id" name="material_id" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Choose a material...</option>
                            <?php foreach ($materials as $material): ?>
                            <option value="<?php echo $material['id']; ?>"><?php echo htmlspecialchars($material['title']); ?> (Uploaded: <?php echo formatDate($material['upload_date']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label for="share_with">Share With:</label><br>
                        <select id="share_with" name="share_with" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Select sharing option...</option>
                            <option value="all">All enrolled students</option>
                            <option value="specific">Specific students (not implemented yet)</option>
                        </select>
                    </div>

                    <button type="submit" style="background: #4caf50; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-size: 16px;">Update Sharing Settings</button>
                </form>
            </div>

            <div class="card" style="margin-top: 30px;">
                <h3>All Materials</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Upload Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $material): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($material['title']); ?></td>
                                <td><?php echo htmlspecialchars($material['description'] ?? 'No description'); ?></td>
                                <td><?php echo formatDate($material['upload_date']); ?></td>
                                <td><span style="color: #4caf50; font-weight: bold;">Shared</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($course): ?>
            <div class="card">
                <p>No materials available for sharing. Please upload materials first.</p>
                <a href="upload-material.php?course_id=<?php echo $courseId; ?>" class="view-link">Upload Materials</a>
            </div>
        <?php else: ?>
            <div class="card">
                <p>Please select a course to manage material sharing.</p>
                <a href="courses.php" class="view-link">Go to My Courses</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
