<?php
// scripts/create_admin.php
// This helper will create an admin user in the database using the credentials below.
// Usage: open this file in your browser while the app is running, or run from CLI (php scripts/create_admin.php).
require_once __DIR__ . '/../api/config.php';

$username = 'admin';
$email = 'admin@example.com';
$password = 'Admin@123'; // change before running in production
$hash = password_hash($password, PASSWORD_DEFAULT);

// check if admin exists
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "Admin user already exists.\n";
    exit;
}

$stmt = $pdo->prepare('INSERT INTO users (username,email,password,role,first_name,last_name) VALUES (?,?,?,?,?,?)');
$stmt->execute([$username, $email, $hash, 'admin', 'System', 'Administrator']);
echo "Admin created. Username: {$username} Password: {$password}\n";
