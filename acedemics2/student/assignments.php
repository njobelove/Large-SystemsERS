<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

/*
Fatal error: Uncaught mysqli_sql_exception: Unknown column 'g.semester_id' in 'on clause' in C:\xamppmy\htdocs\acedemics2\functions.php:15 Stack trace: #0 C:\xamppmy\htdocs\acedemics2\functions.php(15): mysqli->prepare('SELECT c.code, ...') #1 C:\xamppmy\htdocs\acedemics2\functions.php(48): executeQuery('SELECT c.code, ...', Array, 'i') #2 C:\xamppmy\htdocs\acedemics2\student\view-grades.php(22): getMultipleRows('SELECT c.code, ...', Array, 'i') #3 {main} thrown in C:\xamppmy\htdocs\acedemics2\functions.php on line 15// Ensure user is student
if (!isStudent()) {
    header('Location: ../index.html');
    exit();
}
*/
$user = getCurrentUser();
$userId = getCurrentUserId();

// Handle admin or instructor viewing student's assignments
if ((isAdmin() || isInstructor()) && isset($_GET['student_id'])) {
    $userId = (int)$_GET['student_id'];
    $user = getUserById($userId);
}

// Get enrolled courses for filter dropdown
try {
    $coursesSql = "SELECT c.id, c.code, c.title
                   FROM courses c
                   JOIN enrollments e ON c.id = e.course_id
                   WHERE e.student_id = ? AND e.status = 'enrolled'
                   ORDER BY c.code ASC";
    $enrolledCourses = getMultipleRows($coursesSql, [$userId], 'i');
} catch (Exception $e) {
    $enrolledCourses = [];
}

// Get assignments for enrolled courses (without submissions table dependency)
try {
    $sql = "SELECT a.id, a.title, a.description, a.due_date, a.max_points, a.file_path,
                   c.code, c.title as course_title, c.id as course_id
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            JOIN enrollments e ON c.id = e.course_id
            WHERE e.student_id = ? AND e.status = 'enrolled'
            ORDER BY a.due_date ASC";
    $assignments = getMultipleRows($sql, [$userId], 'i');
} catch (Exception $e) {
    // Table doesn't exist or missing columns
    $assignments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - Student</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
    </style>
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
            <?php $studentParam = isset($_GET['student_id']) ? '?student_id=' . $_GET['student_id'] : ''; ?>
            <li><a href="dashboard.php<?php echo $studentParam; ?>">Dashboard</a></li>
            <li><a href="register-courses.php<?php echo $studentParam; ?>">Register Courses</a></li>
            <li><a href="view-courses.php<?php echo $studentParam; ?>">View Courses</a></li>
            <li><a href="course-details.php<?php echo $studentParam; ?>">Course Details</a></li>
            <li><a href="view-attendance.php<?php echo $studentParam; ?>">View Attendance</a></li>
            <li><a href="assignments.php<?php echo $studentParam; ?>" class="active">Assignments</a></li>
            <li><a href="schedule.php<?php echo $studentParam; ?>">Schedule</a></li>
            <li><a href="transcript.php<?php echo $studentParam; ?>">Transcript</a></li>
            <li><a href="view-grades.php<?php echo $studentParam; ?>">View Grades</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>My Assignments</h2>
        <p>View and manage your course assignments.</p>

        <?php if (!empty($enrolledCourses)): ?>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="courseFilter">Filter by Course:</label>
                <select id="courseFilter" onchange="filterAssignments()">
                    <option value="all">All Courses</option>
                    <?php foreach ($enrolledCourses as $course): ?>
                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if (empty($assignments)): ?>
            <div class="card">
                <p>No assignments available.</p>
            </div>
        <?php else: ?>
            <div class="card-container" id="assignmentsContainer">
                <?php foreach ($assignments as $assignment): ?>
                <?php
                // Check if student has already submitted this assignment
                $submission = getSingleRow("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?", [$assignment['id'], $userId], 'ii');
                $hasSubmitted = !empty($submission);
                $isOverdue = strtotime($assignment['due_date']) < time();
                ?>
                <div class="card" data-course="<?php echo $assignment['course_id']; ?>">
                    <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($assignment['code']); ?> - <?php echo htmlspecialchars($assignment['course_title']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($assignment['description']); ?></p>
                    <p><strong>Due Date:</strong> <?php echo formatDateTime($assignment['due_date']); ?></p>
                    <p><strong>Max Points:</strong> <?php echo htmlspecialchars($assignment['max_points']); ?></p>
                    <p><strong>Status:</strong>
                        <?php if ($hasSubmitted): ?>
                            <span style="color: green;">✓ Submitted on <?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></span>
                        <?php elseif ($isOverdue): ?>
                            <span style="color: red;">Overdue</span>
                        <?php else: ?>
                            <span style="color: orange;">Not Submitted</span>
                        <?php endif; ?>
                    </p>

                    <div style="margin-top: 15px;">
                        <?php if (!empty($assignment['file_path'])): ?>
                            <a href="download-assignment.php?id=<?php echo $assignment['id']; ?>" class="btn" style="margin-right: 10px;">Download Assignment</a>
                        <?php endif; ?>

                        <?php if (!$hasSubmitted && !$isOverdue): ?>
                            <button onclick="showUploadModal(<?php echo $assignment['id']; ?>, '<?php echo htmlspecialchars($assignment['title']); ?>')" class="btn">Submit Assignment</button>
                        <?php elseif ($hasSubmitted): ?>
                            <button onclick="viewSubmission(<?php echo $assignment['id']; ?>)" class="btn" style="background-color: #28a745;">View Submission</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Upload Modal -->
        <div id="uploadModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="closeUploadModal()">&times;</span>
                <h3 id="modalTitle">Submit Assignment</h3>
                <form id="assignmentForm" enctype="multipart/form-data">
                    <input type="hidden" id="assignmentId" name="assignment_id">
                    <div class="form-group">
                        <label for="assignmentFile">Select File:</label>
                        <input type="file" id="assignmentFile" name="assignment_file" accept=".pdf,.doc,.docx,.txt,.zip" required>
                        <small>Allowed formats: PDF, DOC, DOCX, ZIP (Max: 10MB)</small>
                    </div>
                    <div class="form-group">
                        <label for="submissionNotes">Notes (Optional):</label>
                        <textarea id="submissionNotes" name="notes" rows="3" placeholder="Add any notes for your submission..."></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Assignment</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function showUploadModal(assignmentId, title) {
            document.getElementById('assignmentId').value = assignmentId;
            document.getElementById('modalTitle').textContent = 'Submit: ' + title;
            document.getElementById('uploadModal').style.display = 'block';
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
            document.getElementById('assignmentForm').reset();
        }

        function viewSubmission(assignmentId) {
            // Redirect to view submission page (to be implemented)
            alert('View submission feature coming soon');
        }

        // Handle form submission
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('submit-assignment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Assignment submitted successfully!');
                    closeUploadModal();
                    location.reload(); // Refresh to show updated status
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the assignment.');
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target == modal) {
                closeUploadModal();
            }
        }

        function filterAssignments() {
            const courseFilter = document.getElementById('courseFilter').value;
            const assignmentCards = document.querySelectorAll('#assignmentsContainer .card');

            assignmentCards.forEach(card => {
                const courseId = card.getAttribute('data-course');

                if (courseFilter === 'all' || courseId === courseFilter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
