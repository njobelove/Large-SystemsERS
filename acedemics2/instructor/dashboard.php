<?php
require_once '../config.php';
require_once '../functions.php';

// Start session
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'instructor') {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Instructor';

// Check if admin is viewing a specific instructor's dashboard
$viewingInstructorId = null;
$viewingInstructor = null;
$isAdminView = false;

if (isset($_GET['instructor_id']) && isAdmin()) {
    $viewingInstructorId = (int)$_GET['instructor_id'];
    // Get complete instructor data with joined information for admin view
    $viewingInstructor = getMultipleRows("
        SELECT
            u.id, u.name, u.email, u.role, u.program_id, u.level_id, u.phone,
            u.date_of_birth, u.gender, u.degree, u.specialization,
            p.name as program_name, l.name as level_name
        FROM users u
        LEFT JOIN programs p ON u.program_id = p.id
        LEFT JOIN levels l ON u.level_id = l.id
        WHERE u.id = ? AND u.role = 'instructor'
    ", [$viewingInstructorId], 'i');

    if (!empty($viewingInstructor)) {
        $isAdminView = true;
        $userId = $viewingInstructorId;
        $userName = $viewingInstructor[0]['name'];
        $user = $viewingInstructor[0];
    } else {
        // Invalid instructor ID, redirect to admin dashboard
        header('Location: ../admin/dashboard.php');
        exit();
    }
} else {
    // Regular instructor view
    $user = getUserById($userId);
}

// Fetch courses assigned to the instructor
$courses = getMultipleRows("SELECT code, title FROM courses WHERE instructor_id = ?", [$userId], 'i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Instructor Dashboard</title>
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
                <span>
                <?php 
                    // Always show instructor name from DB if available, else fallback to session
                    if (!empty($user['name'])) {
                        echo htmlspecialchars($user['name']);
                    } else {
                        echo htmlspecialchars($_SESSION['user_name'] ?? 'Instructor');
                    }
                ?>
                </span>
            </div>
        </nav>
    </header>
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="courses.php">Courses</a></li>
            <li><a href="attendance.php">Attendance</a></li>
            <li><a href="gradebook.php">Gradebook</a></li>
            <li><a href="assignments.php">Assignments</a></li>
            <li><a href="materials.php">Materials</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="schedule.php">Schedule</a></li>
        </ul>
    </aside>
    <main class="main-content">
        <h2>Welcome, <?php echo htmlspecialchars($userName); ?></h2>
        <p>This is your instructor dashboard where you can manage your courses, attendance, grades, assignments, and more.</p>

        <?php if ($isAdminView): ?>
        <div class="card" style="margin-bottom: 30px;">
            <h3>Instructor Profile Information</h3>
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
                    <strong>Gender:</strong> <?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?>
                </div>
                <div class="info-row">
                    <strong>Degree:</strong> <?php echo htmlspecialchars($user['degree'] ?? 'N/A'); ?>
                </div>
                <div class="info-row">
                    <strong>Specialization:</strong> <?php echo htmlspecialchars($user['specialization'] ?? 'N/A'); ?>
                </div>
                <div class="info-row">
                    <strong>Program:</strong> <?php echo htmlspecialchars($user['program_name'] ?? 'N/A'); ?>
                </div>
                <div class="info-row">
                    <strong>Level:</strong> <?php echo htmlspecialchars($user['level_name'] ?? 'N/A'); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <h3>Your Courses</h3>
        <?php if (!empty($courses)): ?>
            <ul>
                <?php foreach ($courses as $course): ?>
                    <li><?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You have no courses assigned.</p>
        <?php endif; ?>
    </main>

    <script src="../js/instructor.js"></script>
</body>
</html>
