<?php
require_once '../config.php';
require_once '../functions.php';

// Start session
session_start();

// Check if user is logged in as admin
/*if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.html');
    exit();
}
*/
// Handle AJAX requests
if (isset($_GET['ajax'])) {
    $action = $_GET['ajax'];

    if ($action === 'load_instructors') {
        // Get all instructors with their program and level information
        $instructors = getMultipleRows("
            SELECT
                u.id,
                u.name as fullName,
                u.email,
                u.phone,
                u.date_of_birth as dob,
                u.gender,
                u.degree,
                u.specialization as teach,
                p.name as program,
                l.name as level
            FROM users u
            LEFT JOIN programs p ON u.program_id = p.id
            LEFT JOIN levels l ON u.level_id = l.id
            WHERE u.role = 'instructor'
            ORDER BY u.name ASC
        ", [], '');

        echo json_encode(['instructors' => $instructors]);
        exit();
    }

    if ($action === 'load_filters') {
        // Get unique values for filters
        $programs = getMultipleRows("SELECT DISTINCT name FROM programs ORDER BY name ASC", [], '');
        $levels = getMultipleRows("SELECT DISTINCT name FROM levels ORDER BY name ASC", [], '');

        echo json_encode([
            'programs' => $programs,
            'levels' => $levels
        ]);
        exit();
    }

    if ($action === 'filter_instructors') {
        $program = isset($_GET['program']) ? $_GET['program'] : '';
        $level = isset($_GET['level']) ? $_GET['level'] : '';

        $where = "WHERE u.role = 'instructor'";
        $params = [];
        $types = '';

        if ($program) {
            $where .= " AND p.name = ?";
            $params[] = $program;
            $types .= 's';
        }

        if ($level) {
            $where .= " AND l.name = ?";
            $params[] = $level;
            $types .= 's';
        }

        $instructors = getMultipleRows("
            SELECT
                u.id,
                u.name as fullName,
                u.email,
                u.phone,
                u.date_of_birth as dob,
                u.gender,
                u.degree,
                u.specialization as teach,
                p.name as program,
                l.name as level
            FROM users u
            LEFT JOIN programs p ON u.program_id = p.id
            LEFT JOIN levels l ON u.level_id = l.id
            $where
            ORDER BY u.name ASC
        ", $params, $types);

        echo json_encode(['instructors' => $instructors]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Instructor Information</title>
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .filter-group { margin-bottom: 20px; }
        .filter-group select { margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #222; color: #fff; }
        a.instructor-link { color: #0077cc; text-decoration: underline; cursor: pointer; }
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
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="courses.php">Courses</a></li>
            <li><a href="student-info.php">Student Info</a></li>
            <li><a href="instructor-info.php" class="active">Instructor Info</a></li>
            <li><a href="programs.php">Programs</a></li>
            <li><a href="semester.php">Semester</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <h2>Instructor Information</h2>
        <div class="filter-group">
            <label>Program:
                <select id="filterProgram">
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
                <table id="instructorTable">
                    <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Program</th>
                    <th>Level</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Degree</th>
                    <th>Specialization</th>
                </tr>
            </thead>
            <tbody id="instructorTableBody">
                <!-- Instructor rows will be inserted here -->
            </tbody>
        </table>
    </div>
</div>
<script>
    // Override renderTable to fix instructor link to dashboard with instructor_id param

</script>
    </main>

    <script src="../js/admin.js"></script>
    <script>
        let instructors = [];
        let allPrograms = [];
        let allLevels = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadFilters();
            loadInstructors();
        });

        function loadFilters() {
            fetch('instructor-info.php?ajax=load_filters')
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

        function loadInstructors() {
            fetch('instructor-info.php?ajax=load_instructors')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error loading instructors:', data.error);
                        return;
                    }

                    instructors = data.instructors || [];
                    renderTable(instructors);
                })
                .catch(error => {
                    console.error('Error loading instructors:', error);
                });
        }

        function renderTable(instructorList) {
            const tbody = document.getElementById('instructorTable').querySelector('tbody');
            tbody.innerHTML = '';

            if (instructorList.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9">No instructors found</td></tr>';
                return;
            }

            instructorList.forEach(instructor => {
                const row = document.createElement('tr');
                const dob = instructor.dob ? new Date(instructor.dob).toLocaleDateString() : '';

                row.innerHTML = `
                    <td><a class="instructor-link" href="../instructor/dashboard.php?instructor_id=${instructor.id}">${instructor.fullName || ''}</a></td>
                    <td>${instructor.email || ''}</td>
                    <td>${instructor.phone || ''}</td>
                    <td>${instructor.program || ''}</td>
                    <td>${instructor.level || ''}</td>
                    <td>${dob}</td>
                    <td>${instructor.gender || ''}</td>
                    <td>${instructor.degree || ''}</td>
                    <td>${instructor.teach || ''}</td>
                `;
                tbody.appendChild(row);
            });
        }

        function applyFilters() {
            const program = document.getElementById('filterProgram').value;
            const level = document.getElementById('filterLevel').value;

            const params = new URLSearchParams();
            params.append('ajax', 'filter_instructors');

            if (program) params.append('program', program);
            if (level) params.append('level', level);

            fetch(`instructor-info.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error filtering instructors:', data.error);
                        return;
                    }

                    renderTable(data.instructors || []);
                })
                .catch(error => {
                    console.error('Error filtering instructors:', error);
                });
        }

        function resetFilters() {
            document.getElementById('filterProgram').value = '';
            document.getElementById('filterLevel').value = '';
            renderTable(instructors);
        }
    </script>
</body>
</html>
