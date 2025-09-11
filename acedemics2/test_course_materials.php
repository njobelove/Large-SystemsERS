<?php
require_once 'config.php';
require_once 'functions.php';

// Test script for course materials upload and download functionality

echo "<h1>Course Materials System Test</h1>";

// Test 1: Check if course_materials table exists and has correct structure
echo "<h2>Test 1: Database Table Structure</h2>";
try {
    $sql = "DESCRIBE course_materials";
    $columns = getMultipleRows($sql, [], '');
    echo "<p>✓ course_materials table exists with " . count($columns) . " columns</p>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>{$col['Field']} - {$col['Type']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 2: Check if uploads/materials directory exists
echo "<h2>Test 2: Upload Directory</h2>";
$uploadDir = 'uploads/materials/';
if (is_dir($uploadDir)) {
    echo "<p>✓ Upload directory exists: $uploadDir</p>";
    if (is_writable($uploadDir)) {
        echo "<p>✓ Directory is writable</p>";
    } else {
        echo "<p>✗ Directory is not writable</p>";
    }
} else {
    echo "<p>✗ Upload directory does not exist: $uploadDir</p>";
}

// Test 3: Check existing materials
echo "<h2>Test 3: Existing Materials</h2>";
try {
    $sql = "SELECT COUNT(*) as count FROM course_materials";
    $result = getSingleRow($sql, [], '');
    echo "<p>✓ Found {$result['count']} materials in database</p>";

    if ($result['count'] > 0) {
        $materials = getMultipleRows("SELECT id, title, course_id, file_path FROM course_materials LIMIT 5", [], '');
        echo "<ul>";
        foreach ($materials as $material) {
            echo "<li>ID: {$material['id']}, Title: {$material['title']}, Course: {$material['course_id']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p>✗ Error querying materials: " . $e->getMessage() . "</p>";
}

// Test 4: Check courses and enrollments
echo "<h2>Test 4: Courses and Enrollments</h2>";
try {
    $courses = getMultipleRows("SELECT id, code, title FROM courses LIMIT 3", [], '');
    echo "<p>✓ Found " . count($courses) . " courses</p>";
    echo "<ul>";
    foreach ($courses as $course) {
        echo "<li>{$course['code']} - {$course['title']}</li>";
    }
    echo "</ul>";

    $enrollments = getMultipleRows("SELECT COUNT(*) as count FROM enrollments WHERE status = 'enrolled'", [], '');
    echo "<p>✓ Found {$enrollments[0]['count']} active enrollments</p>";
} catch (Exception $e) {
    echo "<p>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 5: Test student access query
echo "<h2>Test 5: Student Access Query</h2>";
$testStudentId = 4; // Alice Johnson from sample data
$testCourseId = 1; // CS101

try {
    $sql = "SELECT cm.id, cm.title, cm.description, cm.file_path, cm.created_at as upload_date
            FROM course_materials cm
            JOIN courses c ON cm.course_id = c.id
            JOIN enrollments e ON c.id = e.course_id
            WHERE cm.course_id = ? AND e.student_id = ? AND e.status = 'enrolled'
            ORDER BY cm.created_at DESC";
    $materials = getMultipleRows($sql, [$testCourseId, $testStudentId], 'ii');
    echo "<p>✓ Student access query works, found " . count($materials) . " materials for student $testStudentId in course $testCourseId</p>";
} catch (Exception $e) {
    echo "<p>✗ Error in student access query: " . $e->getMessage() . "</p>";
}

echo "<h2>Test Summary</h2>";
echo "<p>Course materials system test completed. Check the results above for any issues.</p>";
?>
