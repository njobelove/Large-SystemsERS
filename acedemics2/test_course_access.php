<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'session.php';

// Test script to verify course access for instructor
echo "<h1>Course Access Test</h1>";

// Get current user info
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userRole = $_SESSION['user_role'];

echo "<h2>Current User Info:</h2>";
echo "User ID: $userId<br>";
echo "User Name: $userName<br>";
echo "User Role: $userRole<br><br>";

// Test database connection
try {
    $conn = getDBConnection();
    echo "<h2>Database Connection: SUCCESS</h2>";
} catch (Exception $e) {
    echo "<h2>Database Connection: FAILED</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    exit();
}

// Test courses query
echo "<h2>Courses for Instructor ID $userId:</h2>";
try {
    $courses = getInstructorCourses($userId);
    if (empty($courses)) {
        echo "No courses found for this instructor.<br>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Code</th><th>Title</th><th>Enrolled Students</th></tr>";
        foreach ($courses as $course) {
            echo "<tr>";
            echo "<td>" . $course['id'] . "</td>";
            echo "<td>" . $course['code'] . "</td>";
            echo "<td>" . $course['title'] . "</td>";
            echo "<td>" . $course['enrolled_students'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error querying courses: " . $e->getMessage() . "<br>";
}

// Test specific course lookup
echo "<h2>Test Specific Course Lookup:</h2>";
$testCourseCode = 'CS101'; // You can change this to test different course codes
try {
    $course = getSingleRow("
        SELECT c.* FROM courses c
        WHERE c.code = ? AND c.instructor_id = ?
    ", [$testCourseCode, $userId], 'si');

    if ($course) {
        echo "Course '$testCourseCode' found:<br>";
        echo "ID: " . $course['id'] . "<br>";
        echo "Code: " . $course['code'] . "<br>";
        echo "Title: " . $course['title'] . "<br>";
    } else {
        echo "Course '$testCourseCode' NOT found for instructor ID $userId<br>";
    }
} catch (Exception $e) {
    echo "Error looking up course: " . $e->getMessage() . "<br>";
}

echo "<br><a href='instructor/dashboard.php'>Back to Instructor Dashboard</a>";
?>
