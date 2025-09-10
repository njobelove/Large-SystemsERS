<?php
require_once '../config.php';
require_once '../functions.php';

// Start session
session_start();

/*// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.html');
    exit();
}
*/
// Handle AJAX requests
if (isset($_GET['ajax'])) {
    $action = $_GET['ajax'];

    if ($action === 'load_students') {
        // Get all students with their program and semester information
        $students = getMultipleRows("
            SELECT
                u.id,
                u.name as fullName,
                u.email,
                u.phone,
                u.date_of_birth as dob,
                u.place_of_birth as pob,
                u.last_school_attended as school,
                p.name as program,
                s.name as semester,
                l.name as level
            FROM users u
            LEFT JOIN programs p ON u.program_id = p.id
            LEFT JOIN semesters s ON u.semester_id = s.id
            LEFT JOIN levels l ON u.level_id = l.id
            WHERE u.role = 'student'
            ORDER BY u.name ASC
        ", [], '');

        echo json_encode(['students' => $students]);
        exit();
    }

    if ($action === 'load_filters') {
        // Get unique values for filters
        $programs = getMultipleRows("SELECT DISTINCT name FROM programs ORDER BY name ASC", [], '');
        $semesters = getMultipleRows("SELECT DISTINCT name FROM semesters ORDER BY name ASC", [], '');
        $levels = getMultipleRows("SELECT DISTINCT name FROM levels ORDER BY name ASC", [], '');

        echo json_encode([
            'programs' => $programs,
            'semesters' => $semesters,
            'levels' => $levels
        ]);
        exit();
    }

    if ($action === 'filter_students') {
        $program = isset($_GET['program']) ? $_GET['program'] : '';
        $semester = isset($_GET['semester']) ? $_GET['semester'] : '';
        $level = isset($_GET['level']) ? $_GET['level'] : '';

        $where = "WHERE u.role = 'student'";
        $params = [];
        $types = '';

        if ($program) {
            $where .= " AND p.name = ?";
            $params[] = $program;
            $types .= 's';
        }

        if ($semester) {
            $where .= " AND s.name = ?";
            $params[] = $semester;
            $types .= 's';
        }

        if ($level) {
            $where .= " AND l.name = ?";
            $params[] = $level;
            $types .= 's';
        }

        $students = getMultipleRows("
            SELECT
                u.id,
                u.name as fullName,
                u.email,
                u.phone,
                u.date_of_birth as dob,
                u.place_of_birth as pob,
                u.last_school_attended as school,
                p.name as program,
                s.name as semester,
                l.name as level
            FROM users u
            LEFT JOIN programs p ON u.program_id = p.id
            LEFT JOIN semesters s ON u.semester_id = s.id
            LEFT JOIN levels l ON u.level_id = l.id
            $where
            ORDER BY u.name ASC
        ", $params, $types);

        echo json_encode(['students' => $students]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Information</title>
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .filter-group { margin-bottom: 20px; }
        .filter-group select { margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #222; color: #fff; }
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
                <span><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="courses.php">Courses</a></li>
            <li><a href="student-info.php" class="active">Student Info</a></li>
            <li><a href="instructor-info.php">Instructor Info</a></li>
            <li><a href="programs.php">Programs</a></li>
            <li><a href="semester.php">Semester</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Student Information</h2>
        <div class="filter-group">
            <label>Program:
                <select id="filterProgram">
                    <option value="">All</option>
                    <!-- Will be populated by JavaScript -->
                </select>
            </label>
            <label>Semester:
                <select id="filterSemester">
                    <option value="">All</option>
                    <!-- Will be populated by JavaScript -->
                </select>
            </label>
            <label>Level:
                <select id="filterLevel">
                    <option value="">All</option>
                    <!-- Will be populated by JavaScript -->
                </select>
            </label>
            <button onclick="applyFilters()">Filter</button>
            <button onclick="resetFilters()">Reset</button>
        </div>
        <div class="card">
            <div class="table-container">
                <table id="studentTable">
                    <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Program</th>
                    <th>Semester</th>
                    <th>Level</th>
                    <th>Date of Birth</th>
                    <th>Place of Birth</th>
                    <th>Last School Attended</th>
                </tr>
            </thead>
            <tbody>
                <!-- Students will be loaded via JavaScript -->
            </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="../js/admin.js"></script>
    <script>
        let students = [];
        let allPrograms = [];
        let allSemesters = [];
        let allLevels = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadFilters();
            loadStudents();
        });

        function loadFilters() {
            fetch('student-info.php?ajax=load_filters')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error loading filters:', data.error);
                        return;
                    }

                    // Populate program filter
                    const programSelect = document.getElementById('filterProgram');
                    data.programs.forEach(program => {
                        const option = document.createElement('option');
                        option.value = program.name;
                        option.textContent = program.name;
                        programSelect.appendChild(option);
                    });

                    // Populate semester filter
                    const semesterSelect = document.getElementById('filterSemester');
                    data.semesters.forEach(semester => {
                        const option = document.createElement('option');
                        option.value = semester.name;
                        option.textContent = semester.name;
                        semesterSelect.appendChild(option);
                    });

                    // Populate level filter
                    const levelSelect = document.getElementById('filterLevel');
                    data.levels.forEach(level => {
                        const option = document.createElement('option');
                        option.value = level.name;
                        option.textContent = level.name;
                        levelSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading filters:', error);
                });
        }

        function loadStudents() {
            fetch('student-info.php?ajax=load_students')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error loading students:', data.error);
                        return;
                    }

                    students = data.students || [];
                    renderTable(students);
                })
                .catch(error => {
                    console.error('Error loading students:', error);
                });
        }

        function renderTable(studentList) {
            const tbody = document.getElementById('studentTable').querySelector('tbody');
            tbody.innerHTML = '';

            if (studentList.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9">No students found</td></tr>';
                return;
            }

            studentList.forEach(student => {
                const row = document.createElement('tr');
                const dob = student.dob ? new Date(student.dob).toLocaleDateString() : '';

                row.innerHTML = `
                <td><a href="../student/dashboard.php?student_id=${student.id}" style="color:#0077cc;text-decoration:underline;" target="_blank">${student.fullName || ''}</a></td>
                    <td>${student.email || ''}</td>
                    <td>${student.phone || ''}</td>
                    <td>${student.program || ''}</td>
                    <td>${student.semester || ''}</td>
                    <td>${student.level || ''}</td>
                    <td>${dob}</td>
                    <td>${student.pob || ''}</td>
                    <td>${student.school || ''}</td>
                `;
                tbody.appendChild(row);
            });
        }

        function applyFilters() {
            const program = document.getElementById('filterProgram').value;
            const semester = document.getElementById('filterSemester').value;
            const level = document.getElementById('filterLevel').value;

            const params = new URLSearchParams();
            params.append('ajax', 'filter_students');

            if (program) params.append('program', program);
            if (semester) params.append('semester', semester);
            if (level) params.append('level', level);

            fetch(`student-info.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error filtering students:', data.error);
                        return;
                    }

                    renderTable(data.students || []);
                })
                .catch(error => {
                    console.error('Error filtering students:', error);
                });
        }

        function resetFilters() {
            document.getElementById('filterProgram').value = '';
            document.getElementById('filterSemester').value = '';
            document.getElementById('filterLevel').value = '';
            renderTable(students);
        }
    </script>
</body>
</html>
