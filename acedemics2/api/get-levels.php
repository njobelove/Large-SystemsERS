<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

// Get all levels
$levels = getMultipleRows("
    SELECT id, name, code, description
    FROM levels
    ORDER BY name ASC
", [], '');

if ($levels) {
    echo json_encode(['success' => true, 'levels' => $levels]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to load levels']);
}
?>
