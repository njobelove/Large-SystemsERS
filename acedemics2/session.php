<?php
// Start session
session_start();

// For demo purposes, assume user is logged in
// In a real application, this would be set during login
if (!isset($_SESSION['user_id'])) {
    // Demo user - Instructor (ID: 2)
    $_SESSION['user_id'] = 2;
    $_SESSION['user_name'] = 'Jane Smith';
    $_SESSION['user_email'] = 'jane@instructor.edu';
    $_SESSION['user_role'] = 'instructor';
}

// You can change the demo user by uncommenting one of these:
// $_SESSION['user_id'] = 2; // Instructor
// $_SESSION['user_name'] = 'Jane Smith';
// $_SESSION['user_email'] = 'jane@instructor.edu';
// $_SESSION['user_role'] = 'instructor';

// $_SESSION['user_id'] = 3; // Admin
// $_SESSION['user_name'] = 'Admin User';
// $_SESSION['user_email'] = 'admin@system.edu';
// $_SESSION['user_role'] = 'admin';

// Get current user info
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null
    ];
}

// Check if user has specific role
function hasRole($role) {
    return ($_SESSION['user_role'] ?? '') === $role;
}

// Check if user is student (function is defined in functions.php)

// ...existing code...

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Logout function (for future use)
function logout() {
    session_destroy();
    header('Location: ../index.php');
    exit();
}
?>
