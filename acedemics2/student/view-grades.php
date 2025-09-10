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

// Handle admin or instructor viewing student's grades
if ((isAdmin() || isInstructor()) && isset($_GET['student_id'])) {
    $userId = (int)$_GET['student_id'];
    // Fetch full user data including name for the student being viewed
    $user = getUserById($userId);
    if (!$user || empty($user['name'])) {
        // Fallback to current user if student not found
        $user = getCurrentUser();
    }
} else {
    // For regular students, ensure $user is current user
    $user = getCurrentUser();
}

// Get grades for all courses
$sql = "SELECT c.code, c.title, g.grade, g.grade_letter
        FROM grades g
        JOIN courses c ON g.course_id = c.id
        WHERE g.student_id = ?
        ORDER BY c.title";
$grades = getMultipleRows($sql, [$userId], 'i');

// Get assignment grades
$assignmentGrades = getMultipleRows("
    SELECT s.grade, s.feedback, a.title as assignment_title, a.max_points,
           c.code as course_code, c.title as course_title,
           s.submitted_at, s.graded
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.student_id = ? AND s.grade IS NOT NULL
    ORDER BY s.submitted_at DESC
", [$userId], 'i');

// Calculate GPA
$gpaData = calculateGPA($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Grades - Student</title>
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
                <img src="../images/user-avatar.png" alt="User" />
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
            <li><a href="view-attendance.php<?php echo $studentParam; ?>">View Attendance</a></li>
            <li><a href="assignments.php<?php echo $studentParam; ?>">Assignments</a></li>
            <li><a href="schedule.php<?php echo $studentParam; ?>">Schedule</a></li>
            <li><a href="transcript.php<?php echo $studentParam; ?>">Transcript</a></li>
            <li><a href="view-grades.php<?php echo $studentParam; ?>" class="active">View Grades</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="card" style="margin-bottom: 20px;">
            <h2>Student Name: <?php echo htmlspecialchars($user['name'] ?? 'Student'); ?></h2>
        </div>
        <h2>My Grades</h2>
        <p>View your academic performance across all courses.</p>

        <div class="card">
            <h3>Overall GPA</h3>
            <p><strong>GPA:</strong> <?php echo number_format($gpaData['gpa'], 2); ?></p>
            <p><strong>Total Courses:</strong> <?php echo $gpaData['total_courses']; ?></p>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Assignment Grades</h3>
            <?php if (empty($assignmentGrades)): ?>
                <p>No assignment grades available yet.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Course</th>
                                <th>Grade</th>
                                <th>Max Points</th>
                                <th>Percentage</th>
                                <th>Feedback</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignmentGrades as $grade): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grade['assignment_title']); ?></td>
                                <td><?php echo htmlspecialchars($grade['course_code']); ?> - <?php echo htmlspecialchars($grade['course_title']); ?></td>
                                <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                                <td><?php echo htmlspecialchars($grade['max_points']); ?></td>
                                <td><?php echo number_format(($grade['grade'] / $grade['max_points']) * 100, 1); ?>%</td>
                                <td><?php echo htmlspecialchars($grade['feedback'] ?? 'No feedback'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($grade['submitted_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Course Grades</h3>
            <?php if (empty($grades)): ?>
                <p>No grades available yet.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th>Grade</th>
                                <th>Letter Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $grade): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($grade['code']); ?></td>
                                <td><?php echo htmlspecialchars($grade['title']); ?></td>
                                <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                                <td><?php echo htmlspecialchars($grade['grade_letter'] ?? 'N/A'); ?></td>
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
