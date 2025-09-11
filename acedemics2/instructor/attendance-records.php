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

// Get course and date from URL parameters
$courseCode = isset($_GET['course']) ? $_GET['course'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Get course details
$course = null;
if ($courseCode) {
    $course = getSingleRow("
        SELECT c.* FROM courses c
        WHERE c.code = ? AND c.instructor_id = ?
    ", [$courseCode, $instructorId], 'si');
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    $action = $_GET['ajax'];

    if ($action === 'load_records' && isset($_GET['course_id']) && isset($_GET['date'])) {
        $courseId = (int)$_GET['course_id'];
        $date = $_GET['date'];

        // Verify course belongs to instructor
        $course = getSingleRow("SELECT id FROM courses WHERE id = ? AND instructor_id = ?", [$courseId, $instructorId], 'ii');
        if (!$course) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        // Get attendance records for the date
        $records = getMultipleRows("
            SELECT
                a.student_id,
                a.status,
                a.notes,
                u.name,
                u.email
            FROM attendance a
            JOIN users u ON a.student_id = u.id
            WHERE a.course_id = ? AND a.date = ?
            ORDER BY u.name ASC
        ", [$courseId, $date], 'is');

        // Calculate statistics
        $stats = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0
        ];

        foreach ($records as $record) {
            if (isset($stats[$record['status']])) {
                $stats[$record['status']]++;
            }
        }

        echo json_encode([
            'records' => $records,
            'stats' => $stats,
            'total_students' => count($records)
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
    <title>Attendance Records</title>
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
            <li><a href="attendance.php" class="active">Attendance</a></li>
            <li><a href="gradebook.php">Gradebook</a></li>
            <li><a href="assignments.php">Assignments</a></li>
            <li><a href="course-materials.php">Materials</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="schedule.php">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 id="recordsTitle">Attendance Records Saved</h2>
            <button class="btn btn-secondary" onclick="goBack()">Back to Attendance</button>
        </div>

        <div class="card">
            <h3>✅ Attendance Successfully Saved</h3>
            <div class="success-message">
                <p>Attendance records have been saved for:</p>
                <div class="saved-info">
                    <div class="info-item">
                        <strong>Course:</strong> <span id="savedCourse"><?php echo htmlspecialchars($courseCode); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Date:</strong> <span id="savedDate"><?php echo htmlspecialchars($date); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Students Recorded:</strong> <span id="studentsCount">Loading...</span>
                    </div>
                    <div class="info-item">
                        <strong>Saved At:</strong> <span id="saveTime"><?php echo date('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </div>



        <div class="card" style="margin-top: 30px;">
            <h3>Next Actions</h3>
            <div class="action-buttons">
                <button class="btn" onclick="recordAnotherDate()">Record Another Date</button>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>
    <script src="../js/instructor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadSavedRecords();
        });

        function loadSavedRecords() {
            const courseCode = document.getElementById('savedCourse').textContent;
            const date = document.getElementById('savedDate').textContent;

            if (!courseCode || !date) {
                return;
            }

            // Get course ID first
            fetch(`../api/get-course-id.php?code=${encodeURIComponent(courseCode)}`)
                .then(response => response.json())
                .then(courseData => {
                    if (courseData.error) {
                        console.error('Error getting course ID:', courseData.error);
                        return;
                    }

                    const courseId = courseData.id;

                    // Load attendance records from database
                    fetch(`attendance-records.php?ajax=load_records&course_id=${courseId}&date=${date}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                                return;
                            }

                            // Update counts
                            document.getElementById('studentsCount').textContent = data.total_students;
                        })
                        .catch(error => {
                            console.error('Error loading records:', error);
                            alert('Error loading attendance records');
                        });
                })
                .catch(error => {
                    console.error('Error getting course ID:', error);
                });
        }

        function goBack() {
            window.location.href = 'attendance.php';
        }

        function recordAnotherDate() {
            window.location.href = 'attendance.php';
        }
    </script>
</body>
</html>
