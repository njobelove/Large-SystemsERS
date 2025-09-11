<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

$user = getCurrentUser();
$userId = getCurrentUserId();

// Get courses taught by instructor
$courses = getMultipleRows("SELECT id, code, title FROM courses WHERE instructor_id = ?", [$userId], 'i');

// Handle course selection
$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$selectedCourse = null;
if ($selectedCourseId) {
    $selectedCourse = getSingleRow("SELECT id, code, title FROM courses WHERE id = ? AND instructor_id = ?", [$selectedCourseId, $userId], 'ii');
}

// Get materials for selected course
$materials = [];
if ($selectedCourse) {
    $materials = getMultipleRows("SELECT id, title, description, file_path, created_at FROM course_materials WHERE course_id = ? ORDER BY created_at DESC", [$selectedCourseId], 'i');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Course Materials - Instructor</title>
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        .materials-list {
            margin-top: 20px;
        }
        .material-item {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .material-item h4 {
            margin: 0 0 5px 0;
        }
        .upload-button {
            margin-top: 20px;
        }
        #uploadForm {
            display: none;
            margin-top: 20px;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        #uploadForm label {
            display: block;
            margin-bottom: 5px;
        }
        #uploadForm input[type="text"],
        #uploadForm textarea,
        #uploadForm input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        #uploadForm button {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        #uploadForm button:hover {
            background-color: #45a049;
        }
    </style>
    <script>
        function toggleUploadForm() {
            const form = document.getElementById('uploadForm');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
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
            <li><a href="materials.php" class="active">Materials</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="schedule.php">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Course Materials</h2>

        <form method="get" action="materials.php">
            <label for="courseSelect">Select Course:</label>
            <select id="courseSelect" name="course_id" onchange="this.form.submit()">
                <option value="">-- Select a course --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>" <?php if ($selectedCourse && $selectedCourse['id'] == $course['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selectedCourse): ?>
            <button class="upload-button" onclick="toggleUploadForm()">Upload New Material</button>

            <div id="uploadForm" style="display:none;">
                <h3>Upload New Material</h3>
                <form method="post" action="upload-material.php?course_id=<?php echo $selectedCourse['id']; ?>" enctype="multipart/form-data">
                    <label for="title">Material Title:</label>
                    <input type="text" id="title" name="title" required />

                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="3"></textarea>

                    <label for="material_file">Select File:</label>
                    <input type="file" id="material_file" name="material_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.mp4,.avi,.jpg,.png,.txt" />

                    <button type="submit">Upload Material</button>
                </form>
            </div>

            <div class="materials-list">
                <?php if (count($materials) > 0): ?>
                    <?php foreach ($materials as $material): ?>
                        <div class="material-item">
                            <h4><?php echo htmlspecialchars($material['title']); ?></h4>
                            <p><?php echo nl2br(htmlspecialchars($material['description'])); ?></p>
                            <p><small>Uploaded on: <?php echo date('M d, Y', strtotime($material['created_at'])); ?></small></p>
                            <p><a href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank" download>Download</a></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No materials uploaded for this course yet.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Please select a course to view and upload materials.</p>
        <?php endif; ?>
    </main>
</body>
</html>
