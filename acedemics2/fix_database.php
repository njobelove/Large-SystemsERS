<?php
require_once 'config.php';
require_once 'functions.php';

// Connect to database
$conn = getDBConnection();

try {
    // Add department column
    $sql1 = "ALTER TABLE courses ADD COLUMN department VARCHAR(100) DEFAULT NULL AFTER description";
    if ($conn->query($sql1) === TRUE) {
        echo "✓ Added department column to courses table\n";
    } else {
        echo "Error adding department column: " . $conn->error . "\n";
    }

    // Add semester_id column
    $sql2 = "ALTER TABLE courses ADD COLUMN semester_id INT(11) DEFAULT NULL AFTER instructor_id";
    if ($conn->query($sql2) === TRUE) {
        echo "✓ Added semester_id column to courses table\n";
    } else {
        echo "Error adding semester_id column: " . $conn->error . "\n";
    }

    // Add foreign key constraint
    $sql3 = "ALTER TABLE courses ADD CONSTRAINT fk_courses_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL";
    if ($conn->query($sql3) === TRUE) {
        echo "✓ Added foreign key constraint for semester_id\n";
    } else {
        echo "Error adding foreign key: " . $conn->error . "\n";
    }

    echo "\nDatabase schema updated successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Close connection
$conn->close();
?>
