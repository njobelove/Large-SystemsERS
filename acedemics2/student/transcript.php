<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../session.php';

/*// Check if user is logged in
if (!isStudent()) {
    header('Location: ../index.php');
    exit();
}
*/
// Get student information
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: ../index.php');
    exit();
}

// Handle admin or instructor viewing student's transcript
if ((isAdmin() || isInstructor()) && isset($_GET['student_id'])) {
    $userId = (int)$_GET['student_id'];
}

$student = getSingleRow("SELECT * FROM users WHERE id = ? AND role = 'student'", [$userId], 'i');

// Fetch student's program name
$studentProgramName = 'Not Specified';

// Handle admin or instructor viewing student's transcript
if ((isAdmin() || isInstructor()) && isset($_GET['student_id'])) {
    $studentIdForProgram = (int)$_GET['student_id'];
} else {
    $studentIdForProgram = $userId;
}

if ($student && !empty($student['program_id'])) {
    $prog = getSingleRow("SELECT CONCAT(COALESCE(code,''), CASE WHEN code IS NOT NULL AND code<>'' THEN ' - ' ELSE '' END, name) AS pname FROM programs WHERE id = ?", [$studentIdForProgram], 'i');
    if ($prog && !empty($prog['pname'])) {
        $studentProgramName = $prog['pname'];
    }
}

