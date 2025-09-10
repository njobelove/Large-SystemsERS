<?php
require_once 'config.php';
require_once 'functions.php';

echo "<h1>Assignment Submission & Grading System - Database Test</h1>";

// Test database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit();
}

// Test assignments table structure
echo "<h2>Testing Assignments Table</h2>";
try {
    $result = $conn->query("DESCRIBE assignments");
    if ($result) {
        echo "<p style='color: green;'>✓ Assignments table exists</p>";
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }

        $required_columns = ['id', 'course_id', 'title', 'description', 'due_date', 'max_points', 'created_at'];
        $missing_columns = array_diff($required_columns, $columns);

        if (empty($missing_columns)) {
            echo "<p style='color: green;'>✓ All required columns present</p>";
        } else {
            echo "<p style='color: red;'>✗ Missing columns: " . implode(', ', $missing_columns) . "</p>";
        }

        // Check for file_path column
        if (in_array('file_path', $columns)) {
            echo "<p style='color: green;'>✓ file_path column exists for assignment files</p>";
        } else {
            echo "<p style='color: red;'>✗ file_path column missing - assignment file uploads won't work</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Assignments table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking assignments table: " . $e->getMessage() . "</p>";
}

// Test submissions table structure
echo "<h2>Testing Submissions Table</h2>";
try {
    $result = $conn->query("DESCRIBE submissions");
    if ($result) {
        echo "<p style='color: green;'>✓ Submissions table exists</p>";
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }

        $required_columns = ['id', 'assignment_id', 'student_id', 'submitted_at', 'file_path', 'feedback'];
        $missing_columns = array_diff($required_columns, $columns);

        if (empty($missing_columns)) {
            echo "<p style='color: green;'>✓ All required columns present</p>";
        } else {
            echo "<p style='color: red;'>✗ Missing columns: " . implode(', ', $missing_columns) . "</p>";
        }

        // Check for grade column
        if (in_array('grade', $columns)) {
            echo "<p style='color: green;'>✓ grade column exists for grading</p>";
        } else {
            echo "<p style='color: red;'>✗ grade column missing - grading won't work</p>";
        }

        // Check for graded column
        if (in_array('graded', $columns)) {
            echo "<p style='color: green;'>✓ graded column exists for tracking graded status</p>";
        } else {
            echo "<p style='color: red;'>✗ graded column missing - graded status tracking won't work</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Submissions table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking submissions table: " . $e->getMessage() . "</p>";
}

// Test foreign key relationships
echo "<h2>Testing Foreign Key Relationships</h2>";
try {
    // Check if assignments.course_id references courses.id
    $result = $conn->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'assignments' AND COLUMN_NAME = 'course_id'
    ");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Foreign key relationship exists: assignments.course_id → courses.id</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No foreign key constraint found for assignments.course_id</p>";
    }

    // Check if submissions.assignment_id references assignments.id
    $result = $conn->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'submissions' AND COLUMN_NAME = 'assignment_id'
    ");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Foreign key relationship exists: submissions.assignment_id → assignments.id</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No foreign key constraint found for submissions.assignment_id</p>";
    }

    // Check if submissions.student_id references users.id
    $result = $conn->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'submissions' AND COLUMN_NAME = 'student_id'
    ");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Foreign key relationship exists: submissions.student_id → users.id</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No foreign key constraint found for submissions.student_id</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking foreign key relationships: " . $e->getMessage() . "</p>";
}

// Test directory structure
echo "<h2>Testing Directory Structure</h2>";
$upload_dirs = [
    'uploads/assignments' => 'Assignment files directory',
    'uploads/submissions' => 'Student submission files directory'
];

foreach ($upload_dirs as $dir => $description) {
    if (is_dir($dir)) {
        echo "<p style='color: green;'>✓ $description exists: $dir</p>";
        if (is_writable($dir)) {
            echo "<p style='color: green;'>✓ $description is writable</p>";
        } else {
            echo "<p style='color: red;'>✗ $description is not writable</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ $description does not exist: $dir</p>";
        if (mkdir($dir, 0755, true)) {
            echo "<p style='color: green;'>✓ Created directory: $dir</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create directory: $dir</p>";
        }
    }
}

// Test sample data
echo "<h2>Testing Sample Data</h2>";
try {
    // Check for sample assignments
    $result = $conn->query("SELECT COUNT(*) as count FROM assignments");
    if ($result) {
        $row = $result->fetch_assoc();
        $assignment_count = $row['count'];
        echo "<p style='color: blue;'>ℹ Found $assignment_count assignments in database</p>";
    }

    // Check for sample submissions
    $result = $conn->query("SELECT COUNT(*) as count FROM submissions");
    if ($result) {
        $row = $result->fetch_assoc();
        $submission_count = $row['count'];
        echo "<p style='color: blue;'>ℹ Found $submission_count submissions in database</p>";
    }

    // Check for enrolled students
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'enrolled'");
    if ($result) {
        $row = $result->fetch_assoc();
        $enrollment_count = $row['count'];
        echo "<p style='color: blue;'>ℹ Found $enrollment_count active enrollments</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking sample data: " . $e->getMessage() . "</p>";
}

$conn->close();

echo "<h2>Test Summary</h2>";
echo "<p>Database structure test completed. Check the results above for any issues that need to be addressed.</p>";
echo "<p><a href='student/assignments.php'>Test Student Assignment View</a> | <a href='instructor/assignments.php'>Test Instructor Assignment View</a></p>";
?>
