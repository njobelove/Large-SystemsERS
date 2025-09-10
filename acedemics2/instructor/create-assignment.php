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
$instructorId = $_SESSION['user_id'];

// Get instructor's courses
$courses = getMultipleRows("
    SELECT c.* FROM courses c
    WHERE c.instructor_id = ?
    ORDER BY c.code ASC
", [$instructorId], 'i');

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    $action = $_GET['ajax'];

    if ($action === 'create_assignment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Debug logging
        error_log("DEBUG: Starting assignment creation");
        error_log("DEBUG: POST data: " . print_r($_POST, true));
        error_log("DEBUG: FILES data: " . print_r($_FILES, true));

        $courseId = (int)$_POST['course_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $dueDate = $_POST['due_date'];
        $maxPoints = (int)$_POST['max_points'];

        error_log("DEBUG: Parsed data - courseId: $courseId, title: $title, maxPoints: $maxPoints");

        // Verify course belongs to instructor
        $course = getSingleRow("SELECT id FROM courses WHERE id = ? AND instructor_id = ?", [$courseId, $instructorId], 'ii');
        error_log("DEBUG: Course verification result: " . print_r($course, true));

        if (!$course) {
            error_log("DEBUG: Course verification failed - unauthorized access");
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        // Validate required fields
        if (empty($title) || empty($description) || empty($dueDate) || $maxPoints <= 0) {
            error_log("DEBUG: Validation failed - missing required fields");
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        // Handle file upload
        $filePath = null;
        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['assignment_file'];
            error_log("DEBUG: File upload detected - name: " . $file['name'] . ", size: " . $file['size'] . ", type: " . $file['type']);

            // Validate file size (10MB max)
            if ($file['size'] > 10 * 1024 * 1024) {
                error_log("DEBUG: File size validation failed - size: " . $file['size']);
                echo json_encode(['error' => 'File size exceeds 10MB limit']);
                exit();
            }

            // Validate file type
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain'];
            if (!in_array($file['type'], $allowedTypes)) {
                error_log("DEBUG: File type validation failed - type: " . $file['type']);
                echo json_encode(['error' => 'Invalid file type. Allowed: PDF, DOC, DOCX, PPT, PPTX, TXT']);
                exit();
            }

            // Generate unique filename
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $uniqueFilename = uniqid('assignment_' . $courseId . '_', true) . '.' . $fileExtension;
            $uploadDir = '../uploads/assignments/';
            $filePath = $uploadDir . $uniqueFilename;

            error_log("DEBUG: Upload directory: $uploadDir, File path: $filePath");

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                error_log("DEBUG: Creating upload directory: $uploadDir");
                if (!mkdir($uploadDir, 0755, true)) {
                    error_log("DEBUG: Failed to create upload directory");
                    echo json_encode(['error' => 'Failed to create upload directory']);
                    exit();
                }
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                error_log("DEBUG: Failed to move uploaded file from " . $file['tmp_name'] . " to " . $filePath);
                echo json_encode(['error' => 'Failed to upload file']);
                exit();
            }
            error_log("DEBUG: File uploaded successfully to: $filePath");
        } else {
            error_log("DEBUG: No file upload or upload error - FILES data: " . print_r($_FILES, true));
        }

        // Insert assignment into database
        error_log("DEBUG: Attempting database insertion - courseId: $courseId, filePath: $filePath");

        try {
            $result = executeNonQuery("
                INSERT INTO assignments (
                    course_id, title, description, due_date, max_points, file_path, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ", [
                $courseId, $title, $description, $dueDate, $maxPoints, $filePath
            ], 'isssis');
            error_log("DEBUG: First database insertion attempt result: " . ($result ? 'success' : 'failed'));
        } catch (Exception $e) {
            error_log("DEBUG: First insertion failed with exception: " . $e->getMessage());
            // If file_path column doesn't exist, try without it
            try {
                $result = executeNonQuery("
                    INSERT INTO assignments (
                        course_id, title, description, due_date, max_points, created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ", [
                    $courseId, $title, $description, $dueDate, $maxPoints
                ], 'isssi');
                error_log("DEBUG: Second database insertion attempt result: " . ($result ? 'success' : 'failed'));
            } catch (Exception $e2) {
                error_log("DEBUG: Second insertion also failed with exception: " . $e2->getMessage());
                $result = false;
            }
        }

        if ($result) {
            // Get last inserted ID
            $conn = getDBConnection();
            $lastId = $conn->insert_id;
            error_log("DEBUG: Assignment created successfully with ID: $lastId");
            echo json_encode(['success' => true, 'assignment_id' => $lastId]);
        } else {
            error_log("DEBUG: Database insertion failed completely");
            echo json_encode(['error' => 'Failed to create assignment']);
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
    <title>Create Assignment</title>
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
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="courses.php">Courses</a></li>
            <li><a href="attendance.php">Attendance</a></li>
            <li><a href="gradebook.php">Gradebook</a></li>
            <li><a href="assignments.php" class="active">Assignments</a></li>
            <li><a href="course-materials.php">Materials</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="schedule.php">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Create New Assignment</h2>
            <button class="btn btn-secondary" onclick="goBack()">Back to Assignments</button>
        </div>

        <div class="card">
            <h3>Assignment Details</h3>
            <form id="createAssignmentForm" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="assignmentCourse">Course</label>
                        <select id="assignmentCourse" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group-half">
                        <label for="assignmentType">Assignment Type</label>
                        <select id="assignmentType" required>
                            <option value="">Select Type</option>
                            <option value="homework">Homework</option>
                            <option value="quiz">Quiz</option>
                            <option value="project">Project</option>
                            <option value="exam">Exam</option>
                            <option value="lab">Lab</option>
                            <option value="presentation">Presentation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="assignmentTitle">Assignment Title</label>
                    <input type="text" id="assignmentTitle" required placeholder="e.g., Programming Assignment 1">
                </div>

                <div class="form-group">
                    <label for="assignmentDescription">Description</label>
                    <textarea id="assignmentDescription" rows="4" required
                              placeholder="Provide detailed instructions for the assignment..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group-half">
                        <label for="assignmentDueDate">Due Date</label>
                        <input type="datetime-local" id="assignmentDueDate" required>
                    </div>
                    <div class="form-group-half">
                        <label for="assignmentPoints">Total Points</label>
                        <input type="number" id="assignmentPoints" required min="1" max="1000" placeholder="100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-half">
                        <label for="assignmentWeight">Weight (%)</label>
                        <input type="number" id="assignmentWeight" min="0" max="100" placeholder="10">
                    </div>
                    <div class="form-group-half">
                        <label for="lateSubmissions">Allow Late Submissions</label>
                        <select id="lateSubmissions">
                            <option value="no">No</option>
                            <option value="yes">Yes</option>
                            <option value="penalty">With Penalty</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="allowedFileTypes">Allowed File Types</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <label><input type="checkbox" value="pdf" checked> PDF</label>
                        <label><input type="checkbox" value="doc" checked> DOC</label>
                        <label><input type="checkbox" value="docx" checked> DOCX</label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-half">
                        <label for="fileSizeLimit">Max File Size (MB)</label>
                        <input type="number" id="fileSizeLimit" min="1" max="100" value="10">
                    </div>
                    <div class="form-group-half">
                        <label for="notifications">Notifications</label>
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <label><input type="checkbox" id="notifyStudents" checked> Notify students when assignment is posted</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="assignmentFile">Upload Assignment File (Optional)</label>
                    <input type="file" id="assignmentFile" name="assignment_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" />
                    <small style="color: #666;">Accepted formats: PDF, DOC, DOCX, PPT, PPTX, TXT</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Create Assignment</button>
                </div>
            </form>
        </div>
    </main>

    <script src="../js/script.js"></script>
    <script src="../js/instructor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set default due date to one week from now
            const oneWeekFromNow = new Date();
            oneWeekFromNow.setDate(oneWeekFromNow.getDate() + 7);
            oneWeekFromNow.setHours(23, 59); // Set to 11:59 PM

            const formattedDate = oneWeekFromNow.toISOString().slice(0, 16);
            document.getElementById('assignmentDueDate').value = formattedDate;
        });

        document.getElementById('createAssignmentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('course_id', document.getElementById('assignmentCourse').value);
            formData.append('title', document.getElementById('assignmentTitle').value);
            formData.append('description', document.getElementById('assignmentDescription').value);
            formData.append('due_date', document.getElementById('assignmentDueDate').value);
            formData.append('max_points', document.getElementById('assignmentPoints').value);

            // Validate required fields
            if (!formData.get('course_id') || !formData.get('title') || !formData.get('description') ||
                !formData.get('due_date') || !formData.get('max_points')) {
                alert('Please fill in all required fields');
                return;
            }

            // Add file if selected
            const fileInput = document.getElementById('assignmentFile');
            if (fileInput.files.length > 0) {
                formData.append('assignment_file', fileInput.files[0]);
            }

            // Submit assignment to database
            fetch('create-assignment.php?ajax=create_assignment', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Assignment "${formData.get('title')}" created successfully!`);
                    // Redirect to assignments page with success message
                    window.location.href = 'assignments.php?created=1&title=' + encodeURIComponent(formData.get('title'));
                } else {
                    alert('Error creating assignment: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error creating assignment:', error);
                alert('Error creating assignment: ' + error.message);
            });
        });

        function goBack() {
            window.location.href = 'assignments.php';
        }
    </script>
</body>
</html>
