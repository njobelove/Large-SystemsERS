<?php
require_once '../config.php';
require_once '../functions.php';

// Start session
session_start();

/*// Check if user is logged in as instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header('Location: ../index.html');
    exit();
}
*/
$userId = $_SESSION['user_id'];

// Check if viewing specific instructor (from admin)
$viewInstructorId = isset($_GET['instructor_id']) ? (int)$_GET['instructor_id'] : null;
$instructorId = $viewInstructorId ?: $userId;

// Get instructor details
$instructor = getSingleRow("SELECT name FROM users WHERE id = ? AND role = 'instructor'", [$instructorId], 'i');
$instructorName = $instructor ? $instructor['name'] : 'Instructor';

// Get instructor's courses
$courses = getInstructorCourses($instructorId);

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    $action = $_GET['ajax'];

    if ($action === 'load_materials' && isset($_GET['course_id'])) {
        $courseId = (int)$_GET['course_id'];

        // Verify course belongs to instructor
        $course = getSingleRow("SELECT id FROM courses WHERE id = ? AND instructor_id = ?", [$courseId, $instructorId], 'ii');
        if (!$course) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        // Get course materials
        $materials = getMultipleRows("
            SELECT
                id,
                title as file_name,
                material_type as file_type,
                'General' as category,
                created_at as upload_date,
                NULL as file_size,
                0 as download_count,
                'public' as visibility
            FROM course_materials
            WHERE course_id = ?
            ORDER BY created_at DESC
        ", [$courseId], 'i');

        echo json_encode(['materials' => $materials]);
        exit();
    }

    if ($action === 'delete_material' && isset($_POST['material_id'])) {
        $materialId = (int)$_POST['material_id'];

        // Get material details to verify ownership
        $material = getSingleRow("
            SELECT cm.*, c.instructor_id
            FROM course_materials cm
            JOIN courses c ON cm.course_id = c.id
            WHERE cm.id = ?
        ", [$materialId], 'i');

        if (!$material || $material['instructor_id'] !== $instructorId) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        // Delete file if it exists
        if ($material['file_path'] && file_exists($material['file_path'])) {
            unlink($material['file_path']);
        }

        // Delete from database
        $result = executeNonQuery("DELETE FROM course_materials WHERE id = ?", [$materialId], 'i');

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to delete material']);
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials</title>
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
            <li><a href="course-materials.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>" class="active">Materials</a></li>
            <li><a href="analytics.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Analytics</a></li>
            <li><a href="schedule.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Course Materials Management</h2>

        <?php if (!empty($courses)): ?>
            <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #c3e6cb;">
                <strong>✓</strong> You have <?php echo count($courses); ?> course(s) available for materials management.
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="materialsCourse">Select Course:</label>
            <select id="materialsCourse" onchange="loadCourseMaterials()">
                <option value="">Select a course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="materialsContainer" style="display: none;">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 id="materialsTitle">Course Materials</h3>
                    <button class="btn" onclick="uploadMaterial()">Upload New Material</button>
                </div>

                <div class="table-container">
                    <table id="materialsTable">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Upload Date</th>
                                <th>Size</th>
                                <th>Downloads</th>
                                <th>Visibility</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>
    <script src="../js/instructor.js"></script>
    <script>
        function loadCourseMaterials() {
            const courseId = document.getElementById('materialsCourse').value;
            if (!courseId) {
                document.getElementById('materialsContainer').style.display = 'none';
                return;
            }

            // Load materials from database
            fetch(`course-materials.php?ajax=load_materials&course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    document.getElementById('materialsTitle').textContent = 'Course Materials';
                    document.getElementById('materialsContainer').style.display = 'block';

                    const tbody = document.querySelector('#materialsTable tbody');
                    tbody.innerHTML = '';

                    data.materials.forEach(material => {
                        const uploadDate = new Date(material.upload_date).toLocaleDateString();
                        const fileSize = material.file_size ? `${(material.file_size / 1024 / 1024).toFixed(1)} MB` : '—';
                        const visibilityClass = material.visibility === 'public' ? 'status-active' : 'status-inactive';

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${material.file_name}</td>
                            <td>${material.file_type || 'FILE'}</td>
                            <td>${material.category}</td>
                            <td>${uploadDate}</td>
                            <td>${fileSize}</td>
                            <td>${material.download_count || 0}</td>
                            <td><span class="status-badge ${visibilityClass}">${material.visibility.charAt(0).toUpperCase() + material.visibility.slice(1)}</span></td>
                            <td>
                                <button class="btn-action" onclick="downloadMaterial(${material.id})">Download</button>
                                <button class="btn-action delete" onclick="deleteMaterial(${material.id})">Delete</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading materials:', error);
                    alert('Error loading course materials');
                });
        }

        // Button functions that redirect to other pages
        function uploadMaterial() {
            window.location.href = 'upload-material.php';
        }

        function downloadMaterial(materialId) {
            // Redirect to download endpoint
            window.location.href = `download-material.php?id=${materialId}`;
        }

        function deleteMaterial(materialId) {
            if (!confirm('Are you sure you want to delete this material?')) {
                return;
            }

            const courseId = document.getElementById('materialsCourse').value;

            fetch('course-materials.php?ajax=delete_material', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `material_id=${materialId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCourseMaterials(); // Reload the materials list
                } else {
                    alert('Error deleting material: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting material:', error);
                alert('Error deleting material');
            });
        }
    </script>
</body>
</html>
