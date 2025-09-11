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
$course = null;

// Verify instructor teaches this course
if ($courseId) {
    $sql = "SELECT id, code, title FROM courses WHERE id = ? AND instructor_id = ?";
    $course = getSingleRow($sql, [$courseId, $userId], 'ii');
}

// Handle material upload
$message = '';
$redirectToMaterials = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);

    if ($title && isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['material_file'];
        $fileName = basename($file['name']);

        // Generate unique filename to prevent conflicts
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = '../uploads/materials/' . $uniqueFileName;

        // Create uploads directory if it doesn't exist
        if (!is_dir('../uploads/materials/')) {
            mkdir('../uploads/materials/', 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Save to database
            $sql = "INSERT INTO course_materials (course_id, title, description, file_path, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $success = executeNonQuery($sql, [$courseId, $title, $description, $filePath, $userId], 'isssi');

            if ($success) {
                $message = 'Material uploaded successfully!';
                $redirectToMaterials = true;
            } else {
                $message = 'Failed to save material to database.';
                unlink($filePath); // Remove uploaded file if DB insert failed
            }
        } else {
            $message = 'Failed to upload file.';
        }
    } else {
        $message = 'Please provide a title and select a file to upload.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Material - Instructor</title>
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
            <?php $instructorParam = isset($_GET['instructor_id']) ? '?instructor_id=' . $_GET['instructor_id'] : ''; ?>
            <li><a href="dashboard.php<?php echo $instructorParam; ?>">Dashboard</a></li>
            <li><a href="courses.php<?php echo $instructorParam; ?>">Courses</a></li>
            <li><a href="attendance.php<?php echo $instructorParam; ?>">Attendance</a></li>
            <li><a href="gradebook.php<?php echo $instructorParam; ?>">Gradebook</a></li>
            <li><a href="assignments.php<?php echo $instructorParam; ?>">Assignments</a></li>
            <li><a href="materials.php<?php echo $instructorParam; ?>">Materials</a></li>
            <li><a href="analytics.php<?php echo $instructorParam; ?>">Analytics</a></li>
            <li><a href="schedule.php<?php echo $instructorParam; ?>">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Upload Material</h2>
        <?php if ($course): ?>
            <p>Uploading material for: <strong><?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?></strong></p>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="card" style="margin-bottom: 20px; background-color: #e8f5e8; border-left: 5px solid #4caf50;">
                <p><?php echo htmlspecialchars($message); ?></p>
                <?php if ($redirectToMaterials): ?>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'materials.php?course_id=<?php echo $courseId; ?>';
                        }, 2000);
                    </script>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($course): ?>
            <div class="card">
                <h3>Upload New Material</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="upload_material" value="1">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div style="grid-column: span 2;">
                            <label for="title">Material Title:</label><br>
                            <input type="text" id="title" name="title" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>

                        <div style="grid-column: span 2;">
                            <label for="description">Description:</label><br>
                            <textarea id="description" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                        </div>

                        <div style="grid-column: span 2;">
                            <label for="material_file">Select File:</label><br>
                            <input type="file" id="material_file" name="material_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.mp4,.avi,.jpg,.png,.txt" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <small style="color: #666;">Accepted formats: PDF, DOC, PPT, MP4, AVI, JPG, PNG, TXT</small>
                        </div>
                    </div>

                    <button type="submit" style="margin-top: 20px; background: #4caf50; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-size: 16px;">Upload Material</button>
                </form>
            </div>

            <div class="card" style="margin-top: 30px;">
                <h3>Upload Guidelines</h3>
                <ul>
                    <li>Maximum file size: 50MB</li>
                    <li>Supported formats: Documents (PDF, DOC, DOCX), Presentations (PPT, PPTX), Videos (MP4, AVI), Images (JPG, PNG), Text files (TXT)</li>
                    <li>Use descriptive titles and descriptions to help students find materials easily</li>
                    <li>Materials will be automatically shared with all enrolled students</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="card">
                <p>Please select a course to upload materials.</p>
                <a href="courses.php" class="view-link">Go to My Courses</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
