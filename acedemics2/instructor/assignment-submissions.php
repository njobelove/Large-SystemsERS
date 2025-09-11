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

// Get assignment ID
$assignmentId = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;

if (!$assignmentId) {
    header('Location: assignments.php');
    exit();
}

// Get assignment details
$assignment = getSingleRow("
    SELECT a.*, c.code as course_code, c.title as course_title
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND c.instructor_id = ?
", [$assignmentId, $instructorId], 'ii');

if (!$assignment) {
    header('Location: assignments.php');
    exit();
}

// Get instructor details
$instructor = getSingleRow("SELECT name FROM users WHERE id = ? AND role = 'instructor'", [$instructorId], 'i');
$instructorName = $instructor ? $instructor['name'] : 'Instructor';

// Get all submissions for this assignment
$submissions = getMultipleRows("
    SELECT s.*, u.name as student_name, u.email as student_email
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    WHERE s.assignment_id = ?
    ORDER BY s.submitted_at DESC
", [$assignmentId], 'i');

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    $action = $_GET['ajax'];

    if ($action === 'grade_submission') {
        $submissionId = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
        $grade = isset($_POST['grade']) ? trim($_POST['grade']) : '';
        $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';

        if (!$submissionId) {
            echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
            exit();
        }

        // Update submission with grade and feedback
        $result = executeQuery("
            UPDATE submissions
            SET grade = ?, feedback = ?, graded = 1
            WHERE id = ? AND assignment_id = ?
        ", [$grade, $feedback, $submissionId, $assignmentId], 'ssii');

        if ($result) {
            // Also upsert into grades table so it appears in gradebook and transcript
            // Fetch submission meta (student, course)
            $meta = getSingleRow("
                SELECT s.student_id, s.assignment_id, a.course_id, a.max_points
                FROM submissions s
                JOIN assignments a ON s.assignment_id = a.id
                WHERE s.id = ? AND a.id = ?
            ", [$submissionId, $assignmentId], 'ii');

            if ($meta) {
                $studentId = (int)$meta['student_id'];
                $courseId = (int)$meta['course_id'];
                $assnMax = (float)($meta['max_points'] ?? 100);

                // Get enrollment id
                $enr = getSingleRow("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ? AND status = 'enrolled'", [$studentId, $courseId], 'ii');
                $enrollmentId = $enr['id'] ?? null;

                // Fetch course credits
                $courseRow = getSingleRow("SELECT credits FROM courses WHERE id = ?", [$courseId], 'i');
                $credits = (int)($courseRow['credits'] ?? 0);

                // Normalize grade input to numeric points and letter
                $gradeInput = trim($grade);
                $numeric = null;
                $letter = null;

                // Helper: map percent -> letter
                $letterFromPercent = function($pct) {
                    if ($pct >= 87) return 'A';
                    if ($pct >= 79) return 'B+';
                    if ($pct >= 75) return 'B';
                    if ($pct >= 60) return 'C+';
                    if ($pct >= 50) return 'C';
                    if ($pct >= 49) return 'D+';
                    if ($pct >= 40) return 'D';
                    if ($pct >= 30) return 'F';
                    return 'F';
                };
                // Helper: map letter -> representative percent
                $percentFromLetter = function($ltr) {
                    switch (strtoupper(trim($ltr))) {
                        case 'A': return 95;
                        case 'B+': return 85;
                        case 'B': return 80;
                        case 'C+': return 70;
                        case 'C': return 60;
                        case 'D+': return 50;
                        case 'D': return 45;
                        default: return 30; // F or unknown
                    }
                };

                if (is_numeric($gradeInput)) {
                    $numeric = (float)$gradeInput; // points out of max
                    $pct = $assnMax > 0 ? ($numeric / $assnMax) * 100.0 : 0.0;
                    $letter = $letterFromPercent($pct);
                } else {
                    // Treat as letter grade string
                    $letterGiven = strtoupper(str_replace(' ', '', $gradeInput));
                    $letter = in_array($letterGiven, ['A','B+','B','C+','C','D+','D','F']) ? $letterGiven : 'F';
                    $pct = $percentFromLetter($letter);
                    $numeric = round(($pct / 100.0) * $assnMax, 2);
                }

                // GPA points mapping
                $gpaMap = [ 'A'=>4.0, 'B+'=>3.5, 'B'=>3.0, 'C+'=>2.5, 'C'=>2.0, 'D+'=>1.5, 'D'=>1.0, 'F'=>0.0 ];
                $gpa = $gpaMap[$letter] ?? 0.0;
                $gradePoints = $gpa * $credits; // per-course quality points for this graded item

                if ($enrollmentId) {
                    // Upsert by (enrollment_id, assignment_id)
                    $existing = getSingleRow(
                        "SELECT id FROM grades WHERE enrollment_id = ? AND assignment_id = ?",
                        [$enrollmentId, $assignmentId],
                        'ii'
                    );

                    if ($existing) {
                        executeNonQuery(
                            "UPDATE grades SET student_id = ?, course_id = ?, grade = ?, grade_letter = ?, grade_points = ? WHERE id = ?",
                            [$studentId, $courseId, $numeric, $letter, $gradePoints, $existing['id']],
                            'iidsdi'
                        );
                    } else {
                        executeNonQuery(
                            "INSERT INTO grades (student_id, course_id, enrollment_id, assignment_id, grade, grade_letter, grade_points, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                            [$studentId, $courseId, $enrollmentId, $assignmentId, $numeric, $letter, $gradePoints],
                            'iiiidsd'
                        );
                    }

                    // Recompute and upsert final course grade (assignment_id IS NULL)
                    $agg = getSingleRow(
                        "SELECT SUM(g.grade) AS sum_grade, SUM(a.max_points) AS sum_max
                         FROM grades g
                         JOIN assignments a ON g.assignment_id = a.id
                         WHERE g.enrollment_id = ? AND a.course_id = ? AND g.assignment_id IS NOT NULL",
                        [$enrollmentId, $courseId],
                        'ii'
                    );

                    $sumGrade = isset($agg['sum_grade']) ? (float)$agg['sum_grade'] : 0.0;
                    $sumMax   = isset($agg['sum_max']) ? (float)$agg['sum_max'] : 0.0;
                    $finalPct = $sumMax > 0 ? ($sumGrade / $sumMax) * 100.0 : 0.0;
                    $finalLetter = $letterFromPercent($finalPct);
                    $finalGpa = $gpaMap[$finalLetter] ?? 0.0;
                    $finalGradePoints = $finalGpa * $credits;

                    $existingFinal = getSingleRow(
                        "SELECT id FROM grades WHERE enrollment_id = ? AND assignment_id IS NULL",
                        [$enrollmentId],
                        'i'
                    );

                    if ($existingFinal) {
                        executeNonQuery(
                            "UPDATE grades SET student_id = ?, course_id = ?, grade = ?, grade_letter = ?, grade_points = ? WHERE id = ?",
                            [$studentId, $courseId, $finalPct, $finalLetter, $finalGradePoints, $existingFinal['id']],
                            'iidsdi'
                        );
                    } else {
                        executeNonQuery(
                            "INSERT INTO grades (student_id, course_id, enrollment_id, assignment_id, grade, grade_letter, grade_points, created_at)
                             VALUES (?, ?, ?, NULL, ?, ?, ?, NOW())",
                            [$studentId, $courseId, $enrollmentId, $finalPct, $finalLetter, $finalGradePoints],
                            'iiidsd'
                        );
                    }
                }
            }

            echo json_encode(['success' => true, 'message' => 'Submission graded successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to grade submission']);
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
    <title>Assignment Submissions</title>
    <link rel="stylesheet" href="../css/style.css" />
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .submission-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .submission-actions {
            display: flex;
            gap: 10px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
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
                <span><?php echo htmlspecialchars($instructorName); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Dashboard</a></li>
            <li><a href="courses.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Courses</a></li>
            <li><a href="attendance.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Attendance</a></li>
            <li><a href="gradebook.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Gradebook</a></li>
            <li><a href="assignments.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>" class="active">Assignments</a></li>
            <li><a href="analytics.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Analytics</a></li>
            <li><a href="schedule.php<?php echo $viewInstructorId ? '?instructor_id=' . $viewInstructorId : ''; ?>">Schedule</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2><?php echo htmlspecialchars($assignment['title']); ?> - Submissions</h2>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_title']); ?></p>
                <p><strong>Due Date:</strong> <?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?></p>
            </div>
            <button class="btn" onclick="window.location.href='assignments.php'">← Back to Assignments</button>
        </div>

        <div class="card">
            <h3>Student Submissions (<?php echo count($submissions); ?>)</h3>

            <?php if (empty($submissions)): ?>
                <p>No submissions received yet.</p>
            <?php else: ?>
                <?php foreach ($submissions as $submission): ?>
                <div class="submission-card">
                    <div class="submission-header">
                        <div>
                            <strong><?php echo htmlspecialchars($submission['student_name']); ?></strong>
                            <br>
                            <small><?php echo htmlspecialchars($submission['student_email']); ?></small>
                            <br>
                            <small>Submitted: <?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></small>
                        </div>
                        <div class="submission-actions">
                            <?php if (!empty($submission['file_path'])): ?>
                                <a href="download-submission.php?id=<?php echo $submission['id']; ?>" class="btn btn-small">Download</a>
                            <?php endif; ?>
                            <button class="btn btn-small" onclick="gradeSubmission(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($submission['student_name']); ?>', '<?php echo htmlspecialchars($submission['grade'] ?? ''); ?>', '<?php echo htmlspecialchars($submission['feedback'] ?? ''); ?>')">
                                <?php echo !empty($submission['grade']) ? 'Update Grade' : 'Grade'; ?>
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($submission['grade'])): ?>
                        <div style="margin-top: 10px; padding: 10px; background-color: #e8f5e8; border-radius: 4px;">
                            <strong>Grade:</strong> <?php echo htmlspecialchars($submission['grade']); ?>/<?php echo htmlspecialchars($assignment['max_points']); ?>
                            <?php if (!empty($submission['feedback'])): ?>
                                <br><strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($submission['feedback']) && empty($submission['grade'])): ?>
                        <div style="margin-top: 10px; padding: 10px; background-color: #fff3cd; border-radius: 4px;">
                            <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Grade Modal -->
    <div id="gradeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeGradeModal()">&times;</span>
            <h3 id="gradeModalTitle">Grade Submission</h3>
            <form id="gradeForm">
                <input type="hidden" id="submissionId" name="submission_id">
                <div class="form-group">
                    <label for="grade">Grade (out of <?php echo htmlspecialchars($assignment['max_points']); ?>):</label>
                    <input type="text" id="grade" name="grade" placeholder="e.g., 85, A-, 95/100">
                </div>
                <div class="form-group">
                    <label for="feedback">Feedback:</label>
                    <textarea id="feedback" name="feedback" rows="4" placeholder="Provide feedback for the student..."></textarea>
                </div>
                <button type="submit" class="btn">Save Grade</button>
            </form>
        </div>
    </div>

    <script>
        function gradeSubmission(submissionId, studentName, currentGrade, currentFeedback) {
            document.getElementById('submissionId').value = submissionId;
            document.getElementById('gradeModalTitle').textContent = 'Grade Submission - ' + studentName;
            document.getElementById('grade').value = currentGrade;
            document.getElementById('feedback').value = currentFeedback;
            document.getElementById('gradeModal').style.display = 'block';
        }

        function closeGradeModal() {
            document.getElementById('gradeModal').style.display = 'none';
            document.getElementById('gradeForm').reset();
        }

        // Handle grade form submission
        document.getElementById('gradeForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('assignment-submissions.php?ajax=grade_submission&assignment_id=<?php echo $assignmentId; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Grade saved successfully!');
                    closeGradeModal();
                    location.reload(); // Refresh to show updated grade
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the grade.');
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('gradeModal');
            if (event.target == modal) {
                closeGradeModal();
            }
        }
    </script>
</body>
</html>
