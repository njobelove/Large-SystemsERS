<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

// Get all programs
$programs = getMultipleRows("
    SELECT id, code, name, department, degree_level, total_credits
    FROM programs
    ORDER BY name ASC
", [], '');

if ($programs) {
    echo json_encode(['success' => true, 'programs' => $programs]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to load programs']);
}
?>
