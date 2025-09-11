<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if the user is logged in and if their role is allowed.
 * 
 * @param array $allowedRoles Array of roles allowed to access the page.
 * @param string $loginPath The path to the login page.
 */
function checkRoleAccess(array $allowedRoles, string $loginPath = '/yes/school/login.php') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        // Not logged in
        header("Location: " . $loginPath);
        exit;
    }

    if (!in_array($_SESSION['role'], $allowedRoles)) {
        // Role not authorized, redirect to login with an error
        header("Location: " . $loginPath . "?error=access_denied");
        exit;
    }
}
?>