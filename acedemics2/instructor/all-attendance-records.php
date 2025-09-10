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

// Get course from URL parameter
$courseCode = isset($_GET['course']) ? $_GET['course'] : '';

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

    if ($action === 'load_all_records' && isset($_GET['course'])) {
        $courseCode = $_GET['course'];

        // Get course details by code
        $course = getSingleRow("
            SELECT c.* FROM courses c
            WHERE c.code = ? AND c.instructor_id = ?
        ", [$courseCode, $instructorId], 'si');

        if (!$course) {
            echo json_encode(['error' => 'Course not found or access denied']);
            exit();
        }

        // Get all attendance records for the course
        $records = getMultipleRows("
            SELECT
                a.date,
                a.status,
                a.notes,
                u.name as student_name,
                u.id as student_id
            FROM attendance a
            JOIN users u ON a.student_id = u.id
            WHERE a.course_id = ?
            ORDER BY a.date DESC, u.name ASC
        ", [$course['id']], 'i');

        echo json_encode(['records' => $records]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>All Attendance Records</title>
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">Academic System</div>
            <ul class="nav-links">
                <li><a href="attendance.php">Back to Attendance</a></li>
                <li><a href="#" onclick="logout()">Logout</a></li>
            </ul>
            <div class="user-info">
                <img src="../images/user-avatar.png" alt="User" />
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <h2>All Attendance Records for <span id="courseName"><?php echo htmlspecialchars($courseCode); ?></span></h2>
        <div class="card">
            <div class="search-container" style="margin-bottom: 15px;">
                <input type="text" id="searchName" placeholder="Search by student name" />
                <input type="date" id="searchDate" />
                <button class="btn" id="searchButton">Search</button>
            </div>
            <div class="table-container">
                <table id="attendanceSessionsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Notes</th>
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
        function logout() {
            // Clear session and redirect to index.php
            fetch('../logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
            })
            .then(() => {
                window.location.href = '../index.php';
            })
            .catch(() => {
                // Fallback if logout.php doesn't exist
                window.location.href = '../index.php';
            });
        }

        function getQueryParam(name) {
            const url = new URL(window.location.href);
            return url.searchParams.get(name);
        }

        function loadRecords(course, nameFilter = '', dateFilter = '') {
            if (!course) return;

            let url = `all-attendance-records.php?ajax=load_all_records&course=${encodeURIComponent(course)}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    let records = data.records;

                    // Filter by name if provided
                    if (nameFilter) {
                        const lowerName = nameFilter.toLowerCase();
                        records = records.filter(r => r.student_name.toLowerCase().includes(lowerName));
                    }

                    // Filter by date if provided
                    if (dateFilter) {
                        records = records.filter(r => r.date === dateFilter);
                    }

                    const tbody = document.querySelector('#attendanceSessionsTable tbody');
                    tbody.innerHTML = '';

                    if (records.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5">No attendance records found</td></tr>';
                        return;
                    }

                    records.forEach(record => {
                        const row = document.createElement('tr');
                        const formattedDate = new Date(record.date).toLocaleDateString();
                        row.innerHTML = `
                            <td>${formattedDate}</td>
                            <td>S${String(record.student_id).padStart(4, '0')}</td>
                            <td>${record.student_name}</td>
                            <td><span class="status-badge ${record.status}">${record.status.charAt(0).toUpperCase() + record.status.slice(1)}</span></td>
                            <td>${record.notes || ''}</td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading attendance records:', error);
                    alert('Error loading attendance records');
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const course = getQueryParam('course');
            document.getElementById('courseName').textContent = course || '';

            if (!course) {
                return;
            }

            loadRecords(course);

            document.getElementById('searchButton').addEventListener('click', function() {
                const nameFilter = document.getElementById('searchName').value.trim();
                const dateFilter = document.getElementById('searchDate').value;
                loadRecords(course, nameFilter, dateFilter);
            });
        });
    </script>
</body>
</html>
