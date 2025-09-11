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

    // Check if instructor_id is provided in AJAX request (for admin viewing)
    $ajaxInstructorId = isset($_GET['instructor_id']) ? (int)$_GET['instructor_id'] : $instructorId;

    // For AJAX requests, ensure we have the correct instructor ID
    if (isset($_GET['ajax']) && !isset($_GET['instructor_id'])) {
        $ajaxInstructorId = $instructorId;
    }

    if ($action === 'load_students' && isset($_GET['course_id'])) {
        $courseId = (int)$_GET['course_id'];

        // Verify course belongs to instructor
        $course = getSingleRow("SELECT id FROM courses WHERE id = ? AND instructor_id = ?", [$courseId, $ajaxInstructorId], 'ii');
        if (!$course) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        // Get enrolled students
        $students = getMultipleRows("
            SELECT
                u.id,
                u.name,
                u.email
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            WHERE e.course_id = ? AND u.role = 'student' AND e.status = 'enrolled'
            ORDER BY u.name ASC
        ", [$courseId], 'i');

        echo json_encode(['students' => $students]);
        exit();
    }

        if ($action === 'load_attendance' && isset($_GET['course_id']) && isset($_GET['date'])) {
            $courseId = (int)$_GET['course_id'];
            $date = $_GET['date'];

            // Verify course belongs to instructor
            $course = getSingleRow("SELECT id FROM courses WHERE id = ? AND instructor_id = ?", [$courseId, $ajaxInstructorId], 'ii');
            if (!$course) {
                echo json_encode(['error' => 'Unauthorized']);
                exit();
            }

            try {
                // Get attendance records for the date
                $attendanceRecords = getMultipleRows("
                    SELECT
                        a.student_id,
                        a.status,
                        IFNULL(a.notes, '') AS notes,
                        u.name,
                        u.email
                    FROM attendance a
                    JOIN users u ON a.student_id = u.id
                    WHERE a.course_id = ? AND a.date = ?
                    ORDER BY u.name ASC
                ", [$courseId, $date], 'is');

                echo json_encode(['attendance' => $attendanceRecords]);
            } catch (Exception $e) {
                // If attendance table doesn't exist or query fails, return empty array
                // This is normal for new installations or when no attendance has been recorded yet
                echo json_encode(['attendance' => []]);
            }
            exit();
        }

    if ($action === 'save_attendance' && isset($_POST['course_id']) && isset($_POST['date'])) {
        $courseId = (int)$_POST['course_id'];
        $date = $_POST['date'];
        $attendanceData = json_decode($_POST['attendance'], true);

        // Verify course belongs to instructor
        $course = getSingleRow("SELECT id FROM courses WHERE id = ? AND instructor_id = ?", [$courseId, $ajaxInstructorId], 'ii');
        if (!$course) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        try {
            $saved = 0;
            foreach ($attendanceData as $record) {
                // Check if attendance record already exists
                $existing = getSingleRow("
                    SELECT id FROM attendance
                    WHERE course_id = ? AND student_id = ? AND date = ?
                ", [$courseId, (int)$record['student_id'], $date], 'iis');

                if ($existing) {
                    // Update existing record
                    executeNonQuery("
                        UPDATE attendance
                        SET status = ?, notes = ?, updated_at = NOW()
                        WHERE id = ?
                    ", [$record['status'], $record['notes'], $existing['id']], 'ssi');
                } else {
                    // Insert new record
                    executeNonQuery("
                        INSERT INTO attendance (course_id, student_id, date, status, notes, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ", [$courseId, (int)$record['student_id'], $date, $record['status'], $record['notes']], 'iisss');
                }
                $saved++;
            }
        } catch (Exception $e) {
            echo json_encode(['error' => 'Failed to save attendance: ' . $e->getMessage()]);
            exit();
        }

        echo json_encode(['success' => true, 'saved' => $saved]);
        exit();
    }

    if ($action === 'load_summary') {
        try {
            // Get attendance summary for all instructor's courses
            $summary = getMultipleRows("
                SELECT
                    c.code,
                    c.title,
                    COUNT(DISTINCT a.date) as total_classes,
                    ROUND(AVG(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) * 100, 1) as avg_attendance
                FROM courses c
                LEFT JOIN attendance a ON c.id = a.course_id
                WHERE c.instructor_id = ?
                GROUP BY c.id, c.code, c.title
                ORDER BY c.code ASC
            ", [$ajaxInstructorId], 'i');

            echo json_encode(['summary' => $summary]);
        } catch (Exception $e) {
            // If attendance table doesn't exist, return courses without attendance data
            $coursesOnly = getMultipleRows("
                SELECT
                    c.code,
                    c.title,
                    0 as total_classes,
                    0 as avg_attendance
                FROM courses c
                WHERE c.instructor_id = ?
                ORDER BY c.code ASC
            ", [$ajaxInstructorId], 'i');

            echo json_encode(['summary' => $coursesOnly]);
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
    <title>Attendance Management</title>
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
                <span><?php echo htmlspecialchars($instructorName); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Dashboard</a></li>
            <li><a href="courses.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Courses</a></li>
            <li><a href="attendance.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>" class="active">Attendance</a></li>
            <li><a href="gradebook.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Gradebook</a></li>
            <li><a href="assignments.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Assignments</a></li>
            <li><a href="analytics.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Analytics</a></li>
            <li><a href="schedule.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Attendance Management</h2>

        <?php if (!empty($courses)): ?>
            <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #c3e6cb;">
                <strong>✓</strong> You have <?php echo count($courses); ?> course(s) available for attendance management.
            </div>
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group-half">
                <label for="attendanceCourse">Select Course</label>
                <select id="attendanceCourse" onchange="loadAttendanceRecords()">
                    <option value="">Select a course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group-half">
                <label for="attendanceDate">Select Date</label>
                <input type="date" id="attendanceDate" onchange="loadAttendanceRecords()">
            </div>
        </div>

        <div class="card" id="attendanceRecordsContainer" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Attendance Records</h3>
            </div>
            <div class="table-container">
                <table id="attendanceTable">
                    <thead>
                        <tr>
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
            <button class="btn" style="margin-top: 15px;" onclick="saveAttendance()">Save Attendance</button>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Attendance Summary</h3>
            <div class="table-container">
                <table id="attendanceSummaryTable">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Total Classes</th>
                            <th>Average Attendance</th>
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

    <script src="../js/script.js"></script>
    <script src="../js/instructor.js"></script>
    <script>
        let currentCourse = '';
        let currentDate = '';
        let currentStudents = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadAttendanceSummary();

            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('attendanceDate').value = today;
        });

        function loadAttendanceRecords() {
            const courseId = document.getElementById('attendanceCourse').value;
            const date = document.getElementById('attendanceDate').value;

            if (!courseId || !date) {
                document.getElementById('attendanceRecordsContainer').style.display = 'none';
                return;
            }

            currentCourse = courseId;
            currentDate = date;

            // Get instructor_id from URL if present (for admin viewing)
            const urlParams = new URLSearchParams(window.location.search);
            const instructorId = urlParams.get('instructor_id');
            const instructorParam = instructorId ? `&instructor_id=${instructorId}` : '';

            // First load students, then load attendance
            Promise.all([
                fetch(`attendance.php?ajax=load_students&course_id=${courseId}${instructorParam}`).then(r => r.json()),
                fetch(`attendance.php?ajax=load_attendance&course_id=${courseId}&date=${date}${instructorParam}`).then(r => r.json())
            ])
            .then(([studentsData, attendanceData]) => {
                if (studentsData.error) {
                    alert('Error loading students: ' + studentsData.error);
                    return;
                }

                if (attendanceData.error) {
                    console.warn('Attendance data error (this may be normal for new dates):', attendanceData.error);
                    // Don't return here - we can still show students even if attendance data fails
                }

                currentStudents = studentsData.students || [];
                const existingAttendance = attendanceData.attendance || [];

                if (currentStudents.length === 0) {
                    alert('No students found for this course. Please ensure students are enrolled.');
                    return;
                }

                // Create attendance map for quick lookup
                const attendanceMap = {};
                existingAttendance.forEach(record => {
                    attendanceMap[record.student_id] = record;
                });

                const tbody = document.querySelector('#attendanceTable tbody');
                tbody.innerHTML = '';

                currentStudents.forEach(student => {
                    const existingRecord = attendanceMap[student.id];
                    const status = existingRecord ? existingRecord.status : 'present';
                    const notes = existingRecord ? existingRecord.notes : '';

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>S${String(student.id).padStart(4, '0')}</td>
                        <td>${student.name}</td>
                        <td>
                            <select class="attendance-status" data-student="${student.id}">
                                <option value="present" ${status === 'present' ? 'selected' : ''}>Present</option>
                                <option value="absent" ${status === 'absent' ? 'selected' : ''}>Absent</option>
                                <option value="late" ${status === 'late' ? 'selected' : ''}>Late</option>
                                <option value="excused" ${status === 'excused' ? 'selected' : ''}>Excused</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="attendance-notes" data-student="${student.id}"
                                   placeholder="Add notes..." value="${notes}">
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                document.getElementById('attendanceRecordsContainer').style.display = 'block';
                console.log(`Loaded ${currentStudents.length} students for attendance`);
            })
            .catch(error => {
                console.error('Error loading attendance records:', error);
                alert('Error loading attendance records. Please check the console for details.');
            });
        }

        function saveAttendance() {
            if (!currentCourse || !currentDate) {
                alert('Please select course and date first');
                return;
            }

            const statusSelects = document.querySelectorAll('.attendance-status');
            const notesInputs = document.querySelectorAll('.attendance-notes');

            // Build attendance data
            const attendanceData = [];
            statusSelects.forEach(select => {
                const studentId = select.dataset.student;
                const status = select.value;
                const notesInput = document.querySelector(`.attendance-notes[data-student="${studentId}"]`);
                const notes = notesInput ? notesInput.value : '';

                attendanceData.push({
                    student_id: studentId,
                    status: status,
                    notes: notes
                });
            });

            // Get instructor_id from URL if present (for admin viewing)
            const urlParams = new URLSearchParams(window.location.search);
            const instructorId = urlParams.get('instructor_id');
            const instructorParam = instructorId ? `&instructor_id=${instructorId}` : '';

            // Save attendance to database
            fetch(`attendance.php?ajax=save_attendance${instructorParam}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `course_id=${currentCourse}&date=${currentDate}&attendance=${encodeURIComponent(JSON.stringify(attendanceData))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Attendance saved successfully! ${data.saved} records updated.`);
                    // Redirect to records view
                    window.location.href = `attendance-records.php?course=${currentCourse}&date=${currentDate}`;
                } else {
                    alert('Error saving attendance: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error saving attendance:', error);
                alert('Error saving attendance');
            });
        }

        function loadAttendanceSummary() {
            // Get instructor_id from URL if present (for admin viewing)
            const urlParams = new URLSearchParams(window.location.search);
            const instructorId = urlParams.get('instructor_id');
            const instructorParam = instructorId ? `&instructor_id=${instructorId}` : '';

            // Load attendance summary from database
            fetch(`attendance.php?ajax=load_summary${instructorParam}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error loading summary:', data.error);
                        return;
                    }

                    const tbody = document.querySelector('#attendanceSummaryTable tbody');
                    tbody.innerHTML = '';

                    if (data.summary.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4">No courses found</td></tr>';
                        return;
                    }

                    data.summary.forEach(summary => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${summary.code} - ${summary.title}</td>
                            <td>${summary.total_classes || 0}</td>
                            <td>${summary.avg_attendance || 0}%</td>
                            <td>
                                <button class="btn-action" onclick="viewAttendanceDetails('${summary.code}')">View Details</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading attendance summary:', error);
                });
        }

        function viewAttendanceDetails(courseCode) {
            window.location.href = `all-attendance-records.php?course=${courseCode}`;
        }
    </script>
</body>
</html>
