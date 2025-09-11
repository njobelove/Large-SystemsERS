<?php
require_once '../config.php';
require_once '../functions.php';

// Start session
session_start();
/*
// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.html');
    exit();
}*/

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    $action = $_GET['ajax'];

    if ($action === 'load_courses') {
        $courses = getMultipleRows("
            SELECT
                c.*,
                u.name as instructor_name,
                s.name as semester_name
            FROM courses c
            LEFT JOIN users u ON c.instructor_id = u.id
            LEFT JOIN semesters s ON c.semester_id = s.id
            ORDER BY c.code ASC
        ", [], '');

        echo json_encode(['courses' => $courses]);
        exit();
    }

    if ($action === 'load_instructors') {
        $instructors = getMultipleRows("
            SELECT id, name, email
            FROM users
            WHERE role = 'instructor'
            ORDER BY name ASC
        ", [], '');

        echo json_encode(['instructors' => $instructors]);
        exit();
    }

    if ($action === 'load_semesters') {
        $semesters = getMultipleRows("
            SELECT id, name, code
            FROM semesters
            WHERE status IN ('active', 'current')
            ORDER BY start_date DESC
        ", [], '');

        echo json_encode(['semesters' => $semesters]);
        exit();
    }

    if ($action === 'add_course') {
        $code = $_POST['code'] ?? '';
        $title = $_POST['title'] ?? '';
        $credits = (int)($_POST['credits'] ?? 0);
        $department = $_POST['department'] ?? '';
        $description = $_POST['description'] ?? '';
        $instructor_id = (int)($_POST['instructor_id'] ?? 0);
        $semester_id = (int)($_POST['semester_id'] ?? 0);

        if (!$code || !$title || !$credits || !$department || !$instructor_id || !$semester_id) {
            echo json_encode(['error' => 'All required fields must be filled']);
            exit();
        }

        // Check if course code already exists
        $existing = getSingleRow("SELECT id FROM courses WHERE code = ?", [$code], 's');
        if ($existing) {
            echo json_encode(['error' => 'Course code already exists']);
            exit();
        }

        // Insert new course
        $result = executeNonQuery("
            INSERT INTO courses (code, title, credits, department, description, instructor_id, semester_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [$code, $title, $credits, $department, $description, $instructor_id, $semester_id], 'ssisssi');

        if ($result) {
            // Get instructor name for confirmation
            $instructor = getSingleRow("SELECT name FROM users WHERE id = ? AND role = 'instructor'", [$instructor_id], 'i');
            $instructorName = $instructor ? $instructor['name'] : 'Unknown Instructor';

            echo json_encode([
                'success' => true,
                'message' => "Course added successfully and assigned to instructor: {$instructorName}",
                'instructor_name' => $instructorName
            ]);
        } else {
            echo json_encode(['error' => 'Failed to add course']);
        }
        exit();
    }

    if ($action === 'update_course') {
        $id = (int)($_POST['id'] ?? 0);
        $title = $_POST['title'] ?? '';
        $credits = (int)($_POST['credits'] ?? 0);
        $department = $_POST['department'] ?? '';
        $description = $_POST['description'] ?? '';
        $instructor_id = (int)($_POST['instructor_id'] ?? 0);
        $semester_id = (int)($_POST['semester_id'] ?? 0);

        if (!$id || !$title || !$credits || !$department || !$instructor_id || !$semester_id) {
            echo json_encode(['error' => 'All required fields must be filled']);
            exit();
        }

        // Update course
        $result = executeNonQuery("
            UPDATE courses
            SET title = ?, credits = ?, department = ?, description = ?, instructor_id = ?, semester_id = ?
            WHERE id = ?
        ", [$title, $credits, $department, $description, $instructor_id, $semester_id, $id], 'sisssii');

        if ($result) {
            // Get instructor name for confirmation
            $instructor = getSingleRow("SELECT name FROM users WHERE id = ? AND role = 'instructor'", [$instructor_id], 'i');
            $instructorName = $instructor ? $instructor['name'] : 'Unknown Instructor';

            echo json_encode([
                'success' => true,
                'message' => "Course updated successfully and assigned to instructor: {$instructorName}",
                'instructor_name' => $instructorName
            ]);
        } else {
            echo json_encode(['error' => 'Failed to update course']);
        }
        exit();
    }

    if ($action === 'delete_course') {
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['error' => 'Course ID is required']);
            exit();
        }

        // Check if course is being used
        $inUse = getSingleRow("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?", [$id], 'i');
        if ($inUse['count'] > 0) {
            echo json_encode(['error' => 'Cannot delete course that has enrolled students']);
            exit();
        }

        $result = executeNonQuery("DELETE FROM courses WHERE id = ?", [$id], 'i');

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
        } else {
            echo json_encode(['error' => 'Failed to delete course']);
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
    <title>Manage Courses</title>
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        .flex-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
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
            <li><a href="courses.php" class="active">Courses</a></li>
            <li><a href="student-info.php">Student Info</a></li>
            <li><a href="instructor-info.php">Instructor Info</a></li>
            <li><a href="programs.php">Programs</a></li>
            <li><a href="semester.php">Semester</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="flex-header">
            <h2>Manage Courses</h2>
            <button class="btn" onclick="showAddCourseModal()">Add New Course</button>
        </div>

        <div class="card">
            <div class="table-container">
                <table id="coursesTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Credits</th>
                            <th>Department</th>
                            <th>Instructor</th>
                            <th>Semester</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Table rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Course Modal -->
    <div id="addCourseModal" class="modal" style="display: none;">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <span class="close-btn" onclick="closeModal('addCourseModal')">&times;</span>
            <h3>Add New Course</h3>
            <form id="addCourseForm">
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="courseCode">Course Code</label>
                        <input type="text" id="courseCode" name="code" required />
                    </div>
                    <div class="form-group-half">
                        <label for="courseTitle">Course Title</label>
                        <input type="text" id="courseTitle" name="title" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="courseCredits">Credits</label>
                        <input type="number" id="courseCredits" name="credits" min="1" max="6" required />
                    </div>
                    <div class="form-group-half">
                        <label for="courseDepartment">Department</label>
                        <select id="courseDepartment" name="department" required>
                            <option value="">Select Department</option>
                            <option value="CS">Computer Science</option>
                            <option value="SEN">Software Engineering</option>
                            <option value="CYB">Cyber Security</option>
                            <option value="BMS">Business Administration</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="courseDescription">Description</label>
                    <textarea id="courseDescription" name="description" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="courseInstructor">Instructor</label>
                        <select id="courseInstructor" name="instructor_id" required>
                            <option value="">Select Instructor</option>
                            <!-- Will be populated by JavaScript -->
                        </select>
                    </div>
                    <div class="form-group-half">
                        <label for="courseSemester">Semester</label>
                        <select id="courseSemester" name="semester_id" required>
                            <option value="">Select Semester</option>
                            <!-- Will be populated by JavaScript -->
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn" style="display:block;width:100%;margin-top:20px;">Save</button>
            </form>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editCourseModal" class="modal" style="display: none;">
        <div class="modal-content" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
            <span class="close-btn" onclick="closeModal('editCourseModal')">&times;</span>
            <h3>Edit Course</h3>
            <form id="editCourseForm">
                <input type="hidden" id="editCourseId" name="id" value="">
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="editCourseCode">Course Code</label>
                        <input type="text" id="editCourseCode" required readonly />
                    </div>
                    <div class="form-group-half">
                        <label for="editCourseTitle">Course Title</label>
                        <input type="text" id="editCourseTitle" name="title" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="editCourseCredits">Credits</label>
                        <input type="number" id="editCourseCredits" name="credits" min="1" max="6" required />
                    </div>
                    <div class="form-group-half">
                        <label for="editCourseDepartment">Department</label>
                        <select id="editCourseDepartment" name="department" required>
                            <option value="">Select Department</option>
                            <option value="CS">Computer Science</option>
                            <option value="SEN">Software Engineering</option>
                            <option value="CYB">Cyber Security</option>
                            <option value="BMS">Business Administration</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editCourseDescription">Description</label>
                    <textarea id="editCourseDescription" name="description" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group-half">
                        <label for="editCourseInstructor">Instructor</label>
                        <select id="editCourseInstructor" name="instructor_id" required>
                            <option value="">Select Instructor</option>
                            <!-- Will be populated by JavaScript -->
                        </select>
                    </div>
                    <div class="form-group-half">
                        <label for="editCourseSemester">Semester</label>
                        <select id="editCourseSemester" name="semester_id" required>
                            <option value="">Select Semester</option>
                            <!-- Will be populated by JavaScript -->
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn" style="display:block;width:100%;margin-top:20px;">Update Course</button>
            </form>
        </div>
    </div>

    <script src="../js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadCourses();
        });

        function loadCourses() {
            fetch('courses.php?ajax=load_courses')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error loading courses:', data.error);
                        return;
                    }

                    const courses = data.courses || [];
                    populateCoursesTable(courses);
                })
                .catch(error => {
                    console.error('Error loading courses:', error);
                });
        }

        function populateCoursesTable(courses) {
            const tbody = document.querySelector('#coursesTable tbody');
            tbody.innerHTML = '';

            if (courses.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7">No courses found</td></tr>';
                return;
            }

            courses.forEach(course => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${course.code}</td>
                    <td>${course.title}</td>
                    <td>${course.credits}</td>
                    <td>${course.department}</td>
                    <td>${course.instructor_name || 'Not assigned'}</td>
                    <td>${course.semester_name || 'Not assigned'}</td>
                    <td>
                        <button class="btn-action edit" onclick="editCourse(${course.id})">Edit</button>
                        <button class="btn-action delete" onclick="deleteCourse(${course.id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function showAddCourseModal() {
            // Load instructors and semesters for the dropdowns
            Promise.all([
                fetch('courses.php?ajax=load_instructors').then(r => r.json()),
                fetch('courses.php?ajax=load_semesters').then(r => r.json())
            ]).then(([instructorData, semesterData]) => {
                // Populate instructor dropdown
                const instructorSelect = document.getElementById('courseInstructor');
                instructorSelect.innerHTML = '<option value="">Select Instructor</option>';
                instructorData.instructors.forEach(instructor => {
                    const option = document.createElement('option');
                    option.value = instructor.id;
                    option.textContent = instructor.name;
                    instructorSelect.appendChild(option);
                });

                // Populate semester dropdown
                const semesterSelect = document.getElementById('courseSemester');
                semesterSelect.innerHTML = '<option value="">Select Semester</option>';
                semesterData.semesters.forEach(semester => {
                    const option = document.createElement('option');
                    option.value = semester.id;
                    option.textContent = semester.name;
                    semesterSelect.appendChild(option);
                });

                document.getElementById('addCourseModal').style.display = 'block';
            });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editCourse(courseId) {
            // Load course data for editing
            fetch(`courses.php?ajax=load_courses`)
                .then(response => response.json())
                .then(data => {
                    const course = data.courses.find(c => c.id == courseId);
                    if (course) {
                        document.getElementById('editCourseId').value = course.id;
                        document.getElementById('editCourseCode').value = course.code;
                        document.getElementById('editCourseTitle').value = course.title;
                        document.getElementById('editCourseCredits').value = course.credits;
                        document.getElementById('editCourseDepartment').value = course.department;
                        document.getElementById('editCourseDescription').value = course.description || '';

                        // Load instructors and semesters for edit form
                        Promise.all([
                            fetch('courses.php?ajax=load_instructors').then(r => r.json()),
                            fetch('courses.php?ajax=load_semesters').then(r => r.json())
                        ]).then(([instructorData, semesterData]) => {
                            // Populate instructor dropdown
                            const instructorSelect = document.getElementById('editCourseInstructor');
                            instructorSelect.innerHTML = '<option value="">Select Instructor</option>';
                            instructorData.instructors.forEach(instructor => {
                                const option = document.createElement('option');
                                option.value = instructor.id;
                                option.textContent = instructor.name;
                                if (instructor.id == course.instructor_id) {
                                    option.selected = true;
                                }
                                instructorSelect.appendChild(option);
                            });

                            // Populate semester dropdown
                            const semesterSelect = document.getElementById('editCourseSemester');
                            semesterSelect.innerHTML = '<option value="">Select Semester</option>';
                            semesterData.semesters.forEach(semester => {
                                const option = document.createElement('option');
                                option.value = semester.id;
                                option.textContent = semester.name;
                                if (semester.id == course.semester_id) {
                                    option.selected = true;
                                }
                                semesterSelect.appendChild(option);
                            });

                            document.getElementById('editCourseModal').style.display = 'block';
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading course for edit:', error);
                });
        }

        function deleteCourse(courseId) {
            if (confirm('Are you sure you want to delete this course?')) {
                fetch('courses.php?ajax=delete_course', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${courseId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        loadCourses(); // Reload the table
                    } else {
                        alert(data.error || 'Failed to delete course');
                    }
                })
                .catch(error => {
                    console.error('Error deleting course:', error);
                    alert('Error deleting course');
                });
            }
        }

        // Handle add course form submission
        document.getElementById('addCourseForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                code: formData.get('code'),
                title: formData.get('title'),
                credits: formData.get('credits'),
                department: formData.get('department'),
                description: formData.get('description'),
                instructor_id: formData.get('instructor_id'),
                semester_id: formData.get('semester_id')
            };

            fetch('courses.php?ajax=add_course', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `code=${encodeURIComponent(data.code)}&title=${encodeURIComponent(data.title)}&credits=${data.credits}&department=${encodeURIComponent(data.department)}&description=${encodeURIComponent(data.description)}&instructor_id=${data.instructor_id}&semester_id=${data.semester_id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal('addCourseModal');
                    this.reset();
                    loadCourses(); // Reload the table
                } else {
                    alert(data.error || 'Failed to add course');
                }
            })
            .catch(error => {
                console.error('Error adding course:', error);
                alert('Error adding course');
            });
        });

        // Handle edit course form submission
        document.getElementById('editCourseForm')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                id: formData.get('id'),
                title: formData.get('title'),
                credits: formData.get('credits'),
                department: formData.get('department'),
                description: formData.get('description'),
                instructor_id: formData.get('instructor_id'),
                semester_id: formData.get('semester_id')
            };

            fetch('courses.php?ajax=update_course', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${data.id}&title=${encodeURIComponent(data.title)}&credits=${data.credits}&department=${encodeURIComponent(data.department)}&description=${encodeURIComponent(data.description)}&instructor_id=${data.instructor_id}&semester_id=${data.semester_id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal('editCourseModal');
                    loadCourses(); // Reload the table
                } else {
                    alert(data.error || 'Failed to update course');
                }
            })
            .catch(error => {
                console.error('Error updating course:', error);
                alert('Error updating course');
            });
        });
    </script>
</body>
</html>
