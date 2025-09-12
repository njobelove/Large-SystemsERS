<?php
// lib/auth.php
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/helpers.php';

function login_user($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $username]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password'])) {
        $_SESSION['user'] = [
            'id' => $u['id'],
            'username' => $u['username'],
            'role' => $u['role'],
            'first_name' => $u['first_name'],
            'last_name' => $u['last_name']
        ];
        return true;
    }
    return false;
}

function logout_user() {
    unset($_SESSION['user']);
    session_destroy();
}
