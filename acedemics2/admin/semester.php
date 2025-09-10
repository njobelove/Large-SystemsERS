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
if (isset($_GET['ajax'])) {
    $action = $_GET['ajax'];

    if ($action === 'load_semesters') {
        $semesters = getMultipleRows("
            SELECT * FROM semesters
            ORDER BY start_date DESC
        ", [], '');

        echo json_encode(['semesters' => $semesters]);
        exit();
    }

    if ($action === 'add_semester') {
        $name = $_POST['name'] ?? '';
        $code = $_POST['code'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $registration_start = $_POST['registration_start'] ?? '';
        $registration_end = $_POST['registration_end'] ?? '';
        $status = $_POST['status'] ?? 'inactive';

        if (!$name || !$code || !$start_date || !$end_date || !$registration_start || !$registration_end) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }

        // Check if semester code already exists
        $existing = getSingleRow("SELECT id FROM semesters WHERE code = ?", [$code], 's');
        if ($existing) {
            echo json_encode(['error' => 'Semester code already exists']);
            exit();
        }

        // If status is 'current', set all other semesters to 'inactive'
        if ($status === 'current') {
            executeNonQuery("UPDATE semesters SET status = 'inactive'", [], '');
        }

        // Insert new semester
        $result = executeNonQuery("
            INSERT INTO semesters (name, code, start_date, end_date, registration_start, registration_end, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ", [$name, $code, $start_date, $end_date, $registration_start, $registration_end, $status], 'sssssss');

        if ($result) {
            // After adding semester, also update all related places in the system if needed
            // For example, update current semester selection or other references if applicable
            echo json_encode(['success' => true, 'message' => 'Semester added successfully']);
        } else {
            echo json_encode(['error' => 'Failed to add semester']);
        }
        exit();
    }

    if ($action === 'delete_semester') {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['error' => 'Semester ID is required']);
            exit();
        }

        // Check if semester is being used
        $inUse = getSingleRow("SELECT COUNT(*) as count FROM users WHERE semester_id = ?", [$id], 'i');
        if ($inUse['count'] > 0) {
            echo json_encode(['error' => 'Cannot delete semester that is assigned to users']);
            exit();
        }

        $result = executeNonQuery("DELETE FROM semesters WHERE id = ?", [$id], 'i');

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Semester deleted successfully']);
        } else {
            echo json_encode(['error' => 'Failed to delete semester']);
        }
        exit();
    }

    if ($action === 'update_semester') {
        $id = (int)($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $code = $_POST['code'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $registration_start = $_POST['registration_start'] ?? '';
        $registration_end = $_POST['registration_end'] ?? '';
        $status = $_POST['status'] ?? 'inactive';

        if (!$id || !$name || !$code || !$start_date || !$end_date || !$registration_start || !$registration_end) {
            echo json_encode(['error' => 'All fields are required']);
            exit();
        }

        // Check if another semester with same code exists
        $existing = getSingleRow("SELECT id FROM semesters WHERE code = ? AND id != ?", [$code, $id], 'si');
        if ($existing) {
            echo json_encode(['error' => 'Semester code already exists']);
            exit();
        }

        // If status is 'current', set all other semesters to 'inactive'
        if ($status === 'current') {
            executeNonQuery("UPDATE semesters SET status = 'inactive'", [], '');
        }

        // Update semester
        $result = executeNonQuery("
            UPDATE semesters
            SET name = ?, code = ?, start_date = ?, end_date = ?, registration_start = ?, registration_end = ?, status = ?
            WHERE id = ?
        ", [$name, $code, $start_date, $end_date, $registration_start, $registration_end, $status, $id], 'sssssssi');

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Semester updated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to update semester']);
        }
        exit();
    }

    if ($action === 'set_current_semester') {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['error' => 'Semester ID is required']);
            exit();
        }

        // First, set all semesters to inactive
        executeNonQuery("UPDATE semesters SET status = 'inactive'", [], '');

        // Then set the selected semester as current
        $result = executeNonQuery("UPDATE semesters SET status = 'current' WHERE id = ?", [$id], 'i');

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Current semester updated successfully']);
        } else {
            echo json_encode(['error' => 'Failed to update current semester']);
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
    <title>Manage Semesters</title>
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
            <li><a href="programs.php">Programs</a></li>
            <li><a href="semester.php" class="active">Semester</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Manage Academic Semesters</h2>
            <button class="btn" onclick="showAddSemesterModal()">Add New Semester</button>
        </div>

        <div class="card">
            <div class="table-container">
                <table id="semestersTable">
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Registration Start</th>
                            <th>Registration End</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h3>Current Semester: <span id="currentSemester">Loading...</span></h3>
            <div class="current-semester-actions">
                <select id="semesterToSetCurrent">
                    <option value="">Select semester to set as current</option>
                    <!-- Will be populated by JavaScript -->
                </select>
                <button class="btn" onclick="setSelectedAsCurrentSemester()">Set as Current Semester</button>
            </div>
        </div>
    </main>

    <!-- Add Semester Modal -->
    <div id="addSemesterModal" class="modal" style="display: none;">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <span class="close-btn" onclick="closeModal('addSemesterModal')">&times;</span>
            <h3>Add New Semester</h3>
            <form id="addSemesterForm">
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="semesterName">Semester Name</label>
                        <input type="text" id="semesterName" name="name" placeholder="e.g., Fall 2023" required>
                    </div>
                    <div class="form-group-half">
                        <label for="semesterCode">Semester Code</label>
                        <input type="text" id="semesterCode" name="code" placeholder="e.g., FA23" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="semesterStart">Start Date</label>
                        <input type="date" id="semesterStart" name="start_date" required>
                    </div>
                    <div class="form-group-half">
                        <label for="semesterEnd">End Date</label>
                        <input type="date" id="semesterEnd" name="end_date" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="registrationStart">Registration Start</label>
                        <input type="date" id="registrationStart" name="registration_start" required>
                    </div>
                    <div class="form-group-half">
                        <label for="registrationEnd">Registration End</label>
                        <input type="date" id="registrationEnd" name="registration_end" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <div style="display: flex; gap: 20px; margin-top: 10px;">
                            <label style="display: flex; align-items: center;">
                                <input type="radio" name="status" value="inactive" checked style="margin-right: 5px;">
                                Inactive
                            </label>
                            <label style="display: flex; align-items: center;">
                                <input type="radio" name="status" value="current" style="margin-right: 5px;">
                                Current
                            </label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn" style="display:block;width:100%;margin-top:20px;">Save</button>
            </form>
        </div>
    </div>

    <!-- Edit Semester Modal -->
    <div id="editSemesterModal" class="modal" style="display: none;">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <span class="close-btn" onclick="closeModal('editSemesterModal')">&times;</span>
            <h3>Edit Semester</h3>
            <form id="editSemesterForm">
                <input type="hidden" id="editSemesterId" name="id" value="">
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="editSemesterName">Semester Name</label>
                        <input type="text" id="editSemesterName" name="name" placeholder="e.g., Fall 2023" required>
                    </div>
                    <div class="form-group-half">
                        <label for="editSemesterCode">Semester Code</label>
                        <input type="text" id="editSemesterCode" name="code" placeholder="e.g., FA23" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="editSemesterStart">Start Date</label>
                        <input type="date" id="editSemesterStart" name="start_date" required>
                    </div>
                    <div class="form-group-half">
                        <label for="editSemesterEnd">End Date</label>
                        <input type="date" id="editSemesterEnd" name="end_date" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="editRegistrationStart">Registration Start</label>
                        <input type="date" id="editRegistrationStart" name="registration_start" required>
                    </div>
                    <div class="form-group-half">
                        <label for="editRegistrationEnd">Registration End</label>
                        <input type="date" id="editRegistrationEnd" name="registration_end" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <div style="display: flex; gap: 20px; margin-top: 10px;">
                            <label style="display: flex; align-items: center;">
                                <input type="radio" name="status" value="inactive" id="editStatusInactive" style="margin-right: 5px;">
                                Inactive
                            </label>
                            <label style="display: flex; align-items: center;">
                                <input type="radio" name="status" value="current" id="editStatusCurrent" style="margin-right: 5px;">
                                Current
                            </label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn" style="display:block;width:100%;margin-top:20px;">Update Semester</button>
            </form>
        </div>
    </div>

    <script src="../js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadSemesters();
        });

        function loadSemesters() {
            fetch('semester.php?ajax=load_semesters')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error loading semesters:', data.error);
                        return;
                    }

                    const semesters = data.semesters || [];
                    populateSemestersTable(semesters);
                    populateCurrentSemesterSelect(semesters);

                    // Update current semester display
                    const currentSemester = semesters.find(s => s.status === 'current');
                    document.getElementById('currentSemester').textContent = currentSemester ? currentSemester.name : 'None';
                })
                .catch(error => {
                    console.error('Error loading semesters:', error);
                });
        }

        function populateSemestersTable(semesters) {
            const tbody = document.querySelector('#semestersTable tbody');
            tbody.innerHTML = '';

            if (semesters.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7">No semesters found</td></tr>';
                return;
            }

            semesters.forEach(semester => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${semester.name} (${semester.code})</td>
                    <td>${formatDate(semester.start_date)}</td>
                    <td>${formatDate(semester.end_date)}</td>
                    <td>${formatDate(semester.registration_start)}</td>
                    <td>${formatDate(semester.registration_end)}</td>
                    <td><span class="status-badge ${semester.status}">${semester.status.charAt(0).toUpperCase() + semester.status.slice(1)}</span></td>
                    <td>
                        <button class="btn-action edit" onclick="editSemester(${semester.id})">Edit</button>
                        <button class="btn-action delete" onclick="deleteSemester(${semester.id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function populateCurrentSemesterSelect(semesters) {
            const select = document.getElementById('semesterToSetCurrent');
            select.innerHTML = '<option value="">Select semester to set as current</option>';

            semesters.forEach(semester => {
                const option = document.createElement('option');
                option.value = semester.id;
                option.textContent = semester.name;
                select.appendChild(option);
            });
        }

        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        function showAddSemesterModal() {
            document.getElementById('addSemesterModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editSemester(semesterId) {
            // Load semester data for editing
            fetch(`semester.php?ajax=load_semesters`)
                .then(response => response.json())
                .then(data => {
                    const semester = data.semesters.find(s => s.id == semesterId);
                    if (semester) {
                        document.getElementById('editSemesterId').value = semester.id;
                        document.getElementById('editSemesterName').value = semester.name;
                        document.getElementById('editSemesterCode').value = semester.code;
                        document.getElementById('editSemesterStart').value = semester.start_date;
                        document.getElementById('editSemesterEnd').value = semester.end_date;
                        document.getElementById('editRegistrationStart').value = semester.registration_start;
                        document.getElementById('editRegistrationEnd').value = semester.registration_end;

                        // Set status radio buttons
                        if (semester.status === 'current') {
                            document.getElementById('editStatusCurrent').checked = true;
                        } else {
                            document.getElementById('editStatusInactive').checked = true;
                        }

                        document.getElementById('editSemesterModal').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading semester for edit:', error);
                });
        }

        function deleteSemester(semesterId) {
            if (confirm('Are you sure you want to delete this semester?')) {
                fetch('semester.php?ajax=delete_semester', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${semesterId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        loadSemesters(); // Reload the table
                    } else {
                        alert(data.error || 'Failed to delete semester');
                    }
                })
                .catch(error => {
                    console.error('Error deleting semester:', error);
                    alert('Error deleting semester');
                });
            }
        }

        function setSelectedAsCurrentSemester() {
            const semesterId = document.getElementById('semesterToSetCurrent').value;
            if (!semesterId) {
                alert('Please select a semester');
                return;
            }

            fetch('semester.php?ajax=set_current_semester', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${semesterId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadSemesters(); // Reload to show updated status
                } else {
                    alert(data.error || 'Failed to set current semester');
                }
            })
            .catch(error => {
                console.error('Error setting current semester:', error);
                alert('Error setting current semester');
            });
        }

        // Handle form submission
        document.getElementById('addSemesterForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                name: formData.get('name'),
                code: formData.get('code'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date'),
                registration_start: formData.get('registration_start'),
                registration_end: formData.get('registration_end'),
                status: formData.get('status') || 'inactive'
            };

            fetch('semester.php?ajax=add_semester', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `name=${encodeURIComponent(data.name)}&code=${encodeURIComponent(data.code)}&start_date=${data.start_date}&end_date=${data.end_date}&registration_start=${data.registration_start}&registration_end=${data.registration_end}&status=${encodeURIComponent(data.status)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal('addSemesterModal');
                    this.reset();
                    loadSemesters(); // Reload the table
                } else {
                    alert(data.error || 'Failed to add semester');
                }
            })
            .catch(error => {
                console.error('Error adding semester:', error);
                alert('Error adding semester');
            });
        });

        // Handle edit form submission
        document.getElementById('editSemesterForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                id: formData.get('id'),
                name: formData.get('name'),
                code: formData.get('code'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date'),
                registration_start: formData.get('registration_start'),
                registration_end: formData.get('registration_end'),
                status: formData.get('status') || 'inactive'
            };

            fetch('semester.php?ajax=update_semester', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${data.id}&name=${encodeURIComponent(data.name)}&code=${encodeURIComponent(data.code)}&start_date=${data.start_date}&end_date=${data.end_date}&registration_start=${data.registration_start}&registration_end=${data.registration_end}&status=${encodeURIComponent(data.status)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal('editSemesterModal');
                    loadSemesters(); // Reload the table
                } else {
                    alert(data.error || 'Failed to update semester');
                }
            })
            .catch(error => {
                console.error('Error updating semester:', error);
                alert('Error updating semester');
            });
        });
    </script>
</body>
</html>
