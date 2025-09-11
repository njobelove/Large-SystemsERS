<?php
require __DIR__ . '/../include/db_connect.php';

$username = 'TEST3';
$email = 'test3@gmail.com';
$password = 'test123';
$role = 'marketing';

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $username, $email, $hashed_password, $role);

if ($stmt->execute()) {
    echo "Test marketing user created successfully.\n";
} else {
    echo "Error creating test user: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
