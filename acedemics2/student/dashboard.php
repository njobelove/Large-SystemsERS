<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

/*
if (!isStudent()) {
    header('Location: ../index.php');
    exit();
}
*/
$currentUserId = getCurrentUserId();

// Check if admin is viewing a specific student's dashboard
$viewingStudentId = null;
$viewingStudent = null;
$isAdminView = false;

if (isAdmin() && isset($_GET['student_id'])) {
    $viewingStudentId = (int)$_GET['student_id'];
    // Get complete student data with joined information for admin view
    $viewingStudent = getMultipleRows("
        SELECT
            u.id, u.name, u.email, u.role, u.program_id, u.level_id, u.phone,
            u.date_of_birth, u.place_of_birth, u.last_school_attended,
            p.name as program_name, s.name as semester_name, l.name as level_name
        FROM users u
        LEFT JOIN programs p ON u.program_id = p.id
        LEFT JOIN semesters s ON u.semester_id = s.id
        LEFT JOIN levels l ON u.level_id = l.id
        WHERE u.id = ? AND u.role = 'student'
    ", [$viewingStudentId], 'i');

    if (!empty($viewingStudent)) {
        $isAdminView = true;
        $userId = $viewingStudentId;
        $user = $viewingStudent[0];
    } else {
        // Invalid student ID, redirect to admin dashboard
        header('Location: ../admin/dashboard.php');
        exit();
    }
} else {
    $userId = $currentUserId;
    // Get full user data from database for regular students
    $user = getUserById($userId);
if (!$user) {
    // User not found in database, redirect to registration
    if ($isAdminView) {
        // Admin viewing invalid student, redirect to admin dashboard
        header('Location: ../admin/dashboard.php');
        exit();
    } else {
        header('Location: register.php');
        exit();
    }
}
}

// Redirect to registration if student hasn't completed registration (only for actual students)
if (!$isAdminView && (empty($user['program_id']) || empty($user['level_id']))) {
    // Prevent redirect to register.php to fix dashboard redirect issue
    // header('Location: register.php');
    // exit();
}
// Note: If admin is viewing ($isAdminView = true), allow viewing even if registration incomplete

// Fetch student overview data
$courses = getStudentCourses($userId);
$gpaData = calculateGPA($userId);
$attendancePercent = getAttendancePercentage($userId);
$upcomingDeadlines = getUpcomingDeadlines($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        .profile-info { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .info-row { padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-row strong { display: inline-block; width: 180px; color: #333; }
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
                <img src="../images/user-avatar.png" alt="User" />
                <span><?php echo htmlspecialchars($user['name'] ?? 'Student'); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <?php $studentParam = isset($_GET['student_id']) ? '?student_id=' . $_GET['student_id'] : ''; ?>
            <li><a href="dashboard.php<?php echo $studentParam; ?>" class="active">Dashboard</a></li>
            <li><a href="register-courses.php<?php echo $studentParam; ?>">Register Courses</a></li>
            <li><a href="view-courses.php<?php echo $studentParam; ?>">View Courses</a></li>
            <li><a href="course-details.php<?php echo $studentParam; ?>">Course Details</a></li>
            <li><a href="view-attendance.php<?php echo $studentParam; ?>">View Attendance</a></li>
            <li><a href="assignments.php<?php echo $studentParam; ?>">Assignments</a></li>
            <li><a href="schedule.php<?php echo $studentParam; ?>">Schedule</a></li>
            <li><a href="transcript.php<?php echo $studentParam; ?>">Transcript</a></li>
            <li><a href="view-grades.php<?php echo $studentParam; ?>">View Grades</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Student Dashboard</h2>
        <p>Welcome to your academic portal. Stay updated with your academic progress.</p>

        <?php if ($isAdminView): ?>
        <div class="card" style="margin-bottom: 30px;">
            <h3>Student Profile Information</h3>
            <div class="profile-info">
                <div class="info-row">
                    <strong>Full Name:</strong> <?php echo htmlspecialchars($user['name']); ?>
                </div>
                <div class="info-row">
                    <strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?>
                </div>
                <div class="info-row">
                    <strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                </div>
                <div class="info-row">
                    <strong>Date of Birth:</strong> <?php echo $user['date_of_birth'] ? date('M d, Y', strtotime($user['date_of_birth'])) : 'N/A'; ?>
                </div>
                <div class="info-row">
                    <strong>Place of Birth:</strong> <?php echo htmlspecialchars($user['place_of_birth'] ?? 'N/A'); ?>
                </div>
                <div class="info-row">
                    <strong>Last School Attended:</strong> <?php echo htmlspecialchars($user['last_school_attended'] ?? 'N/A'); ?>
                </div>
                <div class="info-row">
                    <strong>Program:</strong> <?php echo htmlspecialchars($user['program_name'] ?? 'N/A'); ?>
                </div>
                <div class="info-row">
                    <strong>Semester:</strong> <?php echo htmlspecialchars($user['semester_name'] ?? 'N/A'); ?>
                </div>
                <div class="info-row">
                    <strong>Level:</strong> <?php echo htmlspecialchars($user['level_name'] ?? 'N/A'); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card-container">
            <div class="card">
                <h3>Current Courses</h3>
                <p id="currentCourses"><?php echo count($courses); ?></p>
            </div>
            <div class="card">
                <h3>Cumulative GPA</h3>
                <p id="currentGPA"><?php echo number_format($gpaData['gpa'], 2); ?></p>
            </div>
            <div class="card">
                <h3>Attendance</h3>
                <p id="attendanceRate"><?php echo $attendancePercent; ?>%</p>
            </div>
            <div class="card">
                <h3>Upcoming Deadlines</h3>
                <p id="upcomingDeadlines"><?php echo count($upcomingDeadlines); ?></p>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Your Current Courses</h3>
            <div class="table-container">
                <table id="studentCoursesTable">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Title</th>
                            <th>Instructor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['code']); ?></td>
                            <td><?php echo htmlspecialchars($course['title']); ?></td>
                            <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'TBA'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Upcoming Deadlines</h3>
            <ul>
                <?php foreach ($upcomingDeadlines as $deadline): ?>
                <li>
                    <strong><?php echo htmlspecialchars($deadline['title']); ?></strong> for
                    <em><?php echo htmlspecialchars($deadline['course_title']); ?></em> due on
                    <?php echo date('M d, Y', strtotime($deadline['due_date'])); ?>
                </li>
                <?php endforeach; ?>
                <?php if (empty($upcomingDeadlines)): ?>
                <li>No upcoming deadlines.</li>
                <?php endif; ?>
            </ul>
        </div>
    </main>

    <script src="../js/student.js"></script>
</body>
</html>
