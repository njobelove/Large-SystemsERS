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

// Handle admin or instructor viewing student's attendance
if ((isAdmin() || isInstructor()) && isset($_GET['student_id'])) {
    $userId = (int)$_GET['student_id'];
    $user = getUserById($userId);
}

// Get enrolled courses for filter dropdown
$enrolledCourses = getStudentCourses($userId);

// Get selected course filter
$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Get attendance records (with optional course filter)
try {
    $sql = "SELECT c.code, c.title, a.date, a.status, a.notes
            FROM attendance a
            JOIN courses c ON a.course_id = c.id
            WHERE a.student_id = ?";
    $params = [$userId];
    $types = 'i';

    if ($selectedCourseId > 0) {
        $sql .= " AND a.course_id = ?";
        $params[] = $selectedCourseId;
        $types .= 'i';
    }

    $sql .= " ORDER BY a.date DESC";
    $attendanceRecords = getMultipleRows($sql, $params, $types);
} catch (Exception $e) {
    // Table doesn't exist or missing columns
    $attendanceRecords = [];
}

// Calculate attendance summary
$attendanceSummary = [];
foreach ($attendanceRecords as $record) {
    $courseCode = $record['code'];
    if (!isset($attendanceSummary[$courseCode])) {
        $attendanceSummary[$courseCode] = [
            'title' => $record['title'],
            'total' => 0,
            'present' => 0
        ];
    }
    $attendanceSummary[$courseCode]['total']++;
    if ($record['status'] === 'present') {
        $attendanceSummary[$courseCode]['present']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - Student</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">Academic System</div>
            <ul class="nav-links">
                <li><a href="../index.php">Logout</a></li>
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
            <li><a href="course-details.php<?php echo $studentParam; ?>">Course Details</a></li>
            <li><a href="view-attendance.php<?php echo $studentParam; ?>" class="active">View Attendance</a></li>
            <li><a href="assignments.php<?php echo $studentParam; ?>">Assignments</a></li>
            <li><a href="schedule.php<?php echo $studentParam; ?>">Schedule</a></li>
            <li><a href="transcript.php<?php echo $studentParam; ?>">Transcript</a></li>
            <li><a href="view-grades.php<?php echo $studentParam; ?>">View Grades</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>My Attendance</h2>
        <p>View your attendance records across all courses.</p>

        <!-- Course Filter -->
        <div class="card" style="margin-bottom: 20px;">
            <h3>Filter by Course</h3>
            <form method="GET" action="view-attendance.php" style="display: flex; gap: 10px; align-items: center;">
                <label for="course_id">Select Course:</label>
                <select name="course_id" id="course_id" onchange="this.form.submit()">
                    <option value="0">All Courses</option>
                    <?php foreach ($enrolledCourses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo ($selectedCourseId == $course['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="card" style="margin-bottom: 30px;">
            <h3>Attendance Summary</h3>
            <?php if (empty($attendanceSummary)): ?>
                <p>No attendance records available.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th>Present</th>
                                <th>Total Classes</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceSummary as $code => $summary): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($code); ?></td>
                                <td><?php echo htmlspecialchars($summary['title']); ?></td>
                                <td><?php echo $summary['present']; ?></td>
                                <td><?php echo $summary['total']; ?></td>
                                <td><?php echo $summary['total'] > 0 ? round(($summary['present'] / $summary['total']) * 100, 1) . '%' : 'N/A'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Detailed Attendance Records</h3>
            <?php if (empty($attendanceRecords)): ?>
                <p>No attendance records available.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): ?>
                            <tr>
                                <td><?php echo formatDate($record['date']); ?></td>
                                <td><?php echo htmlspecialchars($record['code']); ?> - <?php echo htmlspecialchars($record['title']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($record['status'])); ?></td>
                                <td><?php echo htmlspecialchars($record['notes'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
