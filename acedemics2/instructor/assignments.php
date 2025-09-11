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

    if ($action === 'load_assignments') {
        $assignments = getMultipleRows("
            SELECT a.*, c.code as course_code, c.title as course_title
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            WHERE c.instructor_id = ?
            ORDER BY a.due_date ASC
        ", [$instructorId], 'i');

        // For each assignment, get submission and grading stats
        foreach ($assignments as &$assignment) {
            $assignmentId = $assignment['id'];

            $submissionCount = getSingleRow("
                SELECT COUNT(*) as count FROM submissions WHERE assignment_id = ?
            ", [$assignmentId], 'i');

            $gradedCount = getSingleRow("
                SELECT COUNT(*) as count FROM submissions WHERE assignment_id = ? AND graded = 1
            ", [$assignmentId], 'i');

            $averageGrade = getSingleRow("
                SELECT AVG(grade) as avg_grade FROM submissions WHERE assignment_id = ? AND graded = 1
            ", [$assignmentId], 'i');

            $assignment['submissions'] = $submissionCount['count'] ?? 0;
            $assignment['graded'] = $gradedCount['count'] ?? 0;
            $assignment['average'] = $averageGrade['avg_grade'] !== null ? round($averageGrade['avg_grade'], 2) : null;
        }

        echo json_encode(['assignments' => $assignments]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Assignments</title>
    <link rel="stylesheet" href="../css/style.css" />
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
            <li><a href="assignments.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>" class="active">Assignments</a></li>
            <li><a href="analytics.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Analytics</a></li>
            <li><a href="schedule.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Assignments</h2>
            <button class="btn" onclick="window.location.href='create-assignment.php'">Create New Assignment</button>
        </div>

        <?php if (!empty($courses)): ?>
            <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #c3e6cb;">
                <strong>✓</strong> You have <?php echo count($courses); ?> course(s) available for assignment management.
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="assignmentCourseFilter">Filter by Course:</label>
            <select id="assignmentCourseFilter" onchange="filterAssignments()">
                <option value="all">All Courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo htmlspecialchars($course['code']); ?>">
                        <?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="card">
            <div class="table-container">
                <table id="assignmentsTable">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Assignment</th>
                            <th>Due Date</th>
                            <th>Submissions</th>
                            <th>Graded</th>
                            <th>Average</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="../js/instructor.js"></script>
    <script>
        let assignments = [];

        document.addEventListener('DOMContentLoaded', function() {
            // Check for success message in URL params
            const urlParams = new URLSearchParams(window.location.search);
            const created = urlParams.get('created');
            const title = urlParams.get('title');

            if (created && title) {
                alert(`Assignment "${decodeURIComponent(title)}" created successfully!`);
                // Remove query params from URL without reloading
                if (window.history.replaceState) {
                    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({}, document.title, cleanUrl);
                }
            }

            loadAssignments();
        });

        function loadAssignments() {
            fetch('assignments.php?ajax=load_assignments')
                .then(response => response.json())
                .then(data => {
                    assignments = data.assignments || [];
                    populateAssignmentsTable(assignments);
                })
                .catch(error => {
                    console.error('Error loading assignments:', error);
                });
        }

        function populateAssignmentsTable(assignments) {
            const tbody = document.querySelector('#assignmentsTable tbody');
            tbody.innerHTML = '';

            assignments.forEach(assignment => {
                const row = document.createElement('tr');
                row.dataset.course = assignment.course_code;

                row.innerHTML = `
                    <td>${assignment.course_code} - ${assignment.course_title}</td>
                    <td>${assignment.title}</td>
                    <td>${formatDate(assignment.due_date)}</td>
                    <td>${assignment.submissions}</td>
                    <td>${assignment.graded}</td>
                    <td>${assignment.average !== null ? assignment.average : '—'}</td>
                    <td>
                        <button class="btn-action" onclick="gradeAssignment(${assignment.id})">Grade</button>
                        <button class="btn-action" onclick="viewSubmissions(${assignment.id})">View</button>
                    </td>
                `;

                tbody.appendChild(row);
            });
        }

        function filterAssignments() {
            const courseFilter = document.getElementById('assignmentCourseFilter').value;
            const rows = document.querySelectorAll('#assignmentsTable tbody tr');

            rows.forEach(row => {
                if (courseFilter === 'all' || row.dataset.course === courseFilter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        function gradeAssignment(assignmentId) {
            window.location.href = `gradebook.php?assignment_id=${assignmentId}`;
        }

        function viewSubmissions(assignmentId) {
            window.location.href = `assignment-submissions.php?assignment_id=${assignmentId}`;
        }
    </script>
</body>
</html>
