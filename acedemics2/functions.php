<?php
require_once 'config.php';

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Execute prepared statement and return result
function executeQuery($sql, $params = [], $types = '') {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt;
}

// Get single row from query
function getSingleRow($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    if (!$stmt) return null;

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row;
}

// Check if current user is a student
function isStudent() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student');
}

// Check if current user is an admin
function isAdmin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

// Check if current user is an instructor
function isInstructor() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'instructor');
}

// Get multiple rows from query
function getMultipleRows($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    if (!$stmt) return [];

    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

// Execute non-query (INSERT, UPDATE, DELETE)
function executeNonQuery($sql, $params = [], $types = '') {
    $stmt = executeQuery($sql, $params, $types);
    if (!$stmt) return false;

    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    return $affectedRows > 0;
}

// Get user by ID
function getUserById($userId) {
    $sql = "SELECT id, name, email, role, program_id, level_id, phone, date_of_birth, place_of_birth, last_school_attended FROM users WHERE id = ?";
    return getSingleRow($sql, [$userId], 'i');
}

// Get courses for student
function getStudentCourses($studentId) {
    $sql = "SELECT c.id, c.code, c.title, c.description, u.name as instructor_name
            FROM courses c
            JOIN enrollments e ON c.id = e.course_id
            LEFT JOIN users u ON c.instructor_id = u.id
            WHERE e.student_id = ? AND e.status = 'enrolled'
            ORDER BY c.title";
    try {
        return getMultipleRows($sql, [$studentId], 'i');
    } catch (Exception $e) {
        // Fallback without status filter
        $sqlFallback = "SELECT c.id, c.code, c.title, c.description, u.name as instructor_name
                        FROM courses c
                        JOIN enrollments e ON c.id = e.course_id
                        LEFT JOIN users u ON c.instructor_id = u.id
                        WHERE e.student_id = ?
                        ORDER BY c.title";
        return getMultipleRows($sqlFallback, [$studentId], 'i');
    }
}

// Get courses for instructor
function getInstructorCourses($instructorId) {
    $sql = "SELECT c.id, c.code, c.title, c.description,
                   COUNT(e.student_id) as enrolled_students
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
            WHERE c.instructor_id = ?
            GROUP BY c.id
            ORDER BY c.title";
    try {
        return getMultipleRows($sql, [$instructorId], 'i');
    } catch (Exception $e) {
        // Fallback without status filter
        $sqlFallback = "SELECT c.id, c.code, c.title, c.description,
                               COUNT(e.student_id) as enrolled_students
                        FROM courses c
                        LEFT JOIN enrollments e ON c.id = e.course_id
                        WHERE c.instructor_id = ?
                        GROUP BY c.id
                        ORDER BY c.title";
        return getMultipleRows($sqlFallback, [$instructorId], 'i');
    }
}

// Get assignments for course
function getCourseAssignments($courseId) {
    try {
        $sql = "SELECT id, title, description, due_date, max_points
                FROM assignments
                WHERE course_id = ?
                ORDER BY due_date DESC";
        return getMultipleRows($sql, [$courseId], 'i');
    } catch (Exception $e) {
        // Table doesn't exist, return empty array
        return [];
    }
}

// Get student grade for course
function getStudentGrade($studentId, $courseId) {
    try {
        $sql = "SELECT grade, grade_letter FROM grades
                WHERE student_id = ? AND course_id = ?";
        return getSingleRow($sql, [$studentId, $courseId], 'ii');
    } catch (Exception $e) {
        // Table doesn't exist, return null
        return null;
    }
}

// Calculate cumulative GPA for student (weighted by credits)
function calculateGPA($studentId) {
    try {
        // Get all enrollments with grades and course credits
        $sql = "SELECT
                    e.*,
                    c.credits,
                    g.grade_points
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                LEFT JOIN grades g ON e.id = g.enrollment_id AND g.assignment_id IS NULL
                WHERE e.student_id = ? AND g.grade_points IS NOT NULL";

        $enrollments = getMultipleRows($sql, [$studentId], 'i');

        if (empty($enrollments)) {
            return ['gpa' => 0, 'total_courses' => 0, 'total_credits' => 0];
        }

        $totalCredits = 0;
        $totalQualityPoints = 0;
        $totalCourses = 0;

        foreach ($enrollments as $enrollment) {
            $credits = (int)$enrollment['credits'];
            $qualityPoints = (float)$enrollment['grade_points'];

            $totalCredits += $credits;
            $totalQualityPoints += $qualityPoints;
            $totalCourses++;
        }

        $cumulativeGPA = $totalCredits > 0 ? round($totalQualityPoints / $totalCredits, 2) : 0.00;

        return [
            'gpa' => $cumulativeGPA,
            'total_courses' => $totalCourses,
            'total_credits' => $totalCredits
        ];
    } catch (Exception $e) {
        // Table doesn't exist or error, return default values
        return ['gpa' => 0, 'total_courses' => 0, 'total_credits' => 0];
    }
}

// Get attendance percentage for student
function getAttendancePercentage($studentId) {
    try {
        $sql = "SELECT
                    COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
                    COUNT(*) as total_count
                FROM attendance
                WHERE student_id = ?";
        $result = getSingleRow($sql, [$studentId], 'i');

        if (!$result || $result['total_count'] == 0) {
            return 0;
        }

        return round(($result['present_count'] / $result['total_count']) * 100, 1);
    } catch (Exception $e) {
        // Table doesn't exist, return 0
        return 0;
    }
}

// Get upcoming deadlines for student
function getUpcomingDeadlines($studentId) {
    $sql = "SELECT a.title, a.due_date, c.title as course_title
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            JOIN enrollments e ON c.id = e.course_id
            WHERE e.student_id = ? AND e.status = 'enrolled'
            AND a.due_date > NOW()
            ORDER BY a.due_date ASC
            LIMIT 5";
    try {
        return getMultipleRows($sql, [$studentId], 'i');
    } catch (Exception $e) {
        // Fallback without status filter
        $sqlFallback = "SELECT a.title, a.due_date, c.title as course_title
                        FROM assignments a
                        JOIN courses c ON a.course_id = c.id
                        JOIN enrollments e ON c.id = e.course_id
                        WHERE e.student_id = ?
                        AND a.due_date > NOW()
                        ORDER BY a.due_date ASC
                        LIMIT 5";
        return getMultipleRows($sqlFallback, [$studentId], 'i');
    }
}

// Format date for display
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime for display
function formatDateTime($datetime) {
    return date('M d, Y H:i', strtotime($datetime));
}

// Check if user is logged in (for future use)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect to login if not logged in (for future use)
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.html');
        exit();
    }
}
?>
