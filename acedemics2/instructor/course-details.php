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

// Get course ID from URL parameter
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Get course details
$course = getSingleRow("
    SELECT c.* FROM courses c
    WHERE c.id = ? AND c.instructor_id = ?
", [$courseId, $instructorId], 'ii');

if (!$course) {
    header('Location: dashboard.php');
    exit();
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    $action = $_GET['ajax'];

    if ($action === 'load_course_details') {
        // Get enrolled students
        $students = getMultipleRows("
            SELECT
                u.id,
                u.name,
                u.email,
                e.enrollment_date,
                e.status
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            WHERE e.course_id = ? AND u.role = 'student'
            ORDER BY u.name ASC
        ", [$courseId], 'i');

        // Get course statistics
        $stats = getSingleRow("
            SELECT
                COUNT(DISTINCT e.student_id) as total_enrolled,
                AVG(g.grade) as average_grade,
                COUNT(DISTINCT a.id) as total_assignments,
                COUNT(DISTINCT CASE WHEN s.id IS NOT NULL THEN s.id END) as submitted_assignments
            FROM enrollments e
            LEFT JOIN grades g ON e.id = g.enrollment_id
            LEFT JOIN assignments a ON a.course_id = e.course_id
            LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = e.student_id
            WHERE e.course_id = ?
        ", [$courseId], 'i');

        // Get attendance rate
        $attendanceStats = getSingleRow("
            SELECT
                AVG(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) * 100 as attendance_rate
            FROM attendance a
            WHERE a.course_id = ?
        ", [$courseId], 'i');

        // Get recent activity (last 10 activities)
        $recentActivity = getMultipleRows("
            (SELECT
                'assignment' as type,
                s.submitted_at as date,
                CONCAT(u.name, ' submitted assignment: ', a.title) as activity
            FROM submissions s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN users u ON s.student_id = u.id
            WHERE a.course_id = ?
            ORDER BY s.submitted_at DESC
            LIMIT 5)
            UNION ALL
            (SELECT
                'material' as type,
                cm.created_at as date,
                CONCAT('New material uploaded: ', cm.title) as activity
            FROM course_materials cm
            WHERE cm.course_id = ?
            ORDER BY cm.created_at DESC
            LIMIT 3)
            UNION ALL
            (SELECT
                'attendance' as type,
                att.date as date,
                CONCAT('Attendance recorded for ', att.date) as activity
            FROM attendance att
            WHERE att.course_id = ?
            GROUP BY att.date
            ORDER BY att.date DESC
            LIMIT 2)
            ORDER BY date DESC
            LIMIT 10
        ", [$courseId, $courseId, $courseId], 'iii');

        echo json_encode([
            'course' => $course,
            'students' => $students,
            'stats' => [
                'total_enrolled' => (int)($stats['total_enrolled'] ?? 0),
                'average_grade' => round($stats['average_grade'] ?? 0, 1),
                'attendance_rate' => round($attendanceStats['attendance_rate'] ?? 0, 1),
                'assignments_submitted' => (int)($stats['submitted_assignments'] ?? 0)
            ],
            'recent_activity' => $recentActivity
        ]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details</title>
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
            <li><a href="assignments.php">Assignments</a></li>
            <li><a href="course-materials.php">Materials</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="schedule.php">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 id="courseTitle"><?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?></h2>
            <button class="btn btn-secondary" onclick="goBack()">Back to Dashboard</button>
        </div>

        <div class="card">
            <h3>Course Information</h3>
            <div class="course-info-grid">
                <div class="info-item">
                    <label>Course Code:</label>
                    <span id="courseCode"><?php echo htmlspecialchars($course['code']); ?></span>
                </div>
                <div class="info-item">
                    <label>Course Title:</label>
                    <span id="courseTitleDetail"><?php echo htmlspecialchars($course['title']); ?></span>
                </div>
                <div class="info-item">
                    <label>Credits:</label>
                    <span id="courseCredits"><?php echo htmlspecialchars($course['credits']); ?></span>
                </div>

                <div class="info-item">
                    <label>Capacity:</label>
                    <span id="courseCapacity"><?php echo htmlspecialchars($course['max_students'] ?? '30'); ?>/<?php echo htmlspecialchars($course['max_students'] ?? '30'); ?></span>
                </div>
            </div>

            <div class="course-description">
                <label>Course Description:</label>
                <p id="courseDescription"><?php echo htmlspecialchars($course['description'] ?? 'No description available.'); ?></p>
            </div>


        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Enrolled Students</h3>
            <div class="table-container">
                <table id="enrolledStudentsTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Course Statistics</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value" id="totalEnrolled">0</div>
                    <div class="stat-label">Total Enrolled</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="averageGrade">0.0</div>
                    <div class="stat-label">Average Grade</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="attendanceRate">0%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="assignmentsSubmitted">0</div>
                    <div class="stat-label">Assignments Submitted</div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Recent Activity</h3>
            <div class="activity-list" id="recentActivity">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>
    <script src="../js/instructor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadCourseDetails();
        });

        function loadCourseDetails() {
            // Load course details from database
            fetch(`course-details.php?ajax=load_course_details&course_id=<?php echo $courseId; ?>`)
                .then(response => response.json())
                .then(data => {
                    // Update students table
                    const tbody = document.querySelector('#enrolledStudentsTable tbody');
                    tbody.innerHTML = '';

                    data.students.forEach(student => {
                        const enrollmentDate = new Date(student.enrollment_date).toLocaleDateString();
                        const statusClass = student.status === 'active' ? 'status-active' : 'status-inactive';

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>S${String(student.id).padStart(4, '0')}</td>
                            <td>${student.name}</td>
                            <td>${student.email}</td>
                            <td>${enrollmentDate}</td>
                            <td><span class="status-badge ${statusClass}">${student.status.charAt(0).toUpperCase() + student.status.slice(1)}</span></td>
                            <td>
                                <button class="btn-action" onclick="viewStudentProfile(${student.id})">Profile</button>
                                <button class="btn-action" onclick="sendMessage(${student.id})">Message</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Update statistics
                    document.getElementById('totalEnrolled').textContent = data.stats.total_enrolled;
                    document.getElementById('averageGrade').textContent = data.stats.average_grade;
                    document.getElementById('attendanceRate').textContent = data.stats.attendance_rate + '%';
                    document.getElementById('assignmentsSubmitted').textContent = data.stats.assignments_submitted;

                    // Update recent activity
                    const activityList = document.getElementById('recentActivity');
                    activityList.innerHTML = '';

                    if (data.recent_activity.length === 0) {
                        activityList.innerHTML = '<div class="activity-item">No recent activity</div>';
                    } else {
                        data.recent_activity.forEach(activity => {
                            const activityDate = new Date(activity.date).toLocaleDateString();
                            const activityDiv = document.createElement('div');
                            activityDiv.className = 'activity-item';
                            activityDiv.innerHTML = `
                                <span class="activity-date">${activityDate}</span>
                                <span class="activity-text">${activity.activity}</span>
                            `;
                            activityList.appendChild(activityDiv);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading course details:', error);
                    alert('Error loading course details');
                });
        }

        function goBack() {
            window.location.href = 'dashboard.php';
        }

        function viewStudentProfile(studentId) {
            window.location.href = `student-profile.php?student=${studentId}`;
        }

        function sendMessage(studentId) {
            window.location.href = `send-message.php?student=${studentId}`;
        }
    </script>
</body>
</html>
