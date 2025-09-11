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
$userId = $_SESSION['user_id'] ?? null;

// Check if user is logged in
if (!$userId) {
    if (isset($_GET['ajax'])) {
        echo json_encode(['error' => 'Not logged in']);
        exit();
    } else {
        header('Location: ../index.php');
        exit();
    }
}

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
    // Set content type for JSON responses
    header('Content-Type: application/json');
    // Disable error output to prevent HTML in JSON response
    ini_set('display_errors', 0);
    error_reporting(0);

    $action = $_GET['ajax'];

    if ($action === 'load_gradebook' && isset($_GET['course_id'])) {
        $courseId = (int)$_GET['course_id'];

        // Get course details
        $course = getSingleRow("SELECT * FROM courses WHERE id = ? AND instructor_id = ?", [$courseId, $instructorId], 'ii');

        if (!$course) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        // Get enrolled students with their grades
        $students = getMultipleRows("
            SELECT
                u.id,
                u.name,
                u.email,
                e.id as enrollment_id,
                GROUP_CONCAT(
                    CONCAT(g.assignment_id, ':', g.grade, ':', g.grade_points)
                    SEPARATOR '|'
                ) as grades
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            LEFT JOIN grades g ON e.id = g.enrollment_id
            WHERE e.course_id = ? AND u.role = 'student' AND e.status = 'enrolled'
            GROUP BY u.id, u.name, u.email, e.id
            ORDER BY u.name ASC
        ", [$courseId], 'i');

        // Get assignments for this course
        $assignments = getMultipleRows("
            SELECT * FROM assignments
            WHERE course_id = ?
            ORDER BY due_date ASC
        ", [$courseId], 'i');

        echo json_encode([
            'course' => $course,
            'students' => $students,
            'assignments' => $assignments
        ]);
        exit();
    }

    if ($action === 'save_grades' && isset($_POST['course_id'])) {
        $courseId = (int)$_POST['course_id'];
        $grades = json_decode($_POST['grades'], true);

        // Validate input
        if (!$grades || !is_array($grades)) {
            echo json_encode(['error' => 'Invalid grades data']);
            exit();
        }

        // Verify course belongs to instructor
        $course = getSingleRow("SELECT id FROM courses WHERE id = ? AND instructor_id = ?", [$courseId, $instructorId], 'ii');
        if (!$course) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        $saved = 0;
        $touchedEnrollments = [];
        foreach ($grades as $grade) {
            // Get enrollment ID
            $enrollment = getSingleRow("
                SELECT e.id FROM enrollments e
                WHERE e.student_id = ? AND e.course_id = ? AND e.status = 'enrolled'
            ", [(int)$grade['student_id'], $courseId], 'ii');

            if ($enrollment) {
                // Get assignment details
                $assn = getSingleRow("SELECT course_id, max_points FROM assignments WHERE id = ?", [(int)$grade['assignment_id']], 'i');
                if (!$assn) {
                    continue; // Skip if assignment not found
                }

                $courseIdRow = (int)$assn['course_id'];
                $maxPts = (float)($assn['max_points'] ?? 100);
                $studentIdRow = (int)$grade['student_id'];
                $gradeValue = (float)$grade['grade'];

                // Validate grade doesn't exceed max points
                if ($gradeValue > $maxPts) {
                    continue; // Skip invalid grades
                }

                $pct = $maxPts > 0 ? ($gradeValue / $maxPts) * 100.0 : 0.0;

                // Map percent to letter
                if ($pct >= 90) $letter = 'A';
                else if ($pct >= 80) $letter = 'B';
                else if ($pct >= 70) $letter = 'C';
                else if ($pct >= 60) $letter = 'D';
                else $letter = 'F';

                // Credits and GPA
                $courseRow = getSingleRow("SELECT credits FROM courses WHERE id = ?", [$courseIdRow], 'i');
                $credits = (int)($courseRow['credits'] ?? 0);
                switch ($letter) {
                    case 'A': $gpa = 4.0; break;
                    case 'B': $gpa = 3.0; break;
                    case 'C': $gpa = 2.0; break;
                    case 'D': $gpa = 1.0; break;
                    default: $gpa = 0.0; break;
                }
                $gradePointsCalc = $gpa * $credits;

                // Check if grade already exists
                $existing = getSingleRow("
                    SELECT id FROM grades
                    WHERE enrollment_id = ? AND assignment_id = ?
                ", [$enrollment['id'], $grade['assignment_id']], 'ii');

                try {
                    if ($existing) {
                        // Update existing grade
                        $result = executeNonQuery("
                            UPDATE grades
                            SET grade = ?, grade_letter = ?, grade_points = ?, updated_at = NOW()
                            WHERE enrollment_id = ? AND assignment_id = ?
                        ", [$gradeValue, $letter, $gradePointsCalc, $enrollment['id'], $grade['assignment_id']], 'dsdii');
                    } else {
                        // Insert new grade
                        $result = executeNonQuery("
                            INSERT INTO grades (student_id, course_id, enrollment_id, assignment_id, grade, grade_letter, grade_points, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                        ", [$studentIdRow, $courseIdRow, $enrollment['id'], $grade['assignment_id'], $gradeValue, $letter, $gradePointsCalc], 'iiiidsd');
                    }

                    if ($result) {
                        $touchedEnrollments[$enrollment['id']] = ['course_id' => $courseId, 'student_id' => $studentIdRow];
                        $saved++;
                    }
                } catch (Exception $e) {
                    // Log error but continue processing other grades
                    error_log("Error saving grade for student {$studentIdRow}, assignment {$grade['assignment_id']}: " . $e->getMessage());
                    continue;
                }
            }
        }

        // Recompute and upsert final course grades for touched enrollments
        foreach ($touchedEnrollments as $enrollmentId => $meta) {
            $courseIdF = (int)$meta['course_id'];
            $studentIdF = (int)$meta['student_id'];

            $agg = getSingleRow(
                "SELECT SUM(g.grade) AS sum_grade, SUM(a.max_points) AS sum_max
                 FROM grades g
                 JOIN assignments a ON g.assignment_id = a.id
                 WHERE g.enrollment_id = ? AND a.course_id = ? AND g.assignment_id IS NOT NULL",
                [$enrollmentId, $courseIdF],
                'ii'
            );

            $sumGrade = isset($agg['sum_grade']) ? (float)$agg['sum_grade'] : 0.0;
            $sumMax   = isset($agg['sum_max']) ? (float)$agg['sum_max'] : 0.0;
            $finalPct = $sumMax > 0 ? ($sumGrade / $sumMax) * 100.0 : 0.0;

            if ($finalPct >= 87) $finalLetter = 'A';
            else if ($finalPct >= 79) $finalLetter = 'B+';
            else if ($finalPct >= 75) $finalLetter = 'B';
            else if ($finalPct >= 60) $finalLetter = 'C+';
            else if ($finalPct >= 50) $finalLetter = 'C';
            else if ($finalPct >= 49) $finalLetter = 'D+';
            else if ($finalPct >= 40) $finalLetter = 'D';
            else $finalLetter = 'F';

            $courseRow = getSingleRow("SELECT credits FROM courses WHERE id = ?", [$courseIdF], 'i');
            $credits = (int)($courseRow['credits'] ?? 0);
            switch ($finalLetter) {
                case 'A': $gpa = 4.0; break; case 'B+': $gpa = 3.5; break; case 'B': $gpa = 3.0; break;
                case 'C+': $gpa = 2.5; break; case 'C': $gpa = 2.0; break; case 'D+': $gpa = 1.5; break;
                case 'D': $gpa = 1.0; break; default: $gpa = 0.0; break;
            }
            $finalGradePoints = $gpa * $credits;

            $existingFinal = getSingleRow(
                "SELECT id FROM grades WHERE enrollment_id = ? AND assignment_id IS NULL",
                [$enrollmentId],
                'i'
            );

            if ($existingFinal) {
                executeNonQuery(
                    "UPDATE grades SET student_id = ?, course_id = ?, grade = ?, grade_letter = ?, grade_points = ? WHERE id = ?",
                    [$studentIdF, $courseIdF, $finalPct, $finalLetter, $finalGradePoints, $existingFinal['id']],
                    'iidsdi'
                );
            } else {
                executeNonQuery(
                    "INSERT INTO grades (student_id, course_id, enrollment_id, assignment_id, grade, grade_letter, grade_points, created_at) VALUES (?, ?, ?, NULL, ?, ?, ?, NOW())",
                    [$studentIdF, $courseIdF, $enrollmentId, $finalPct, $finalLetter, $finalGradePoints],
                    'iiidsd'
                );
            }
        }

        // Log success for debugging
        error_log("Gradebook save completed successfully. Saved: $saved");
        echo json_encode(['success' => true, 'saved' => $saved]);
        exit();
    }

    // If we reach here with an AJAX request but no valid action, return error
    echo json_encode(['error' => 'Invalid action']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gradebook</title>
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
            <li><a href="attendance.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Attendance</a></li>
            <li><a href="gradebook.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>" class="active">Gradebook</a></li>
            <li><a href="assignments.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Assignments</a></li>
            <li><a href="analytics.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Analytics</a></li>
            <li><a href="schedule.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Gradebook</h2>
            <button class="btn" onclick="exportGradebook()">Export Gradebook</button>
        </div>

        <?php if (!empty($courses)): ?>
            <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #c3e6cb;">
                <strong>✓</strong> You have <?php echo count($courses); ?> course(s) available for gradebook management.
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="gradebookCourse">Select Course:</label>
            <select id="gradebookCourse" onchange="loadGradebook()">
                <option value="">Select a course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="card" id="gradebookContainer" style="display: none;">
            <h3 id="gradebookTitle">Gradebook</h3>
            <div class="table-container" style="overflow-x: auto;">
                <table id="gradebookTable">
                    <thead>
                        <tr id="gradebookHeader">
                            <!-- Will be populated by JavaScript -->
                        </tr>
                    </thead>
                    <tbody id="gradebookBody">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px;">
                <button class="btn" onclick="saveGradebook()">Save Changes</button>
                <button class="btn btn-secondary" onclick="calculateFinalGrades()">Calculate Final Grades</button>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>
    <script src="../js/instructor.js"></script>
    <script>
        let currentAssignments = [];
        let currentStudents = [];

        function loadGradebook() {
            const courseId = document.getElementById('gradebookCourse').value;
            if (!courseId) {
                document.getElementById('gradebookContainer').style.display = 'none';
                return;
            }

            // Load gradebook data from database
            fetch(`gradebook.php?ajax=load_gradebook&course_id=${courseId}`, {
                credentials: 'include'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    currentAssignments = data.assignments;
                    currentStudents = data.students;

                    document.getElementById('gradebookTitle').textContent = `Gradebook for ${data.course.code} - ${data.course.title}`;

                    // Build header
                    const headerRow = document.getElementById('gradebookHeader');
                    headerRow.innerHTML = `
                        <th>Student ID</th>
                        <th>Name</th>
                        ${currentAssignments.map(a => `<th>${a.title}<br>${a.max_points} pts</th>`).join('')}
                        <th>Total</th>
                        <th>Grade</th>
                    `;

                    // Build body
                    const tbody = document.getElementById('gradebookBody');
                    tbody.innerHTML = '';

                    currentStudents.forEach(student => {
                        // Parse grades
                        const grades = {};
                        if (student.grades) {
                            student.grades.split('|').forEach(gradeStr => {
                                const [assignmentId, grade, gradePoints] = gradeStr.split(':');
                                grades[assignmentId] = { grade: parseFloat(grade), gradePoints: parseFloat(gradePoints) };
                            });
                        }

                        // Calculate total and grade (percentage of points earned)
                        let sumGrades = 0;
                        let sumMax = 0;

                        currentAssignments.forEach(assignment => {
                            const studentGrade = grades[assignment.id];
                            const maxPts = parseFloat(assignment.max_points) || 0;
                            if (!isNaN(maxPts)) {
                                sumMax += maxPts;
                            }
                            if (studentGrade && !isNaN(parseFloat(studentGrade.grade))) {
                                sumGrades += parseFloat(studentGrade.grade);
                            }
                        });

                        const average = sumMax > 0 ? (sumGrades / sumMax) * 100 : 0;
                        const letterGrade = calculateLetterGrade(average);

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>S${String(student.id).padStart(4, '0')}</td>
                            <td>${student.name}</td>
                            ${currentAssignments.map(a => {
                                const studentGrade = grades[a.id];
                                return `
                                    <td>
                                        <input type="number" value="${studentGrade ? studentGrade.grade : ''}"
                                               min="0" max="${a.max_points}"
                                               data-student="${student.id}"
                                               data-assignment="${a.id}">
                                    </td>
                                `;
                            }).join('')}
                            <td>${average.toFixed(1)}%</td>
                            <td>${letterGrade}</td>
                        `;
                        tbody.appendChild(row);
                    });

                    document.getElementById('gradebookContainer').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error loading gradebook:', error);
                    alert('Error loading gradebook data');
                });
        }

        function calculateLetterGrade(average) {
            if (average >= 87) return 'A';
            if (average >= 79) return 'B+';
            if (average >= 75) return 'B';
            if (average >= 60) return 'C+';
            if (average >= 50) return 'C';
            if (average >= 49) return 'D+';
            if (average >= 40) return 'D';
            if (average >= 30) return 'F';
            return 'F';
        }

        function saveGradebook() {
            const courseId = document.getElementById('gradebookCourse').value;
            if (!courseId) {
                alert('Please select a course first');
                return;
            }

            // Collect all grade inputs
            const gradeInputs = document.querySelectorAll('input[type="number"][data-student]');
            const grades = [];

            gradeInputs.forEach(input => {
                if (input.value && !isNaN(input.value)) {
                    const assignment = currentAssignments.find(a => a.id == input.dataset.assignment);
                    if (assignment) {
                        const gradeValue = parseFloat(input.value);
                        const maxPoints = parseFloat(assignment.max_points) || 100;

                        // Validate grade is not greater than max points
                        if (gradeValue > maxPoints) {
                            alert(`Grade for assignment "${assignment.title}" cannot exceed ${maxPoints} points`);
                            return;
                        }

                        grades.push({
                            student_id: input.dataset.student,
                            assignment_id: input.dataset.assignment,
                            grade: gradeValue
                        });
                    }
                }
            });

            if (grades.length === 0) {
                alert('No grades to save. Please enter some grades first.');
                return;
            }

            // Save grades to database
            const formData = new FormData();
            formData.append('course_id', courseId);
            formData.append('grades', JSON.stringify(grades));

            fetch('gradebook.php?ajax=save_grades', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Gradebook changes saved successfully!\n${data.saved} grades updated.`);
                    // Reload the gradebook to show updated data
                    loadGradebook();
                } else {
                    alert('Error saving grades: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error saving grades:', error);
                alert('Error saving grades: ' + error.message);
            });
        }

        function exportGradebook() {
            const courseId = document.getElementById('gradebookCourse').value;
            if (!courseId) {
                alert('Please select a course first');
                return;
            }

            // Create CSV content
            let csv = 'Student ID,Student Name,';
            currentAssignments.forEach(assignment => {
                csv += `${assignment.title} (${assignment.max_points}pts),`;
            });
            csv += 'Total,Letter Grade\n';

            currentStudents.forEach(student => {
                csv += `S${String(student.id).padStart(4, '0')},${student.name},`;

                // Parse grades
                const grades = {};
                if (student.grades) {
                    student.grades.split('|').forEach(gradeStr => {
                        const [assignmentId, grade] = gradeStr.split(':');
                        grades[assignmentId] = parseFloat(grade);
                    });
                }

                let sumGrades = 0;
                let sumMax = 0;

                currentAssignments.forEach(assignment => {
                    const grade = grades[assignment.id] || 0;
                    csv += `${grade},`;
                    sumGrades += parseFloat(grade) || 0;
                    sumMax += parseFloat(assignment.max_points) || 0;
                });

                const average = sumMax > 0 ? (sumGrades / sumMax) * 100 : 0;
                const letterGrade = calculateLetterGrade(average);

                csv += `${average.toFixed(1)}%,${letterGrade}\n`;
            });

            // Create download link
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `gradebook_${courseId}_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function calculateFinalGrades() {
            const courseId = document.getElementById('gradebookCourse').value;
            if (!courseId) {
                alert('Please select a course first');
                return;
            }

            // Calculate final grades for all students
            let message = 'Final grades calculated:\n\n';
            let count = 0;

            currentStudents.forEach(student => {
                // Parse grades
                const grades = {};
                if (student.grades) {
                    student.grades.split('|').forEach(gradeStr => {
                        const [assignmentId, grade] = gradeStr.split(':');
                        grades[assignmentId] = parseFloat(grade);
                    });
                }

                let totalPoints = 0;
                let totalMaxPoints = 0;

                currentAssignments.forEach(assignment => {
                    const grade = grades[assignment.id] || 0;
                    const maxPoints = parseFloat(assignment.max_points) || 0;

                    if (!isNaN(grade) && !isNaN(maxPoints) && maxPoints > 0) {
                        totalPoints += grade;
                        totalMaxPoints += maxPoints;
                    }
                });

                const average = totalMaxPoints > 0 ? (totalPoints / totalMaxPoints) * 100 : 0;
                const letterGrade = calculateLetterGrade(average);

                message += `${student.name}: ${letterGrade} (${average.toFixed(1)}%)\n`;
                count++;
            });

            message += `\n${count} students processed.`;
            alert(message);
        }
    </script>
</body>
</html>