if (!$student) {
    // Fallback: try to get user info from session
    $student = [
        'id' => $userId,
        'name' => $_SESSION['user_name'] ?? 'Student Name',
        'program' => $_SESSION['user_program'] ?? 'Not Specified',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Get student's enrolled courses and grades
$enrollments = getMultipleRows("
    SELECT
        e.*,
        c.code,
        c.title,
        c.credits,
        g.grade,
        g.grade_letter,
        g.grade_points,
        COALESCE(g.term, s.name) AS display_term
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN grades g ON e.id = g.enrollment_id AND g.assignment_id IS NULL
    LEFT JOIN semesters s ON DATE(e.enrollment_date) BETWEEN s.start_date AND s.end_date
    WHERE e.student_id = ?
    ORDER BY display_term DESC, c.code ASC
", [$userId], 'i');

// Group by term/semester
$transcriptData = [];
foreach ($enrollments as $enrollment) {
    $term = $enrollment['display_term'] ?? 'Current Semester';
    if (!isset($transcriptData[$term])) {
        $transcriptData[$term] = [];
    }
    $transcriptData[$term][] = $enrollment;
}

// Calculate cumulative statistics
$totalCredits = 0;
$totalQualityPoints = 0;

foreach ($enrollments as $enrollment) {
    if (!empty($enrollment['grade'])) {
        $credits = (int)$enrollment['credits'];
        $qualityPoints = (float)$enrollment['grade_points'];
        $totalCredits += $credits;
        $totalQualityPoints += $qualityPoints;
    }
}

$cumulativeGPA = $totalCredits > 0 ? round($totalQualityPoints / $totalCredits, 2) : 0.00;

// Determine academic standing
$academicStanding = 'Good Standing';
if ($cumulativeGPA >= 3.5) $academicStanding = 'Dean\'s List';
else if ($cumulativeGPA >= 3.0) $academicStanding = 'Good Standing';
else if ($cumulativeGPA >= 2.0) $academicStanding = 'Academic Warning';
else $academicStanding = 'Academic Probation';

// Return JSON data for AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'student' => [
            'name' => $student['name'],
            'id' => 'S' . str_pad($student['id'], 4, '0', STR_PAD_LEFT),
            'program' => $student['program'] ?? 'Not Specified',
            'admission_date' => date('F Y', strtotime($student['created_at']))
        ],
        'transcript' => $transcriptData,
        'summary' => [
            'total_credits' => $totalCredits,
            'cumulative_gpa' => $cumulativeGPA,
            'academic_standing' => $academicStanding,
            'credits_required' => 120
        ]
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Transcript</title>
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
                <span><?php echo htmlspecialchars($student['name'] ?? ($_SESSION['user_name'] ?? 'Student')); ?></span>
            </div>
        </nav>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <?php $studentParam = isset($_GET['student_id']) ? '?student_id=' . $_GET['student_id'] : ''; ?>
            <li><a href="dashboard.php<?php echo $studentParam; ?>">Dashboard</a></li>
            <li><a href="register-courses.php<?php echo $studentParam; ?>">Register Courses</a></li>
            <li><a href="view-courses.php<?php echo $studentParam; ?>">View Courses</a></li>
            <li><a href="course-details.php<?php echo $studentParam; ?>">Course Details</a></li>
            <li><a href="view-attendance.php<?php echo $studentParam; ?>">View Attendance</a></li>
            <li><a href="assignments.php<?php echo $studentParam; ?>">Assignments</a></li>
            <li><a href="schedule.php<?php echo $studentParam; ?>">Schedule</a></li>
            <li><a href="transcript.php<?php echo $studentParam; ?>" class="active">Transcript</a></li>
            <li><a href="view-grades.php<?php echo $studentParam; ?>">View Grades</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Academic Transcript</h2>
            <button class="btn" onclick="downloadTranscript()">Download Transcript</button>
        </div>

        <div class="card">
            <div class="transcript-header">
                <h3>ICT UNIVERSITY</h3>
                <p>Official Academic Transcript</p>
                <div class="student-info">
                    <div class="info-item">
                        <strong>Student Name:</strong> <span id="studentNameTranscript"><?php echo htmlspecialchars($student['name'] ?? ($_SESSION['user_name'] ?? 'Student')); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Student ID:</strong> <?php echo 'S' . str_pad($student['id'], 4, '0', STR_PAD_LEFT); ?>
                    </div>
                    <div class="info-item">
                        <strong>Program:</strong> <?php echo htmlspecialchars($studentProgramName ?? ($_SESSION['user_program'] ?? 'Not Specified')); ?>
                    </div>
                    <div class="info-item">
                        <strong>Admission Date:</strong> <?php echo date('F Y', strtotime($student['created_at'])); ?>
                    </div>
                </div>
            </div>

            <!-- Semester-wise transcript sections will be populated by JavaScript -->
            <div id="semesterSections">
                <!-- Dynamic content -->
            </div>

            <div class="transcript-summary">
                <div class="summary-row">
                    <div class="summary-item">
                        <strong>Total Credits Earned:</strong> <span id="totalCredits"><?php echo $totalCredits; ?></span>
                    </div>
                    <div class="summary-item">
                        <strong>Cumulative GPA:</strong> <span id="cumulativeGPA"><?php echo number_format($cumulativeGPA, 2); ?></span>
                    </div>
                </div>
                <div class="summary-row">
                    <div class="summary-item">
                        <strong>Academic Standing:</strong> <span id="academicStanding"><?php echo $academicStanding; ?></span>
                    </div>
                    <div class="summary-item">
                        <strong>Credits Required for Graduation:</strong> 120
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>
    <script src="../js/student.js"></script>
    <script>
        // Grade point values
        const gradePoints = {
            'A': 4.0,
            'B+': 3.5,
            'B': 3.0,
            'C+': 2.5,
            'C': 2.0,
            'D+': 1.5,
            'D': 1.0,
            'F': 0.0
        };

        // Transcript data from PHP
        const transcriptData = <?php echo json_encode($transcriptData); ?>;

        // Load transcript data
        document.addEventListener('DOMContentLoaded', function() {
            // Name already set server-side
            generateTranscript(transcriptData);
        });

        function generateTranscript(transcriptData) {
            const container = document.getElementById('semesterSections');
            container.innerHTML = '';

            let cumulativeCredits = 0;
            let cumulativeQualityPoints = 0;

            // Convert object to array if needed and sort by term (most recent first)
            const semesters = Array.isArray(transcriptData) ? transcriptData : Object.entries(transcriptData)
                .sort(([a], [b]) => b.localeCompare(a)) // Sort terms in descending order
                .map(([term, courses]) => courses);

            // Process each semester
            semesters.forEach(semesterData => {
                if (!Array.isArray(semesterData)) return;

                // Calculate semester statistics
                let semesterCredits = 0;
                let semesterQualityPoints = 0;

                semesterData.forEach(course => {
                    const credits = parseInt(course.credits) || 0;
                    const gradePointsValue = parseFloat(course.grade_points) || 0;

                    // Only count courses with valid grades
                    if (course.grade && course.grade !== 'Not Graded' && gradePointsValue > 0) {
                        semesterCredits += credits;
                        semesterQualityPoints += gradePointsValue;
                    }
                });

                const semesterGPA = semesterCredits > 0 ? (semesterQualityPoints / semesterCredits).toFixed(2) : '0.00';

                // Update cumulative totals (accumulate across all semesters)
                cumulativeCredits += semesterCredits;
                cumulativeQualityPoints += semesterQualityPoints;
                const cumulativeGPA = cumulativeCredits > 0 ? (cumulativeQualityPoints / cumulativeCredits).toFixed(2) : '0.00';

                // Create semester section
                const semesterDiv = document.createElement('div');
                semesterDiv.className = 'semester-section';
                semesterDiv.innerHTML = `
                    <h4 class="semester-title">${semesterData[0]?.display_term || 'Current Semester'}</h4>
                    <div class="table-container">
                        <table class="semester-table">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Title</th>
                                    <th>Credits</th>
                                    <th>Grade</th>
                                    <th>Quality Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${semesterData.map(course => {
                                    const letter = course.grade_letter || (course.grade ? course.grade : 'Not Graded');
                                    const creds = parseInt(course.credits) || 0;
                                    const qp = parseFloat(course.grade_points) || 0;
                                    return `
                                        <tr>
                                            <td>${course.code}</td>
                                            <td>${course.title}</td>
                                            <td>${creds}</td>
                                            <td>${letter}</td>
                                            <td>${qp.toFixed(1)}</td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div class="semester-summary">
                        <div class="semester-stats">
                            <span><strong>Semester Credits:</strong> ${semesterCredits}</span>
                            <span><strong>Semester GPA:</strong> ${semesterGPA}</span>
                            <span><strong>Cumulative Credits:</strong> ${cumulativeCredits}</span>
                            <span><strong>Cumulative GPA:</strong> ${cumulativeGPA}</span>
                        </div>
                    </div>
                `;

                container.appendChild(semesterDiv);
            });

            // Update overall summary with accumulated values
            const finalCumulativeGPA = cumulativeCredits > 0 ? (cumulativeQualityPoints / cumulativeCredits).toFixed(2) : '0.00';
            document.getElementById('totalCredits').textContent = cumulativeCredits;
            document.getElementById('cumulativeGPA').textContent = finalCumulativeGPA;

            // Determine academic standing
            const gpa = parseFloat(finalCumulativeGPA);
            let standing = 'Good Standing';
            if (gpa >= 3.5) standing = 'Dean\'s List';
            else if (gpa >= 3.0) standing = 'Good Standing';
            else if (gpa >= 2.0) standing = 'Academic Warning';
            else standing = 'Academic Probation';

            document.getElementById('academicStanding').textContent = standing;
        }

        function downloadTranscript() {
            // Create a printable version
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Popup blocked! Please allow popups for this site to download/print your transcript.');
                return;
            }
            const transcriptContent = document.querySelector('.card').innerHTML;

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Official Academic Transcript - <?php echo htmlspecialchars($student['name']); ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .transcript-header { text-align: center; margin-bottom: 30px; }
                        .student-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 20px 0; }
                        .semester-section { margin: 30px 0; page-break-inside: avoid; }
                        .semester-title { background-color: #f0f0f0; padding: 10px; margin: 0; }
                        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; }
                        .semester-summary { margin: 15px 0; padding: 10px; background-color: #f9f9f9; }
                        .semester-stats { display: flex; justify-content: space-around; }
                        .transcript-summary { margin-top: 30px; padding: 20px; background-color: #f0f0f0; }
                        .summary-row { display: flex; justify-content: space-between; margin: 10px 0; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <div class="transcript-header">
                        <h3>ICT UNIVERSITY</h3>
                        <p>Official Academic Transcript</p>
                    </div>
                    ${transcriptContent}
                    <div style="margin-top: 30px; text-align: center; font-size: 12px;">
                        <p>This is an official transcript generated on ${new Date().toLocaleDateString()}</p>
                        <p>ICT University Registrar Office</p>
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                            window.close();
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
            console.log('Transcript downloaded/printed');
        }
    </script>
</body>
</html>
