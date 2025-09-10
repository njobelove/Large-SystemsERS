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

    if ($action === 'load_programs') {
        $programs = getMultipleRows("
            SELECT * FROM programs
            ORDER BY name ASC
        ", [], '');

        echo json_encode(['programs' => $programs]);
        exit();
    }

    if ($action === 'add_program') {
        $code = $_POST['code'] ?? '';
        $name = $_POST['name'] ?? '';
        $department = $_POST['department'] ?? '';
        $degree_level = $_POST['degree_level'] ?? '';
        $total_credits = (int)($_POST['total_credits'] ?? 0);
        $description = $_POST['description'] ?? '';

        if (!$code || !$name || !$department || !$degree_level || !$total_credits) {
            echo json_encode(['error' => 'All required fields must be filled']);
            exit();
        }

        // Check if program code already exists
        $existing = getSingleRow("SELECT id FROM programs WHERE code = ?", [$code], 's');
        if ($existing) {
            echo json_encode(['error' => 'Program code already exists']);
            exit();
        }

        // Insert new program
        $result = executeNonQuery("
            INSERT INTO programs (code, name, department, degree_level, total_credits, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$code, $name, $department, $degree_level, $total_credits, $description], 'ssssis');

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Program added successfully']);
        } else {
            echo json_encode(['error' => 'Failed to add program']);
        }
        exit();
    }

    if ($action === 'update_program') {
        $id = (int)($_POST['id'] ?? 0);
        $code = $_POST['code'] ?? '';
        $name = $_POST['name'] ?? '';
        $department = $_POST['department'] ?? '';
        $degree_level = $_POST['degree_level'] ?? '';
        $total_credits = (int)($_POST['total_credits'] ?? 0);
        $description = $_POST['description'] ?? '';

        if (!$id || !$code || !$name || !$department || !$degree_level || !$total_credits) {
            echo json_encode(['error' => 'All required fields must be filled']);
            exit();
        }

        // Check if another program with same code exists
        $existing = getSingleRow("SELECT id FROM programs WHERE code = ? AND id != ?", [$code, $id], 'si');
        if ($existing) {
            echo json_encode(['error' => 'Program code already exists']);
            exit();
        }

        // Update program
        $result = executeNonQuery("
            UPDATE programs
            SET code = ?, name = ?, department = ?, degree_level = ?, total_credits = ?, description = ?
            WHERE id = ?
        ", [$code, $name, $department, $degree_level, $total_credits, $description, $id], 'ssssisi');

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Program updated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to update program']);
        }
        exit();
    }

    if ($action === 'delete_program') {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['error' => 'Program ID is required']);
            exit();
        }

        // Check if program is being used
        $inUse = getSingleRow("SELECT COUNT(*) as count FROM users WHERE program_id = ?", [$id], 'i');
        if ($inUse['count'] > 0) {
            echo json_encode(['error' => 'Cannot delete program that is assigned to users']);
            exit();
        }

        $result = executeNonQuery("DELETE FROM programs WHERE id = ?", [$id], 'i');

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Program deleted successfully']);
        } else {
            echo json_encode(['error' => 'Failed to delete program']);
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Programs</title>
    <link rel="stylesheet" href="../css/style.css" />
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
            <li><a href="instructor-info.php">Instructor Info</a></li>
            <li><a href="programs.php" class="active">Programs</a></li>
            <li><a href="semester.php">Semester</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Manage Academic Programs</h2>
            <button class="btn" onclick="showAddProgramModal()">Add New Program</button>
        </div>

        <div class="card">
            <div class="table-container">
                <table id="programsTable">
                    <thead>
                        <tr>
                            <th>Program Code</th>
                            <th>Program Name</th>
                            <th>Department</th>
                            <th>Degree Level</th>
                            <th>Total Credits</th>
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

    <!-- Add Program Modal -->
    <div id="addProgramModal" class="modal" style="display: none;">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <span class="close-btn" onclick="closeModal('addProgramModal')">&times;</span>
            <h3 id="modalTitle">Add New Academic Program</h3>
            <form id="addProgramForm">
                <input type="hidden" id="programId" name="id" value="">
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="programCode">Program Code</label>
                        <input type="text" id="programCode" name="code" required>
                    </div>
                    <div class="form-group-half">
                        <label for="programName">Program Name</label>
                        <input type="text" id="programName" name="name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="programDepartment">Department</label>
                        <select id="programDepartment" name="department" required>
                            <option value="">Select Department</option>
                            <option value="CS">Computer Science</option>
                            <option value="SEN">Software Engineering</option>
                            <option value="CYB">Cyber Security</option>
                            <option value="BMS">Business Administration</option>
                        </select>
                    </div>
                    <div class="form-group-half">
                        <label for="programDegree">Degree Level</label>
                        <select id="programDegree" name="degree_level" required>
                            <option value="">Select Degree</option>
                            <option value="BACHELOR">Bachelor's</option>
                            <option value="MASTER">Master's</option>
                            <option value="PHD">PhD</option>
                            <option value="CERTIFICATE">Certificate</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="programCredits">Total Credits Required</label>
                    <input type="number" id="programCredits" name="total_credits" min="1" required>
                </div>
                <div class="form-group">
                    <label for="programDescription">Description</label>
                    <textarea id="programDescription" name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn" style="display:block;width:100%;margin-top:20px;" id="submitBtn">Save</button>
            </form>
        </div>
    </div>

    <script src="../js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadPrograms();
        });

        function loadPrograms() {
            fetch('programs.php?ajax=load_programs')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error loading programs:', data.error);
                        return;
                    }

                    const programs = data.programs || [];
                    populateProgramsTable(programs);
                })
                .catch(error => {
                    console.error('Error loading programs:', error);
                });
        }

        function populateProgramsTable(programs) {
            const tbody = document.querySelector('#programsTable tbody');
            tbody.innerHTML = '';

            if (programs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">No programs found</td></tr>';
                return;
            }

            programs.forEach(program => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${program.code}</td>
                    <td>${program.name}</td>
                    <td>${program.department}</td>
                    <td>${formatDegreeLevel(program.degree_level)}</td>
                    <td>${program.total_credits}</td>
                    <td>
                        <button class="btn-action edit" onclick="editProgram(${program.id})">Edit</button>
                        <button class="btn-action delete" onclick="deleteProgram(${program.id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function formatDegreeLevel(degree) {
            const degrees = {
                'BACHELOR': "Bachelor's",
                'MASTER': "Master's",
                'PHD': 'PhD',
                'CERTIFICATE': 'Certificate'
            };
            return degrees[degree] || degree;
        }

        function showAddProgramModal() {
            document.getElementById('addProgramModal').style.display = 'block';
            document.getElementById('addProgramForm').reset();
            document.getElementById('programId').value = '';
            document.getElementById('modalTitle').textContent = 'Add New Program';
            document.getElementById('submitBtn').textContent = 'Create Program';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editProgram(programId) {
            // Load program data for editing
            fetch(`programs.php?ajax=load_programs`)
                .then(response => response.json())
                .then(data => {
                    const program = data.programs.find(p => p.id == programId);
                    if (program) {
                        document.getElementById('programId').value = program.id;
                        document.getElementById('programCode').value = program.code;
                        document.getElementById('programName').value = program.name;
                        document.getElementById('programDepartment').value = program.department;
                        document.getElementById('programDegree').value = program.degree_level;
                        document.getElementById('programCredits').value = program.total_credits;
                        document.getElementById('programDescription').value = program.description || '';
                        document.getElementById('modalTitle').textContent = 'Edit Program';
                        document.getElementById('submitBtn').textContent = 'Update Program';
                        document.getElementById('addProgramModal').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading program for edit:', error);
                });
        }

        function deleteProgram(programId) {
            if (confirm('Are you sure you want to delete this program?')) {
                fetch('programs.php?ajax=delete_program', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${programId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        loadPrograms(); // Reload the table
                    } else {
                        alert(data.error || 'Failed to delete program');
                    }
                })
                .catch(error => {
                    console.error('Error deleting program:', error);
                    alert('Error deleting program');
                });
            }
        }

        // Handle form submission
        document.getElementById('addProgramForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const programId = formData.get('id');
            const data = {
                code: formData.get('code'),
                name: formData.get('name'),
                department: formData.get('department'),
                degree_level: formData.get('degree_level'),
                total_credits: formData.get('total_credits'),
                description: formData.get('description')
            };

            const action = programId ? 'update_program' : 'add_program';
            const body = programId ?
                `id=${programId}&code=${encodeURIComponent(data.code)}&name=${encodeURIComponent(data.name)}&department=${encodeURIComponent(data.department)}&degree_level=${encodeURIComponent(data.degree_level)}&total_credits=${data.total_credits}&description=${encodeURIComponent(data.description)}` :
                `code=${encodeURIComponent(data.code)}&name=${encodeURIComponent(data.name)}&department=${encodeURIComponent(data.department)}&degree_level=${encodeURIComponent(data.degree_level)}&total_credits=${data.total_credits}&description=${encodeURIComponent(data.description)}`;

            fetch(`programs.php?ajax=${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal('addProgramModal');
                    this.reset();
                    loadPrograms(); // Reload the table
                } else {
                    alert(data.error || 'Failed to save program');
                }
            })
            .catch(error => {
                console.error('Error saving program:', error);
                alert('Error saving program');
            });
        });
    </script>
</body>
</html>
