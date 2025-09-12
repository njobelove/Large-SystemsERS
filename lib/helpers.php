<?php
// lib/helpers.php
function json($data) {
    echo json_encode($data);
    exit;
}

function require_auth() {
    if (empty($_SESSION['user'])) {
        http_response_code(401);
        json(['error' => 'Unauthorized']);
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}
